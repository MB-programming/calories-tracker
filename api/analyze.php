<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

// ─── Settings ────────────────────────────────────────────────────────────────
$primaryProvider    = getSetting($pdo, 'ai_provider', 'gemini');
$consensusMode      = getSetting($pdo, 'consensus_mode', '0') === '1';
$consensusProviders = json_decode(getSetting($pdo, 'consensus_providers', '[]'), true) ?: [];
$fallbackProviders  = json_decode(getSetting($pdo, 'fallback_providers',  '[]'), true) ?: [];
$fallbackEnabled    = getSetting($pdo, 'fallback_enabled', '1') === '1';

$fallbackChain = array_unique(array_merge(
    [$primaryProvider],
    $fallbackEnabled ? $fallbackProviders : []
));

// ─── Prompts ─────────────────────────────────────────────────────────────────
$jsonPrompt = <<<PROMPT
أنت خبير تغذية. حلل الطعام وأعط تقديراً دقيقاً للقيم الغذائية.
أجب بـ JSON فقط بالصيغة التالية بدون أي نص إضافي:
{
  "food_name": "اسم الطعام بالعربي",
  "calories": 350,
  "protein": 15.5,
  "carbs": 40.0,
  "fat": 12.0,
  "description": "وصف مختصر للطعام",
  "confidence": "high"
}
إذا لم تتمكن من التعرف على الطعام، أعد: {"error": "لا يمكن التعرف على الطعام"}
PROMPT;

$textSystemMsg = 'أنت خبير تغذية. عندما يسألك المستخدم عن طعام أو يصفه، أعط القيم الغذائية بصيغة JSON فقط بدون أي نص إضافي: {"food_name":"...","calories":0,"protein":0,"carbs":0,"fat":0,"description":"...","confidence":"medium"}. إذا كان السؤال عاماً لا يتعلق بوجبة محددة أو وصف كمية، أعد {"error":"يرجى وصف الطعام أو الكمية بشكل أوضح"}.';

// ─── Text Query Mode ──────────────────────────────────────────────────────────
$textQuery = trim($_POST['text_query'] ?? '');
if ($textQuery) {
    if ($consensusMode && count($consensusProviders) >= 2) {
        $result = runConsensusText($consensusProviders, $pdo, $textSystemMsg, $textQuery);
    } else {
        $result = runFallbackText($fallbackChain, $pdo, $textSystemMsg, $textQuery);
    }
    echo json_encode($result);
    exit;
}

// ─── Image Mode ──────────────────────────────────────────────────────────────
$imageData = null;
$mimeType  = 'image/jpeg';

if (!empty($_FILES['image']['tmp_name'])) {
    $imageData = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
    $mimeType  = $_FILES['image']['type'] ?: 'image/jpeg';
} elseif (!empty($_POST['image_base64'])) {
    $raw = $_POST['image_base64'];
    if (preg_match('/^data:([^;]+);base64,(.+)$/', $raw, $m)) {
        $mimeType  = $m[1];
        $imageData = $m[2];
    } else {
        $imageData = $raw;
    }
}

if (!$imageData) {
    echo json_encode(['success' => false, 'message' => 'لم يتم إرسال صورة أو نص']);
    exit;
}

if ($consensusMode && count($consensusProviders) >= 2) {
    $result = runConsensusVision($consensusProviders, $pdo, $imageData, $mimeType, $jsonPrompt);
} else {
    $result = runFallbackVision($fallbackChain, $pdo, $imageData, $mimeType, $jsonPrompt);
}
echo json_encode($result);


// ═══════════════════ CONSENSUS ═══════════════════════════════════════════════

