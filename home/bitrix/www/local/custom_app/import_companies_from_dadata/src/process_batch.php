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

// Проверяем, что есть активная сессия импорта
if (!isset($_SESSION['import_session_id']) || !isset($_POST['session_id']) || $_SESSION['import_session_id'] !== $_POST['session_id']) {
    sendResponse(false, 'Неверная сессия импорта');
}

// Получаем данные из сессии
$sessionId = $_SESSION['import_session_id'];
$tempDir = dirname(__DIR__) . '/temp';
$companiesFile = $tempDir . '/companies_' . $sessionId . '.json';
$progressFile = $tempDir . '/progress_' . $sessionId . '.json';

// Проверяем существование файлов
if (!file_exists($companiesFile) || !file_exists($progressFile)) {
    sendResponse(false, 'Файлы сессии не найдены');
}

// Загружаем данные о прогрессе
$progressData = json_decode(file_get_contents($progressFile), true);
if (!$progressData) {
    sendResponse(false, 'Ошибка при загрузке данных о прогрессе');
}

// Загружаем ID компаний
$allCompaniesIds = json_decode(file_get_contents($companiesFile), true);
if (!$allCompaniesIds) {
    sendResponse(false, 'Ошибка при загрузке ID компаний');
}

// Проверяем, не завершен ли уже импорт
if ($progressData['completed']) {
    sendResponse(true, 'Импорт уже завершен', $progressData);
}

// Определяем текущий батч
$batchSize = $progressData['batch_size'];
$currentBatch = $progressData['current_batch'];
$totalBatches = $progressData['total_batches'];

// Проверяем, есть ли еще батчи для обработки
if ($currentBatch >= $totalBatches) {
    $progressData['completed'] = true;
    $progressData['end_time'] = date('Y-m-d H:i:s');
    file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));
    
    sendResponse(true, 'Импорт завершен', $progressData);
}

// Получаем ID компаний для текущего батча
$startIndex = $currentBatch * $batchSize;
$endIndex = min($startIndex + $batchSize, count($allCompaniesIds));
$batchCompanyIds = array_slice($allCompaniesIds, $startIndex, $batchSize);

try {
    // Обрабатываем батч через BitrixController
    $result = BitrixController::actionUploadCompaniesRevenue($batchCompanyIds);
    
    // Обновляем статистику
    $progressData['processed_companies'] += count($batchCompanyIds);
    // Адаптация к новому формату ответа Company::uploadRevenue
    // Если присутствует старое поле, используем его, иначе суммируем новые поля
    $updatedThisBatch = 0;
    if (isset($result['companies_updated_revenue'])) {
        $updatedThisBatch = (int)$result['companies_updated_revenue'];
    } else {
        $updatedThisBatch = (int)($result['companies_add'] ?? 0) + (int)($result['companies_update'] ?? 0);
    }
    $progressData['companies_updated_count'] += $updatedThisBatch;
    $progressData['companies_error_count'] += isset($result['errors']) ? (int)$result['errors'] : 0;
    // Новые детальные счётчики
    if (isset($result['companies_add'])) {
        $progressData['companies_add_count'] += (int)$result['companies_add'];
    }
    if (isset($result['companies_update'])) {
        $progressData['companies_update_count'] += (int)$result['companies_update'];
    }
    if (isset($result['companies_check'])) {
        $progressData['companies_check_count'] += (int)$result['companies_check'];
    }
    if (isset($result['holdings_add'])) {
        $progressData['holdings_add_count'] += (int)$result['holdings_add'];
    }
    
    // Обновляем данные о балансе и запросах (берем последние значения из результата)
    if (isset($result['balance'])) {
        $progressData['balance'] = $result['balance'];
    }
    if (isset($result['today_request_count'])) {
        $progressData['today_request_count'] = $result['today_request_count'];
    }
    
    // Переходим к следующему батчу
    $progressData['current_batch']++;
    
    // Проверяем, завершен ли импорт
    if ($progressData['current_batch'] >= $totalBatches) {
        $progressData['completed'] = true;
        $progressData['end_time'] = date('Y-m-d H:i:s');
    }
    
    // Вычисляем процент выполнения
    if ($progressData['total_companies'] > 0) {
        $progressData['progress_percent'] = round(($progressData['processed_companies'] / $progressData['total_companies']) * 100);
    } else {
        $progressData['progress_percent'] = 0;
    }
    
    // Сохраняем обновленные данные о прогрессе
    file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));
    
    sendResponse(true, 'Батч обработан успешно', $progressData);
    
} catch (Exception $e) {
    // В случае ошибки увеличиваем счетчик ошибок
    $progressData['companies_error_count'] += count($batchCompanyIds);
    $progressData['processed_companies'] += count($batchCompanyIds);
    $progressData['current_batch']++;
    
    // Вычисляем процент выполнения
    if ($progressData['total_companies'] > 0) {
        $progressData['progress_percent'] = round(($progressData['processed_companies'] / $progressData['total_companies']) * 100);
    } else {
        $progressData['progress_percent'] = 0;
    }
    
    // Сохраняем обновленные данные о прогрессе
    file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));
    
    sendResponse(false, 'Ошибка при обработке батча: ' . $e->getMessage(), $progressData);
}
?>
