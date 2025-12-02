<?php

// Простой скрипт приёма формы и отправки заявки в Telegram

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$name  = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$msg   = isset($_POST['msg']) ? trim($_POST['msg']) : '';

if ($name === '' && $phone === '' && $msg === '') {
    http_response_code(400);
    echo 'Нет данных формы';
    exit;
}

// Telegram — структура по аналогии с рабочим примером
$botToken = '7961060962:AAG4iC0jUn7ORTSwdM1ItIW6073hi5wNSOY'; // токен бота из примера
$chatId   = '358932815'; // твой chat ID

$telegramMessage =
    "Новая заявка с лендинга Энтех Групп" . "\n\n" .
    "Имя: " . $name . "\n" .
    "Телефон: " . $phone . "\n" .
    "Комментарий: " . ($msg !== '' ? $msg : '—');

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

$result = curl_exec($ch);
curl_close($ch);

if ($result) {
    // Для фронта важно вернуть "ok"
    echo 'ok';
} else {
    http_response_code(500);
    echo 'Ошибка отправки в Telegram';
}

?>



