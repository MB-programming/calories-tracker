<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$provider = getSetting($pdo, 'ai_provider', 'gemini');

// ── Shared prompt ────────────────────────────────────────────────────────────
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

// ── Text query mode (no image) ───────────────────────────────────────────────
$textQuery = trim($_POST['text_query'] ?? '');
if ($textQuery) {
    $systemMsg = 'أنت خبير تغذية. عندما يسألك المستخدم عن طعام أو يصفه، أعط القيم الغذائية بصيغة JSON فقط بدون أي نص إضافي: {"food_name":"...","calories":0,"protein":0,"carbs":0,"fat":0,"description":"...","confidence":"medium"}. إذا كان السؤال عاماً لا يتعلق بوجبة محددة أو وصف كمية، أعد {"error":"يرجى وصف الطعام أو الكمية بشكل أوضح"}.';
    $userMsg   = $textQuery;
    doTextAnalysis($provider, $pdo, $systemMsg, $userMsg);
    exit;
}

// ── Image mode ───────────────────────────────────────────────────────────────
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

// ── OpenAI GPT-4 Vision ──────────────────────────────────────────────────────
if ($provider === 'openai') {
    $apiKey = getSetting($pdo, 'openai_api_key', '');
    $model  = getSetting($pdo, 'openai_model', 'gpt-4o');
    if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد مفتاح OpenAI API']); exit; }

    $result = callOpenAICompatibleVision(
        'https://api.openai.com/v1/chat/completions',
        ['Authorization: Bearer '.$apiKey, 'Content-Type: application/json'],
        $model, $jsonPrompt, $imageData, $mimeType
    );
    echo json_encode($result); exit;
}

// ── Anthropic Claude Vision ──────────────────────────────────────────────────
if ($provider === 'anthropic') {
    $apiKey = getSetting($pdo, 'anthropic_api_key', '');
    $model  = getSetting($pdo, 'anthropic_model', 'claude-sonnet-4-6');
    if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد مفتاح Anthropic API']); exit; }

    $payload = [
        'model'      => $model,
        'max_tokens' => 500,
        'messages'   => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type'=>'base64','media_type'=>$mimeType,'data'=>$imageData]],
                ['type' => 'text', 'text' => $jsonPrompt],
            ],
        ]],
    ];
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01'],
        CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    $data = json_decode($response, true);
    if ($httpCode !== 200 || empty($data['content'][0]['text'])) {
        echo json_encode(['success'=>false,'message'=>$data['error']['message']??"فشل النموذج {$model}"]); exit;
    }
    $result = parseJsonResult($data['content'][0]['text']);
    echo json_encode($result); exit;
}

// ── OpenRouter (Free vision models) ─────────────────────────────────────────
if ($provider === 'openrouter') {
    $apiKey = getSetting($pdo, 'openrouter_api_key', '');
    $model  = getSetting($pdo, 'openrouter_model', 'google/gemini-2.0-flash-exp:free');
    if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد مفتاح OpenRouter API']); exit; }

    $result = callOpenAICompatibleVision(
        'https://openrouter.ai/api/v1/chat/completions',
        [
            'Authorization: Bearer '.$apiKey,
            'Content-Type: application/json',
            'HTTP-Referer: https://caltrack.app',
            'X-Title: CalTrack',
        ],
        $model, $jsonPrompt, $imageData, $mimeType
    );
    echo json_encode($result); exit;
}

// ── Groq (Free vision) ───────────────────────────────────────────────────────
if ($provider === 'groq') {
    $apiKey = getSetting($pdo, 'groq_api_key', '');
    $model  = getSetting($pdo, 'groq_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
    if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد مفتاح Groq API']); exit; }

    $result = callOpenAICompatibleVision(
        'https://api.groq.com/openai/v1/chat/completions',
        ['Authorization: Bearer '.$apiKey, 'Content-Type: application/json'],
        $model, $jsonPrompt, $imageData, $mimeType
    );
    echo json_encode($result); exit;
}

// ── Google Gemini (default) ──────────────────────────────────────────────────
$apiKey       = getSetting($pdo, 'gemini_api_key', '');
$primaryModel = getSetting($pdo, 'gemini_model', 'gemini-1.5-flash');
if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد مفتاح API']); exit; }

$fallbackModels = [
    'gemini-2.0-flash', 'gemini-2.0-flash-lite',
    'gemini-2.5-flash-preview-04-17', 'gemini-1.5-pro',
    'gemini-1.5-flash-8b', 'gemini-1.5-flash-latest',
];
$models = array_merge([$primaryModel],
    array_values(array_filter($fallbackModels, fn($m) => $m !== $primaryModel))
);

$payload = [
    'contents' => [['parts' => [
        ['text' => $jsonPrompt],
        ['inline_data' => ['mime_type'=>$mimeType,'data'=>$imageData]],
    ]]],
    'generationConfig' => ['temperature'=>0.1,'maxOutputTokens'=>500],
];

$lastError = 'فشلت جميع النماذج المتاحة';
foreach ($models as $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);

    if ($curlError) { $lastError = 'خطأ في الاتصال: '.$curlError; continue; }
    $data = json_decode($response, true);
    if ($httpCode !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text'])) {
        $lastError = $data['error']['message'] ?? "فشل النموذج {$model}"; continue;
    }
    $result = parseJsonResult($data['candidates'][0]['content']['parts'][0]['text']);
    if (!$result['success']) { $lastError = $result['message']; continue; }
    $result['model_used'] = $model;
    echo json_encode($result); exit;
}
echo json_encode(['success'=>false,'message'=>$lastError]);


