<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Получаем userId из POST запроса
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Авторизация в битриксе истекла'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = intval($_POST['user_id']);

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
if (!isset($_SESSION['import_session_id']) || !isset($_POST['session_id']) || $_SESSION['import_session_id'] !== $_POST['session_id']) {
    sendResponse(false, 'Неверная сессия импорта');
}

// Получаем данные из сессии
$sessionId = $_SESSION['import_session_id'];

// Удаляем файлы сессии
$tempDir = dirname(__DIR__) . '/temp';
$companiesFile = $tempDir . '/companies_' . $sessionId . '.json';
$progressFile = $tempDir . '/progress_' . $sessionId . '.json';

if (file_exists($companiesFile)) {
    unlink($companiesFile);
}

if (file_exists($progressFile)) {
    unlink($progressFile);
}

// Очищаем сессию
unset($_SESSION['import_session_id']);

sendResponse(true, 'Загрузка прервана');
?>
