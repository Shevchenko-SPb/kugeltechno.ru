<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Функция для отправки JSON ответа
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем, что есть активная сессия импорта
if (!isset($_SESSION['import_session_id']) || !isset($_GET['session_id']) || $_SESSION['import_session_id'] !== $_GET['session_id']) {
    sendResponse(false, 'Неверная сессия импорта');
}

// Получаем данные из сессии
$sessionId = $_SESSION['import_session_id'];
$progressFile = dirname(__DIR__) . '/temp/progress_' . $sessionId . '.json';

// Проверяем существование файла прогресса
if (!file_exists($progressFile)) {
    sendResponse(false, 'Файл прогресса не найден');
}

// Загружаем данные о прогрессе
$progressData = json_decode(file_get_contents($progressFile), true);
if (!$progressData) {
    sendResponse(false, 'Ошибка при загрузке данных о прогрессе');
}

sendResponse(true, 'Данные о прогрессе получены', $progressData);
?>