// ═══════════════════ HELPERS ═══════════════════════════════════════════════

function callOpenAICompatibleVision(string $url, array $headers, string $model, string $prompt, string $imageData, string $mimeType): array
{
    $payload = [
        'model'       => $model,
        'messages'    => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$imageData}", 'detail' => 'low']],
            ],
        ]],
        'max_tokens'  => 500,
        'temperature' => 0.1,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>json_encode($payload), CURLOPT_TIMEOUT=>30]);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);

    if ($curlError) return ['success'=>false,'message'=>'خطأ في الاتصال: '.$curlError];
    $data = json_decode($response, true);
    if ($httpCode !== 200 || empty($data['choices'][0]['message']['content'])) {
        return ['success'=>false,'message'=>$data['error']['message']??"فشل النموذج {$model}"];
    }
    $result = parseJsonResult($data['choices'][0]['message']['content']);
    if ($result['success']) $result['model_used'] = $model;
    return $result;
}

function doTextAnalysis(string $provider, $pdo, string $systemMsg, string $userMsg): void
{
    $messages = [
        ['role'=>'system','content'=>$systemMsg],
        ['role'=>'user','content'=>$userMsg],
    ];

    if ($provider === 'groq') {
        $apiKey = getSetting($pdo,'groq_api_key','');
        $model  = getSetting($pdo,'groq_model','meta-llama/llama-4-scout-17b-16e-instruct');
        if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد Groq API']); return; }
        $result = callOpenAICompatibleText('https://api.groq.com/openai/v1/chat/completions',
            ['Authorization: Bearer '.$apiKey,'Content-Type: application/json'], $model, $messages);
    } elseif ($provider === 'openrouter') {
        $apiKey = getSetting($pdo,'openrouter_api_key','');
        $model  = getSetting($pdo,'openrouter_model','google/gemini-2.0-flash-exp:free');
        if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد OpenRouter API']); return; }
        $result = callOpenAICompatibleText('https://openrouter.ai/api/v1/chat/completions',
            ['Authorization: Bearer '.$apiKey,'Content-Type: application/json','HTTP-Referer: https://caltrack.app','X-Title: CalTrack'],
            $model, $messages);
    } elseif ($provider === 'openai') {
        $apiKey = getSetting($pdo,'openai_api_key','');
        $model  = getSetting($pdo,'openai_model','gpt-4o');
        if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد OpenAI API']); return; }
        $result = callOpenAICompatibleText('https://api.openai.com/v1/chat/completions',
            ['Authorization: Bearer '.$apiKey,'Content-Type: application/json'], $model, $messages);
    } elseif ($provider === 'anthropic') {
        $apiKey = getSetting($pdo,'anthropic_api_key','');
        $model  = getSetting($pdo,'anthropic_model','claude-sonnet-4-6');
        if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد Anthropic API']); return; }
        $payload = ['model'=>$model,'max_tokens'=>500,'system'=>$systemMsg,
            'messages'=>[['role'=>'user','content'=>$userMsg]]];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01'],
            CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>30]);
        $response = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        $data = json_decode($response,true);
        if ($code !== 200 || empty($data['content'][0]['text'])) {
            echo json_encode(['success'=>false,'message'=>$data['error']['message']??"فشل النموذج"]); return;
        }
        $result = parseJsonResult($data['content'][0]['text']);
        if ($result['success']) $result['model_used'] = $model;
        echo json_encode($result); return;
    } else {
        // Gemini text
        $apiKey = getSetting($pdo,'gemini_api_key','');
        $model  = getSetting($pdo,'gemini_model','gemini-1.5-flash');
        if (empty($apiKey)) { echo json_encode(['success'=>false,'message'=>'لم يتم إعداد Gemini API']); return; }
        $fullPrompt = $systemMsg."\n\nالمستخدم: ".$userMsg;
        $payload = ['contents'=>[['parts'=>[['text'=>$fullPrompt]]]],
            'generationConfig'=>['temperature'=>0.1,'maxOutputTokens'=>400]];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>30]);
        $response = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        $data = json_decode($response,true);
        if ($code !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            echo json_encode(['success'=>false,'message'=>$data['error']['message']??"فشل النموذج"]); return;
        }
        $result = parseJsonResult($data['candidates'][0]['content']['parts'][0]['text']);
        if ($result['success']) $result['model_used'] = $model;
        echo json_encode($result); return;
    }

    echo json_encode($result);
}

function callOpenAICompatibleText(string $url, array $headers, string $model, array $messages): array
{
    $payload = ['model'=>$model,'messages'=>$messages,'max_tokens'=>400,'temperature'=>0.1];
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>30]);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);

    if ($curlError) return ['success'=>false,'message'=>'خطأ في الاتصال: '.$curlError];
    $data = json_decode($response,true);
    if ($httpCode !== 200 || empty($data['choices'][0]['message']['content'])) {
        return ['success'=>false,'message'=>$data['error']['message']??"فشل النموذج {$model}"];
    }
    $result = parseJsonResult($data['choices'][0]['message']['content']);
    if ($result['success']) $result['model_used'] = $model;
    return $result;
}

function parseJsonResult(string $text): array
{
    $text   = preg_replace('/```json\s*|\s*```/', '', trim($text));
    $result = json_decode($text, true);
    if (!$result || isset($result['error'])) {
        return ['success'=>false,'message'=>$result['error']??'فشل تحليل الاستجابة'];
    }
    return ['success'=>true,'data'=>$result];
}
