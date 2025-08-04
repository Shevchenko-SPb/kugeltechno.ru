<?php
session_start();

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
$contactsFile = $tempDir . '/contacts_' . $sessionId . '.json';

if (!file_exists($progressFile)) {
    sendResponse(false, 'Файл прогресса не найден');
}

if (!file_exists($contactsFile)) {
    sendResponse(false, 'Файл контактов не найден');
}

// Загружаем данные о прогрессе
$progressData = json_decode(file_get_contents($progressFile), true);
if (!$progressData) {
    sendResponse(false, 'Ошибка при загрузке данных о прогрессе');
}

// Проверяем, есть ли ошибки
if (empty($progressData['contacts_upload_error_data'])) {
    sendResponse(false, 'Нет ошибок для скачивания');
}

// Загружаем исходные контакты для получения заголовков
$allContacts = json_decode(file_get_contents($contactsFile), true);
if (!$allContacts) {
    sendResponse(false, 'Ошибка при загрузке контактов');
}

// Получаем заголовки из исходного файла (первая строка до unset)
// Нам нужно восстановить заголовки из исходного файла
$originalFile = $_SESSION['original_file_data'] ?? null;
$headers = [];

if ($originalFile && isset($originalFile['headers'])) {
    $headers = $originalFile['headers'];
} else {
    // Если заголовки не сохранены, создаем стандартные
    $headers = [
        'Название компании',
        'Телефон',
        'Факс',
        'Сайт',
        'Адрес',
        'ИНН',
        'КПП',
        'ОГРН',
        'Банк',
        'БИК',
        'Email',
        'Дополнительная информация'
    ];
}

// Создаем данные для Excel файла
$excelData = [];

// Сначала добавляем заголовки
$excelData[] = $headers;

// Затем добавляем все данные об ошибках (они уже содержат строки контактов)
foreach ($progressData['contacts_upload_error_data'] as $errorRow) {
    if (is_array($errorRow)) {
        $excelData[] = $errorRow;
    }
}

// Создаем Excel файл
try {
    $filename = 'import_errors_' . $sessionId . '.xlsx';
    $filepath = $tempDir . '/' . $filename;
    
    // Удаляем старые файлы с ошибками перед созданием нового
    cleanupOldErrorFiles($tempDir);
    
    // Создаем Excel файл с помощью SimpleXLSXWriter
    $writer = new SimpleXLSXWriter('Ошибки импорта');
    $writer->addRows($excelData);
    
    if (!$writer->writeToFile($filepath)) {
        throw new Exception('Не удалось создать файл');
    }
    
    // Отправляем файл для скачивания
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    readfile($filepath);
    
    // НЕ удаляем файл сразу - он будет удален автоматически через время функцией cleanupOldErrorFiles
    
} catch (Exception $e) {
    sendResponse(false, 'Ошибка при создании файла: ' . $e->getMessage());
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