function runConsensusVision(array $providers, $pdo, string $imageData, string $mimeType, string $prompt): array
{
    $active = array_values(array_filter($providers, fn($p) => isProviderConfigured($p, $pdo)));
    if (count($active) < 2) {
        return runFallbackVision(count($active) ? $active : ['gemini'], $pdo, $imageData, $mimeType, $prompt);
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($active as $provider) {
        $info = buildVisionCurlHandle($provider, $pdo, $imageData, $mimeType, $prompt);
        if (!$info) continue;
        curl_multi_add_handle($mh, $info['ch']);
        $handles[$provider] = $info;
    }

    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 0.5);
    } while ($running && $status === CURLM_OK);

    $results = [];
    foreach ($handles as $provider => $info) {
        $response  = curl_multi_getcontent($info['ch']);
        $httpCode  = curl_getinfo($info['ch'], CURLINFO_HTTP_CODE);
        $curlErr   = curl_error($info['ch']);
        curl_multi_remove_handle($mh, $info['ch']);
        curl_close($info['ch']);

        if ($curlErr || !$response) {
            $results[$provider] = ['success' => false, 'provider' => $provider];
            continue;
        }
        $parsed = parseProviderResponse($provider, $response, $httpCode, 'vision');
        $parsed['provider'] = $provider;
        $results[$provider] = $parsed;
    }
    curl_multi_close($mh);

    $ok = array_values(array_filter($results, fn($r) => $r['success']));
    if (empty($ok)) return ['success' => false, 'message' => 'فشلت جميع النماذج في التحليل'];
    if (count($ok) === 1) {
        $ok[0]['data']['confidence'] = 'low';
        $ok[0]['consensus_info'] = ['mode'=>'single','providers_used'=>1,'providers_total'=>count($active),'provider'=>$ok[0]['provider']];
        return $ok[0];
    }
    return buildConsensusResult($ok, count($active));
}

function runConsensusText(array $providers, $pdo, string $systemMsg, string $userMsg): array
{
    $active = array_values(array_filter($providers, fn($p) => isProviderConfigured($p, $pdo)));
    if (count($active) < 2) {
        return runFallbackText(count($active) ? $active : ['gemini'], $pdo, $systemMsg, $userMsg);
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($active as $provider) {
        $info = buildTextCurlHandle($provider, $pdo, $systemMsg, $userMsg);
        if (!$info) continue;
        curl_multi_add_handle($mh, $info['ch']);
        $handles[$provider] = $info;
    }

    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 0.5);
    } while ($running && $status === CURLM_OK);

    $results = [];
    foreach ($handles as $provider => $info) {
        $response  = curl_multi_getcontent($info['ch']);
        $httpCode  = curl_getinfo($info['ch'], CURLINFO_HTTP_CODE);
        $curlErr   = curl_error($info['ch']);
        curl_multi_remove_handle($mh, $info['ch']);
        curl_close($info['ch']);

        if ($curlErr || !$response) {
            $results[$provider] = ['success' => false, 'provider' => $provider];
            continue;
        }
        $parsed = parseProviderResponse($provider, $response, $httpCode, 'text');
        $parsed['provider'] = $provider;
        $results[$provider] = $parsed;
    }
    curl_multi_close($mh);

    $ok = array_values(array_filter($results, fn($r) => $r['success']));
    if (empty($ok)) return ['success' => false, 'message' => 'فشلت جميع النماذج في التحليل'];
    if (count($ok) === 1) {
        $ok[0]['consensus_info'] = ['mode'=>'single','providers_used'=>1,'providers_total'=>count($active),'provider'=>$ok[0]['provider']];
        return $ok[0];
    }
    return buildConsensusResult($ok, count($active));
}

function buildConsensusResult(array $ok, int $total): array
{
    $n   = count($ok);
    $cal = array_map(fn($r) => (float)($r['data']['calories'] ?? 0), $ok);
    $pro = array_map(fn($r) => (float)($r['data']['protein']  ?? 0), $ok);
    $crb = array_map(fn($r) => (float)($r['data']['carbs']    ?? 0), $ok);
    $fat = array_map(fn($r) => (float)($r['data']['fat']      ?? 0), $ok);

    $avgCal  = round(array_sum($cal) / $n);
    $avgPro  = round(array_sum($pro) / $n, 1);
    $avgCrb  = round(array_sum($crb) / $n, 1);
    $avgFat  = round(array_sum($fat) / $n, 1);

    $spread = $avgCal > 0 ? (max($cal) - min($cal)) / $avgCal * 100 : 0;
    if ($spread < 15)     $confidence = 'high';
    elseif ($spread < 35) $confidence = 'medium';
    else                  $confidence = 'low';

    $best = $ok[0];
    foreach ($ok as $r) {
        if (($r['data']['confidence'] ?? '') === 'high') { $best = $r; break; }
    }

    return [
        'success' => true,
        'data'    => [
            'food_name'   => $best['data']['food_name'],
            'calories'    => $avgCal,
            'protein'     => $avgPro,
            'carbs'       => $avgCrb,
            'fat'         => $avgFat,
            'description' => $best['data']['description'] ?? '',
            'confidence'  => $confidence,
        ],
        'consensus_info' => [
            'mode'           => 'consensus',
            'providers_used' => $n,
            'providers_total'=> $total,
            'cal_spread_pct' => round($spread, 1),
            'providers'      => array_map(fn($r) => [
                'provider' => $r['provider'],
                'calories' => (int)($r['data']['calories'] ?? 0),
            ], $ok),
        ],
    ];
}


