<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$provider = getSetting($pdo, 'ai_provider', 'gemini');

// ── Image input ──────────────────────────────────────────────────────────────
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
    echo json_encode(['success' => false, 'message' => 'لم يتم إرسال صورة']);
    exit;
}

$prompt = <<<PROMPT
أنت خبير تغذية. حلل الطعام في هذه الصورة وأعط تقديراً للقيم الغذائية.
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
إذا لم تتمكن من التعرف على الطعام، أعد: {"error": "لا يمكن التعرف على الطعام في الصورة"}
PROMPT;

// ── OpenAI GPT-4 Vision ──────────────────────────────────────────────────────
if ($provider === 'openai') {
    $apiKey = getSetting($pdo, 'openai_api_key', '');
    $model  = getSetting($pdo, 'openai_model', 'gpt-4o');

    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'message' => 'لم يتم إعداد مفتاح OpenAI API. يرجى التواصل مع المسؤول.']);
        exit;
    }

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

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال: ' . $curlError]);
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || empty($data['choices'][0]['message']['content'])) {
        echo json_encode(['success' => false, 'message' => $data['error']['message'] ?? "فشل النموذج {$model}"]);
        exit;
    }

    $text   = preg_replace('/```json\s*|\s*```/', '', trim($data['choices'][0]['message']['content']));
    $result = json_decode($text, true);

    if (!$result || isset($result['error'])) {
        echo json_encode(['success' => false, 'message' => $result['error'] ?? 'فشل تحليل الصورة']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $result, 'model_used' => $model]);
    exit;
}

// ── Anthropic Claude Vision ──────────────────────────────────────────────────
if ($provider === 'anthropic') {
    $apiKey = getSetting($pdo, 'anthropic_api_key', '');
    $model  = getSetting($pdo, 'anthropic_model', 'claude-sonnet-4-6');

    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'message' => 'لم يتم إعداد مفتاح Anthropic API. يرجى التواصل مع المسؤول.']);
        exit;
    }

    $payload = [
        'model'      => $model,
        'max_tokens' => 500,
        'messages'   => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $imageData]],
                ['type' => 'text', 'text' => $prompt],
            ],
        ]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال: ' . $curlError]);
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || empty($data['content'][0]['text'])) {
        echo json_encode(['success' => false, 'message' => $data['error']['message'] ?? "فشل النموذج {$model}"]);
        exit;
    }

    $text   = preg_replace('/```json\s*|\s*```/', '', trim($data['content'][0]['text']));
    $result = json_decode($text, true);

    if (!$result || isset($result['error'])) {
        echo json_encode(['success' => false, 'message' => $result['error'] ?? 'فشل تحليل الصورة']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $result, 'model_used' => $model]);
    exit;
}

// ── Google Gemini (default) ──────────────────────────────────────────────────
$apiKey       = getSetting($pdo, 'gemini_api_key', '');
$primaryModel = getSetting($pdo, 'gemini_model', 'gemini-1.5-flash');

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'لم يتم إعداد مفتاح API. يرجى التواصل مع المسؤول.']);
    exit;
}

$fallbackModels = [
    'gemini-2.0-flash',
    'gemini-2.0-flash-lite',
    'gemini-2.5-flash-preview-04-17',
    'gemini-1.5-pro',
    'gemini-1.5-flash-8b',
    'gemini-1.5-flash-latest',
];

$models = array_merge(
    [$primaryModel],
    array_values(array_filter($fallbackModels, fn($m) => $m !== $primaryModel))
);

$payload = [
    'contents' => [[
        'parts' => [
            ['text' => $prompt],
            ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]],
        ],
    ]],
    'generationConfig' => [
        'temperature'     => 0.1,
        'maxOutputTokens' => 500,
    ],
];

$lastError = 'فشلت جميع النماذج المتاحة';

foreach ($models as $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $lastError = 'خطأ في الاتصال: ' . $curlError;
        continue;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text'])) {
        $lastError = $data['error']['message'] ?? "فشل النموذج {$model}";
        continue;
    }

    $text   = preg_replace('/```json\s*|\s*```/', '', trim($data['candidates'][0]['content']['parts'][0]['text']));
    $result = json_decode($text, true);

    if (!$result || isset($result['error'])) {
        $lastError = $result['error'] ?? 'فشل تحليل الصورة';
        continue;
    }

    echo json_encode(['success' => true, 'data' => $result, 'model_used' => $model]);
    exit;
}

echo json_encode(['success' => false, 'message' => $lastError]);
