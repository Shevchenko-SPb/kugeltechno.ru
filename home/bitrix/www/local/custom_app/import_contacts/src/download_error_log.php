<?php
session_start();

// Получаем userId из GET запроса
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Авторизация в битриксе истекла. Обновите страницу'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = intval($_GET['user_id']);

// Инициализация Битрикса и авторизация
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Подключаем AuthController и выполняем авторизацию
require_once dirname(__DIR__) . '/lib/AuthController.php';
AuthController::login($userId);

// Подключаем автолоадер для всех классов из lib
require_once dirname(__DIR__) . '/autoloader.php';

// Функция для отправки JSON ответа
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
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

// Проверяем параметры
if (!isset($_GET['session_id'])) {
    sendResponse(false, 'Не указан ID сессии');
}

$sessionId = $_GET['session_id'];

// Проверяем файлы
$tempDir = dirname(__DIR__) . '/temp';
$progressFile = $tempDir . '/progress_' . $sessionId . '.json';

if (!file_exists($progressFile)) {
    sendResponse(false, 'Файл прогресса не найден');
}

// Загружаем данные о прогрессе
$progressData = json_decode(file_get_contents($progressFile), true);
if (!$progressData) {
    sendResponse(false, 'Ошибка при загрузке данных о прогрессе');
}

// Проверяем, есть ли лог ошибок
if (empty($progressData['contacts_upload_error_log'])) {
    sendResponse(false, 'Нет лога ошибок для скачивания');
}

// Создаем текстовый файл с логом ошибок
try {
    $filename = 'import_error_log_' . $sessionId . '.txt';
    $filepath = $tempDir . '/' . $filename;
    
    // Удаляем старые файлы с логами перед созданием нового
    cleanupOldLogFiles($tempDir);
    
    // Формируем содержимое лога
    $logContent = "Лог ошибок импорта контактов\n";
    $logContent .= "Дата: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "ID сессии: " . $sessionId . "\n";
    $logContent .= str_repeat("=", 50) . "\n\n";
    
    // Добавляем каждую ошибку из лога
    foreach ($progressData['contacts_upload_error_log'] as $index => $errorGroup) {
        $logContent .= "Ошибка #" . ($index + 1) . ":\n";
        
        if (is_array($errorGroup)) {
            foreach ($errorGroup as $errorKey => $errorMessages) {
                $logContent .= "  " . $errorKey . ":\n";
                
                if (is_array($errorMessages)) {
                    foreach ($errorMessages as $message) {
                        $logContent .= "    - " . $message . "\n";
                    }
                } else {
                    $logContent .= "    - " . $errorMessages . "\n";
                }
            }
        } else {
            $logContent .= "  " . $errorGroup . "\n";
        }
        
        $logContent .= "\n" . str_repeat("-", 30) . "\n\n";
    }
    
    // Записываем содержимое в файл
    if (file_put_contents($filepath, $logContent) === false) {
        throw new Exception('Не удалось создать файл');
    }
    
    // Отправляем файл для скачивания
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    readfile($filepath);
    
    // НЕ удаляем файл сразу - он будет удален автоматически через время функцией cleanupOldLogFiles
    
} catch (Exception $e) {
    sendResponse(false, 'Ошибка при создании файла: ' . $e->getMessage());
}

/**
 * Удаляет старые файлы с логами (старше 7 дней)
 */
function cleanupOldLogFiles($tempDir) {
    if (!is_dir($tempDir)) {
        return;
    }
    
    $files = glob($tempDir . '/import_error_log_*.txt');
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