// ═══════════════════ FALLBACK ════════════════════════════════════════════════

function runFallbackVision(array $chain, $pdo, string $imageData, string $mimeType, string $prompt): array
{
    $lastError = 'لا يوجد مزود API مُعدّ';
    foreach ($chain as $provider) {
        if (!isProviderConfigured($provider, $pdo)) continue;
        if ($provider === 'gemini') {
            $result = callGeminiVision($pdo, $imageData, $mimeType, $prompt);
        } else {
            $info = buildVisionCurlHandle($provider, $pdo, $imageData, $mimeType, $prompt);
            if (!$info) continue;
            $response  = curl_exec($info['ch']);
            $httpCode  = curl_getinfo($info['ch'], CURLINFO_HTTP_CODE);
            $curlErr   = curl_error($info['ch']);
            curl_close($info['ch']);
            if ($curlErr) { $lastError = 'خطأ في الاتصال: '.$curlErr; continue; }
            $result = parseProviderResponse($provider, $response, $httpCode, 'vision');
        }
        if ($result['success']) return $result;
        $lastError = $result['message'] ?? $lastError;
    }
    return ['success' => false, 'message' => $lastError];
}

function runFallbackText(array $chain, $pdo, string $systemMsg, string $userMsg): array
{
    $lastError = 'لا يوجد مزود API مُعدّ';
    foreach ($chain as $provider) {
        if (!isProviderConfigured($provider, $pdo)) continue;
        if ($provider === 'gemini') {
            $result = callGeminiText($pdo, $systemMsg, $userMsg);
        } else {
            $info = buildTextCurlHandle($provider, $pdo, $systemMsg, $userMsg);
            if (!$info) continue;
            $response  = curl_exec($info['ch']);
            $httpCode  = curl_getinfo($info['ch'], CURLINFO_HTTP_CODE);
            $curlErr   = curl_error($info['ch']);
            curl_close($info['ch']);
            if ($curlErr) { $lastError = 'خطأ في الاتصال: '.$curlErr; continue; }
            $result = parseProviderResponse($provider, $response, $httpCode, 'text');
        }
        if ($result['success']) return $result;
        $lastError = $result['message'] ?? $lastError;
    }
    return ['success' => false, 'message' => $lastError];
}


// ═══════════════════ CURL HANDLE BUILDERS ════════════════════════════════════

function buildVisionCurlHandle(string $provider, $pdo, string $imageData, string $mimeType, string $prompt): ?array
{
    switch ($provider) {
        case 'openai':
            $key   = getSetting($pdo, 'openai_api_key', '');
            $model = getSetting($pdo, 'openai_model', 'gpt-4o');
            if (!$key) return null;
            return makeOpenAICompatVisionHandle(
                'https://api.openai.com/v1/chat/completions',
                ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
                $model, $prompt, $imageData, $mimeType
            );

        case 'anthropic':
            $key   = getSetting($pdo, 'anthropic_api_key', '');
            $model = getSetting($pdo, 'anthropic_model', 'claude-sonnet-4-6');
            if (!$key) return null;
            $payload = ['model'=>$model,'max_tokens'=>500,'messages'=>[[
                'role'    => 'user',
                'content' => [
                    ['type'=>'image','source'=>['type'=>'base64','media_type'=>$mimeType,'data'=>$imageData]],
                    ['type'=>'text','text'=>$prompt],
                ],
            ]]];
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$key,'anthropic-version: 2023-06-01'],
                CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
            return ['ch'=>$ch, 'type'=>'anthropic'];

        case 'openrouter':
            $key   = getSetting($pdo, 'openrouter_api_key', '');
            $model = getSetting($pdo, 'openrouter_model', 'google/gemini-2.0-flash-exp:free');
            if (!$key) return null;
            return makeOpenAICompatVisionHandle(
                'https://openrouter.ai/api/v1/chat/completions',
                ['Authorization: Bearer '.$key, 'Content-Type: application/json',
                 'HTTP-Referer: https://caltrack.app', 'X-Title: CalTrack'],
                $model, $prompt, $imageData, $mimeType
            );

        case 'groq':
            $key   = getSetting($pdo, 'groq_api_key', '');
            $model = getSetting($pdo, 'groq_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
            if (!$key) return null;
            return makeOpenAICompatVisionHandle(
                'https://api.groq.com/openai/v1/chat/completions',
                ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
                $model, $prompt, $imageData, $mimeType
            );

        default:
            return null;
    }
}

