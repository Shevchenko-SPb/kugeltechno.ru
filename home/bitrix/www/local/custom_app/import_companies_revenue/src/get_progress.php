<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Получаем userId из GET запроса
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Авторизация в битриксе истекла. Обновите страницу'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = intval($_GET['user_id']);

// Инициализация Битрикса и авторизация
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Подключаем AuthController и выполняем авторизацию
require_once dirname(__DIR__) . '/lib/AuthController.php';
AuthController::login($userId);

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
$tempDir = dirname(__DIR__) . '/temp';
$progressFile = $tempDir . '/progress_' . $sessionId . '.json';

// Проверяем существование файла прогресса
if (!file_exists($progressFile)) {
    // Добавляем отладочную информацию
    $debugInfo = [
        'session_id' => $sessionId,
        'temp_dir' => $tempDir,
        'progress_file' => $progressFile,
        'temp_dir_exists' => is_dir($tempDir),
        'temp_dir_writable' => is_writable($tempDir),
        'session_import_id' => $_SESSION['import_session_id'] ?? 'not_set'
    ];
    sendResponse(false, 'Файл прогресса не найден. Отладочная информация: ' . json_encode($debugInfo));
}

// Загружаем данные о прогрессе
$progressData = json_decode(file_get_contents($progressFile), true);
if (!$progressData) {
    sendResponse(false, 'Ошибка при загрузке данных о прогрессе');
}

// Вычисляем процент выполнения
if ($progressData['total_companies'] > 0) {
    $progressData['progress_percent'] = round(($progressData['processed_companies'] / $progressData['total_companies']) * 100);
} else {
    $progressData['progress_percent'] = 0;
}

sendResponse(true, 'Данные о прогрессе получены', $progressData);
?>
