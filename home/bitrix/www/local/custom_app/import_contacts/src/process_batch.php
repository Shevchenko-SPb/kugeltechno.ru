<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

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
$contactsFile = dirname(__DIR__) . '/temp/contacts_' . $sessionId . '.json';
$progressFile = dirname(__DIR__) . '/temp/progress_' . $sessionId . '.json';

// Проверяем существование файлов
if (!file_exists($contactsFile) || !file_exists($progressFile)) {
    sendResponse(false, 'Файлы сессии не найдены');
}

// Загружаем данные о прогрессе
$progressData = json_decode(file_get_contents($progressFile), true);
if (!$progressData) {
    sendResponse(false, 'Ошибка при загрузке данных о прогрессе');
}

// Загружаем контакты
$allContacts = json_decode(file_get_contents($contactsFile), true);
if (!$allContacts) {
    sendResponse(false, 'Ошибка при загрузке контактов');
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

// Получаем контакты для текущего батча
$startIndex = $currentBatch * $batchSize;
$endIndex = min($startIndex + $batchSize, count($allContacts));
$batchContacts = array_slice($allContacts, $startIndex, $batchSize);

try {
    // Обрабатываем батч через BitrixController
    $result = BitrixController::actionUploadContacts($batchContacts);
    
    // Обновляем статистику
    $progressData['processed_contacts'] += count($batchContacts);
    $progressData['contacts_updated_count'] += $result['contacts_updated_count'];
    $progressData['contacts_upload_count'] += $result['contacts_upload_count'];
    $progressData['companies_upload_count'] += $result['companies_upload_count'];
    $progressData['contacts_upload_error_count'] += $result['contacts_upload_error_count'];
    
    // Добавляем ошибки, если есть
    if (!empty($result['contacts_upload_error_data'])) {
        $progressData['contacts_upload_error_data'] = array_merge(
            $progressData['contacts_upload_error_data'],
            $result['contacts_upload_error_data']
        );
    }
    
    // Переходим к следующему батчу
    $progressData['current_batch']++;
    $progressData['last_update'] = date('Y-m-d H:i:s');
    
    // Вычисляем процент выполнения
    $progressData['progress_percent'] = round(($progressData['processed_contacts'] / $progressData['total_contacts']) * 100, 2);
    
    // Проверяем, завершен ли импорт
    if ($progressData['current_batch'] >= $totalBatches) {
        $progressData['completed'] = true;
        $progressData['end_time'] = date('Y-m-d H:i:s');
        
        // Очищаем старые файлы при завершении импорта
        cleanupOldFiles(dirname(__DIR__) . '/temp');
        
        // Сохраняем ошибки в файл, если есть
        if (!empty($progressData['contacts_upload_error_data'])) {
            $errorFile = dirname(__DIR__) . '/temp/errors_' . $sessionId . '.json';
            file_put_contents($errorFile, json_encode($progressData['contacts_upload_error_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $progressData['error_file'] = 'temp/errors_' . $sessionId . '.json';
        }
    }
    
    // Сохраняем обновленный прогресс
    file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));
    
    sendResponse(true, 'Батч обработан успешно', $progressData);
    
} catch (Exception $e) {
    // В случае ошибки обновляем счетчик ошибок
    $progressData['contacts_upload_error_count'] += count($batchContacts);
    $progressData['contacts_upload_error_data'][] = [
        'batch' => $currentBatch,
        'error' => $e->getMessage(),
        'contacts_count' => count($batchContacts),
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Переходим к следующему батчу даже при ошибке
    $progressData['current_batch']++;
    $progressData['processed_contacts'] += count($batchContacts);
    $progressData['last_update'] = date('Y-m-d H:i:s');
    $progressData['progress_percent'] = round(($progressData['processed_contacts'] / $progressData['total_contacts']) * 100, 2);
    
    // Проверяем, завершен ли импорт
    if ($progressData['current_batch'] >= $totalBatches) {
        $progressData['completed'] = true;
        $progressData['end_time'] = date('Y-m-d H:i:s');
    }
    
    // Сохраняем обновленный прогресс
    file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));
    
    sendResponse(false, 'Ошибка при обработке батча: ' . $e->getMessage(), $progressData);
}

/**
 * Удаляет старые временные файлы (старше 24 часов)
 * Файлы с ошибками (.xlsx) удаляются отдельно с большим временем жизни
 */
function cleanupOldFiles($tempDir) {
    if (!is_dir($tempDir)) {
        return;
    }
    
    // Удаляем только служебные файлы, файлы с ошибками оставляем
    $patterns = [
        'contacts_*.json',
        'progress_*.json',
        'errors_*.json'
    ];
    
    $currentTime = time();
    $maxAge = 86400; // 24 часа в секундах
    
    foreach ($patterns as $pattern) {
        $files = glob($tempDir . '/' . $pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = filemtime($file);
                if (($currentTime - $fileTime) > $maxAge) {
                    unlink($file);
                }
            }
        }
    }
    
    // Отдельно очищаем файлы с ошибками (с большим временем жизни)
    cleanupOldErrorFiles($tempDir);
}

/**
 * Удаляет старые файлы с ошибками (старше 7 дней)
 */
function cleanupOldErrorFiles($tempDir) {
    if (!is_dir($tempDir)) {
        return;
    }
    
    $files = glob($tempDir . '/import_errors_*.xlsx');
    $currentTime = time();
    $maxAge = 604800; // 7 дней в секундах
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileTime = filemtime($file);
            if (($currentTime - $fileTime) > $maxAge) {
                unlink($file);
            }
        }
    }
}
?>