function makeOpenAICompatVisionHandle(string $url, array $headers, string $model, string $prompt, string $imageData, string $mimeType): array
{
    $payload = ['model'=>$model,'max_tokens'=>500,'temperature'=>0.1,'messages'=>[[
        'role'    => 'user',
        'content' => [
            ['type'=>'text','text'=>$prompt],
            ['type'=>'image_url','image_url'=>['url'=>"data:{$mimeType};base64,{$imageData}",'detail'=>'low']],
        ],
    ]]];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
    return ['ch'=>$ch, 'type'=>'openai_compat'];
}

function buildTextCurlHandle(string $provider, $pdo, string $systemMsg, string $userMsg): ?array
{
    $messages = [['role'=>'system','content'=>$systemMsg], ['role'=>'user','content'=>$userMsg]];

    switch ($provider) {
        case 'openai':
            $key   = getSetting($pdo, 'openai_api_key', '');
            $model = getSetting($pdo, 'openai_model', 'gpt-4o');
            if (!$key) return null;
            return makeOpenAICompatTextHandle(
                'https://api.openai.com/v1/chat/completions',
                ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
                $model, $messages
            );

        case 'anthropic':
            $key   = getSetting($pdo, 'anthropic_api_key', '');
            $model = getSetting($pdo, 'anthropic_model', 'claude-sonnet-4-6');
            if (!$key) return null;
            $payload = ['model'=>$model,'max_tokens'=>500,'system'=>$systemMsg,'messages'=>[['role'=>'user','content'=>$userMsg]]];
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
                CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$key,'anthropic-version: 2023-06-01'],
                CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
            return ['ch'=>$ch, 'type'=>'anthropic'];

        case 'openrouter':
            $key   = getSetting($pdo, 'openrouter_api_key', '');
            $model = getSetting($pdo, 'openrouter_model', 'google/gemini-2.0-flash-exp:free');
            if (!$key) return null;
            return makeOpenAICompatTextHandle(
                'https://openrouter.ai/api/v1/chat/completions',
                ['Authorization: Bearer '.$key, 'Content-Type: application/json',
                 'HTTP-Referer: https://caltrack.app', 'X-Title: CalTrack'],
                $model, $messages
            );

        case 'groq':
            $key   = getSetting($pdo, 'groq_api_key', '');
            $model = getSetting($pdo, 'groq_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
            if (!$key) return null;
            return makeOpenAICompatTextHandle(
                'https://api.groq.com/openai/v1/chat/completions',
                ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
                $model, $messages
            );

        default:
            return null;
    }
}

function makeOpenAICompatTextHandle(string $url, array $headers, string $model, array $messages): array
{
    $payload = ['model'=>$model,'messages'=>$messages,'max_tokens'=>400,'temperature'=>0.1];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
    return ['ch'=>$ch, 'type'=>'openai_compat'];
}


// ═══════════════════ RESPONSE PARSER ════════════════════════════════════════

function parseProviderResponse(string $provider, string $response, int $httpCode, string $mode): array
{
    if ($httpCode === 429) {
        return ['success'=>false, 'message'=>'تجاوز حد الطلبات - جاري المحاولة مع مزود آخر'];
    }
    $data = json_decode($response, true);
    if ($provider === 'anthropic') {
        if ($httpCode !== 200 || empty($data['content'][0]['text']))
            return ['success'=>false, 'message'=>$data['error']['message'] ?? "فشل {$provider}"];
        return parseJsonResult($data['content'][0]['text']);
    }
    // OpenAI-compatible (openai / openrouter / groq)
    if ($httpCode !== 200 || empty($data['choices'][0]['message']['content']))
        return ['success'=>false, 'message'=>$data['error']['message'] ?? "فشل {$provider}"];
    $result = parseJsonResult($data['choices'][0]['message']['content']);
    if ($result['success']) $result['model_used'] = $data['model'] ?? '';
    return $result;
}


