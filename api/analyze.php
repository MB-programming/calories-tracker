<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$provider = getSetting($pdo, 'ai_provider', 'gemini');
$apiKey   = getSetting($pdo, 'gemini_api_key', '');
$model    = getSetting($pdo, 'gemini_model', 'gemini-1.5-flash');

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'لم يتم إعداد مفتاح API. يرجى التواصل مع المسؤول.']);
    exit;
}

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

$payload = [
    'contents' => [[
        'parts' => [
            ['text' => $prompt],
            ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]]
        ]
    ]],
    'generationConfig' => [
        'temperature'     => 0.1,
        'maxOutputTokens' => 500,
    ]
];

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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text'])) {
    $errMsg = $data['error']['message'] ?? 'خطأ في استجابة الذكاء الاصطناعي';
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$text = $data['candidates'][0]['content']['parts'][0]['text'];
$text = preg_replace('/```json\s*|\s*```/', '', trim($text));

$result = json_decode($text, true);

if (!$result || isset($result['error'])) {
    echo json_encode(['success' => false, 'message' => $result['error'] ?? 'فشل تحليل الصورة']);
    exit;
}

echo json_encode(['success' => true, 'data' => $result]);
