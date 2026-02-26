<?php

// Скрипт приёма формы и отправки заявки в Telegram
// Защита от спама: honeypot, валидация, фильтр по ключевым словам и ссылкам

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$name   = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone  = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$msg    = isset($_POST['msg']) ? trim($_POST['msg']) : '';
$honey  = isset($_POST['company']) ? trim($_POST['company']) : ''; // honeypot

// Honeypot: если заполнено — бот, не отправляем (возвращаем ok, чтобы не выдавать себя)
if ($honey !== '') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

if ($name === '' && $phone === '' && $msg === '') {
    http_response_code(400);
    echo 'Нет данных формы';
    exit;
}

// Валидация имени: 2–100 символов, без ссылок
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    http_response_code(400);
    echo 'Проверьте поле «Имя».';
    exit;
}
if (preg_match('/https?:\/\//ui', $name) || preg_match('/<a\s/hui', $name)) {
    http_response_code(400);
    echo 'Некорректное имя.';
    exit;
}

// Валидация телефона: только цифры, 10–11 символов (российский формат)
$phoneClean = preg_replace('/\D/', '', $phone);
if (strlen($phoneClean) < 10 || strlen($phoneClean) > 11) {
    http_response_code(400);
    echo 'Проверьте номер телефона.';
    exit;
}

// Запрещённые слова в комментарии (спам, мошенничество, запрещённый контент)
$spamKeywords = [
    'child porn', 'childporn', 'preteen', 'underage', 'csam', 'loli nsfw',
    'cp for trade', 'cp trader', 'cp collection', 'cp folder', 'cp link',
    'cp channel', 'cp archive', 'cp content', 'teen under 18', 'minor sex',
    'schoolgirl sex', 'pthc', 'csam collection', 'drop cp', 'trade cp',
    'swap cp', 'hidden cp', 'tg cp', 'mega cp', 'new cp', 'young teen sex',
    'barely legal', 'sexvideo', 'sex video', 'child porn link',
    // крипто-мошенничество
    'withdraw 1.', 'withdraw 0.', 'SEQUENCE:', 'plu.sh', 'Achieved $79',
    // HTML-спам
    '<a href', 'href="http',
];

$msgLower = mb_strtolower($msg);
foreach ($spamKeywords as $kw) {
    if (mb_strpos($msgLower, mb_strtolower($kw)) !== false) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'ok'; // тихо отклоняем
        exit;
    }
}

// Слишком много ссылок в комментарии (SEO-спам)
$urlCount = preg_match_all('/https?:\/\/[^\s<>"\']+/ui', $msg) +
            preg_match_all('/<a\s[^>]*href/ui', $msg);
if ($urlCount > 2) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

// Слишком длинный комментарий (часто спам)
if (mb_strlen($msg) > 2000) {
    http_response_code(400);
    echo 'Комментарий слишком длинный.';
    exit;
}

// Telegram
$botToken = '7961060962:AAG4iC0jUn7ORTSwdM1ItIW6073hi5wNSOY';
$chatId   = '358932815';

$telegramMessage =
    "Новая заявка с лендинга Энтех Групп" . "\n\n" .
    "Имя: " . $name . "\n" .
    "Телефон: " . $phone . "\n" .
    "Комментарий: " . ($msg !== '' ? $msg : '—');

// Очищаем HTML из сообщения перед отправкой
$telegramMessage = strip_tags($telegramMessage);

$url  = "https://api.telegram.org/bot{$botToken}/sendMessage";
$data = [
    'chat_id' => $chatId,
    'text'    => $telegramMessage,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
curl_close($ch);

$resp = $result ? json_decode($result, true);
if ($result && isset($resp['ok']) && $resp['ok']) {
    echo 'ok';
} else {
    http_response_code(500);
    echo 'Ошибка отправки. Попробуйте позже.';
}