// ═══════════════════ GEMINI (built-in model fallback) ════════════════════════

function callGeminiVision($pdo, string $imageData, string $mimeType, string $prompt): array
{
    $apiKey       = getSetting($pdo, 'gemini_api_key', '');
    $primaryModel = getSetting($pdo, 'gemini_model', 'gemini-1.5-flash');
    if (!$apiKey) return ['success'=>false, 'message'=>'لم يتم إعداد مفتاح Gemini API', 'provider'=>'gemini'];

    $allModels = ['gemini-2.0-flash','gemini-2.0-flash-lite','gemini-2.5-flash-preview-04-17',
                  'gemini-1.5-pro','gemini-1.5-flash-8b','gemini-1.5-flash-latest'];
    $models = array_merge([$primaryModel], array_values(array_filter($allModels, fn($m) => $m !== $primaryModel)));

    $payload = [
        'contents' => [['parts' => [['text'=>$prompt], ['inline_data'=>['mime_type'=>$mimeType,'data'=>$imageData]]]]],
        'generationConfig' => ['temperature'=>0.1,'maxOutputTokens'=>500],
    ];

    $lastError = 'فشلت جميع نماذج Gemini';
    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr   = curl_error($ch);
        curl_close($ch);

        if ($curlErr) { $lastError = 'خطأ في الاتصال: '.$curlErr; continue; }
        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            $lastError = $data['error']['message'] ?? "فشل النموذج {$model}";
            continue;
        }
        $result = parseJsonResult($data['candidates'][0]['content']['parts'][0]['text']);
        if (!$result['success']) { $lastError = $result['message']; continue; }
        $result['model_used'] = $model;
        $result['provider']   = 'gemini';
        return $result;
    }
    return ['success'=>false, 'message'=>$lastError, 'provider'=>'gemini'];
}

function callGeminiText($pdo, string $systemMsg, string $userMsg): array
{
    $apiKey = getSetting($pdo, 'gemini_api_key', '');
    $model  = getSetting($pdo, 'gemini_model', 'gemini-1.5-flash');
    if (!$apiKey) return ['success'=>false, 'message'=>'لم يتم إعداد مفتاح Gemini API', 'provider'=>'gemini'];

    $payload = [
        'contents' => [['parts' => [['text' => $systemMsg."\n\nالمستخدم: ".$userMsg]]]],
        'generationConfig' => ['temperature'=>0.1,'maxOutputTokens'=>400],
    ];
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text']))
        return ['success'=>false, 'message'=>$data['error']['message']??'فشل Gemini', 'provider'=>'gemini'];
    $result = parseJsonResult($data['candidates'][0]['content']['parts'][0]['text']);
    if ($result['success']) { $result['model_used'] = $model; $result['provider'] = 'gemini'; }
    return $result;
}


// ═══════════════════ HELPERS ═════════════════════════════════════════════════

function isProviderConfigured(string $provider, $pdo): bool
{
    static $cache = [];
    if (isset($cache[$provider])) return $cache[$provider];
    $keyMap = [
        'gemini'     => 'gemini_api_key',
        'openai'     => 'openai_api_key',
        'anthropic'  => 'anthropic_api_key',
        'openrouter' => 'openrouter_api_key',
        'groq'       => 'groq_api_key',
    ];
    $dbKey = $keyMap[$provider] ?? '';
    $cache[$provider] = $dbKey && strlen(getSetting($pdo, $dbKey, '')) > 4;
    return $cache[$provider];
}

function parseJsonResult(string $text): array
{
    $text   = preg_replace('/```json\s*|\s*```/', '', trim($text));
    $result = json_decode($text, true);
    if (!$result || isset($result['error']))
        return ['success'=>false, 'message'=>$result['error'] ?? 'فشل تحليل الاستجابة'];
    return ['success'=>true, 'data'=>$result];
}
