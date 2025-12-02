<?php

// Скрипт приёма формы и отправки заявки в Telegram

// Включаем вывод ошибок в режиме разработки (можно отключить на проде)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Проверяем метод
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Забираем данные из формы
$name  = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$msg   = isset($_POST['msg']) ? trim($_POST['msg']) : '';

if ($name === '' && $phone === '' && $msg === '') {
    http_response_code(400);
    echo 'Нет данных формы';
    exit;
}

// Telegram
$botToken = '8527238121:AAE76sxyk_r933arkYLykrfWIgoM186M_rE'; // токен бота
$chatId   = '358932815'; // ID чата, при необходимости поменяй на свой

$telegramMessage =
    "Новая заявка с лендинга Энтех Групп" . "\n\n" .
    "Имя: " . ($name !== '' ? $name : '—') . "\n" .
    "Телефон: " . ($phone !== '' ? $phone : '—') . "\n" .
    "Комментарий: " . ($msg !== '' ? $msg : '—');

$url  = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
$data = [
    'chat_id' => $chatId,
    'text'    => $telegramMessage,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
// На некоторых хостингах могут быть проблемы с SSL-сертификатами
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false || $curlError) {
    http_response_code(500);
    // Для отладки можно смотреть лог send.log на сервере
    @file_put_contents(
        __DIR__ . '/send.log',
        date('Y-m-d H:i:s') . " | CURL error: " . $curlError . PHP_EOL,
        FILE_APPEND
    );
    echo 'Ошибка отправки в Telegram';
} elseif ($httpCode >= 400) {
    http_response_code(500);
    @file_put_contents(
        __DIR__ . '/send.log',
        date('Y-m-d H:i:s') . " | HTTP error code from Telegram API: " . $httpCode . " | response: " . $result . PHP_EOL,
        FILE_APPEND
    );
    echo 'Ошибка отправки в Telegram';
} else {
    // Успешно
    echo 'ok';
}


