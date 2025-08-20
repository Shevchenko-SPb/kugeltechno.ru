<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Получаем userId из POST запроса
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Авторизация в битриксе истекла. Обновите страницу'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = intval($_POST['user_id']);

// Инициализация Битрикса и авторизация
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Подключаем AuthController и выполняем авторизацию
require_once dirname(__DIR__) . '/lib/AuthController.php';
AuthController::login($userId);

// Подключаем автолоадер для всех классов из lib
require_once dirname(__DIR__) . '/autoloader.php';

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

try {
    // Получаем параметр обновления существующих компаний
    $updateExistingCompanies = isset($_POST['update_existing_companies']) ? $_POST['update_existing_companies'] === '1' : false;
    
    // Получаем фильтры из POST запроса
    $filters = [];
    if (isset($_POST['filters']) && is_string($_POST['filters'])) {
        $filters = json_decode($_POST['filters'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $filters = [];
        }
    }
    
    // Получаем все ID компаний с учетом фильтров
    $arCompaniesIds = BitrixController::getAllCompaniesIds(!$updateExistingCompanies, $filters);
    
    // Проверяем на ошибки
    if (isset($arCompaniesIds['alert_error'])) {
        sendResponse(false, $arCompaniesIds['alert_error']);
    }
    
    if (empty($arCompaniesIds)) {
        sendResponse(false, 'Не найдено компаний для обработки');
    }
    
    // Генерируем уникальный ID сессии
    $sessionId = uniqid('revenue_', true);
    
    // Проверяем существование директории temp
    $tempDir = dirname(__DIR__) . '/temp';
    if (!is_dir($tempDir)) {
        if (!mkdir($tempDir, 0755, true)) {
            sendResponse(false, 'Не удалось создать директорию temp');
        }
    }
    
    // Проверяем права на запись
    if (!is_writable($tempDir)) {
        sendResponse(false, 'Нет прав на запись в директорию temp');
    }
    
    // Сохраняем ID компаний в файл
    $companiesFile = $tempDir . '/companies_' . $sessionId . '.json';
    $companiesResult = file_put_contents($companiesFile, json_encode($arCompaniesIds, JSON_UNESCAPED_UNICODE));
    if ($companiesResult === false) {
        sendResponse(false, 'Не удалось сохранить файл с ID компаний');
    }
    
    // Инициализируем данные о прогрессе
    $batchSize = 10; // Размер батча
    $totalCompanies = count($arCompaniesIds);
    $totalBatches = ceil($totalCompanies / $batchSize);
    
    $progressData = [
        'session_id' => $sessionId,
        'total_companies' => $totalCompanies,
        'processed_companies' => 0,
        'companies_updated_count' => 0,
        'companies_error_count' => 0,
        'batch_size' => $batchSize,
        'current_batch' => 0,
        'total_batches' => $totalBatches,
        'start_time' => date('Y-m-d H:i:s'),
        'completed' => false,
        'progress_percent' => 0,
        'balance' => 0,
        'today_request_count' => 0
    ];
    
    // Сохраняем данные о прогрессе
    $progressFile = $tempDir . '/progress_' . $sessionId . '.json';
    $progressResult = file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));
    if ($progressResult === false) {
        sendResponse(false, 'Не удалось сохранить файл прогресса');
    }
    
    // Сохраняем ID сессии в сессии PHP
    $_SESSION['import_session_id'] = $sessionId;
    
    sendResponse(true, 'Готово к загрузке оборотов компаний', [
        'ready_for_import' => true,
        'session_id' => $sessionId,
        'total_companies' => $totalCompanies,
        'total_batches' => $totalBatches
    ]);
    
} catch (Exception $e) {
    sendResponse(false, 'Ошибка при подготовке загрузки: ' . $e->getMessage());
}
?>
