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
require_once 'lib/AuthController.php';
AuthController::login($userId);

// Подключаем автолоадер для всех классов из lib
require_once 'autoloader.php';
require_once 'Settings.php';

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

// Функция для нормализации текста заголовка (убираем пробелы, приводим к нижнему регистру)
function normalizeHeaderText($text) {
    // Убираем все пробелы, переводим в нижний регистр, убираем специальные символы
    $normalized = mb_strtolower(trim($text), 'UTF-8');
    $normalized = preg_replace('/\s+/', '', $normalized); // Убираем все пробелы
    $normalized = preg_replace('/[^\p{L}\p{N}]/u', '', $normalized); // Оставляем только буквы и цифры
    return $normalized;
}

// Функция для проверки соответствия заголовков с гибким сравнением
function isHeaderMatch($actualHeader, $expectedHeader) {
    if (empty($expectedHeader)) {
        return empty($actualHeader); // Для пустых столбцов
    }
    
    $actualNormalized = normalizeHeaderText($actualHeader);
    $expectedNormalized = normalizeHeaderText($expectedHeader);
    
    // Точное совпадение после нормализации
    if ($actualNormalized === $expectedNormalized) {
        return true;
    }
    
    // Дополнительные варианты для некоторых полей
    $alternativeMatches = [
        'должность' => ['должностьповизитке', 'позиция', 'роль', 'должностьпо', 'должностьна'],
        'мобильныйтелефон' => ['мобильный', 'моб', 'мобтелефон', 'сотовый', 'сотовыйтелефон', 'мобильныйтел', 'мобтел'],
        'телефон' => ['тел', 'рабочийтелефон', 'офисныйтелефон', 'телрабочий', 'телофисный'],
        'email' => ['почта', 'электроннаяпочта', 'емейл', 'емайл', 'mail', 'эмейл', 'эмайл'],
        'контрагент' => ['компания', 'организация', 'предприятие', 'фирма', 'контр', 'наименованиеполное', 'наименование','название'],
        'инн' => ['иннкомпании', 'иннорганизации', 'иннконтрагента'],
        'страна' => ['country', 'государство', 'регион'],
        'контактноелицо' => ['фио', 'контакт', 'лицо', 'представитель', 'имя', 'фамилия', 'имяфамилия', 'фамилияимя', 'полноеимя', 'контактныйчеловек', 'ответственный'],
        // Оставляем старые варианты для совместимости
        'фамилия' => ['lastname', 'surname', 'фам'],
        'имя' => ['firstname', 'name'],
        'отчество' => ['middlename', 'patronymic', 'отч']
    ];
    
    // Проверяем альтернативные варианты для ожидаемого поля
    if (isset($alternativeMatches[$expectedNormalized])) {
        foreach ($alternativeMatches[$expectedNormalized] as $alternative) {
            if ($actualNormalized === $alternative) {
                return true;
            }
        }
    }
    
    // Обратная проверка - проверяем, не является ли ожидаемое поле альтернативой для актуального
    foreach ($alternativeMatches as $mainField => $alternatives) {
        if (in_array($expectedNormalized, $alternatives) && $actualNormalized === $mainField) {
            return true;
        }
        if (in_array($actualNormalized, $alternatives) && $expectedNormalized === $mainField) {
            return true;
        }
    }
    
    // Проверяем, содержит ли актуальный заголовок ожидаемый (для случаев типа "ДолжностьПоВизитке" содержит "Должность")
    if (mb_strlen($expectedNormalized) >= 3 && mb_strpos($actualNormalized, $expectedNormalized) !== false) {
        return true;
    }
    
    // Проверяем обратное - содержит ли ожидаемый заголовок актуальный (для случаев типа "Мобильный телефон" содержит "Мобильный")
    if (mb_strlen($actualNormalized) >= 3 && mb_strpos($expectedNormalized, $actualNormalized) !== false) {
        return true;
    }
    
    // Специальная проверка для составных названий
    // Разбиваем на слова и проверяем пересечения
    $expectedWords = preg_split('/[\s\-_]+/', mb_strtolower($expectedHeader, 'UTF-8'));
    $actualWords = preg_split('/[\s\-_]+/', mb_strtolower($actualHeader, 'UTF-8'));
    
    // Убираем пустые элементы
    $expectedWords = array_filter($expectedWords, function($word) { return !empty(trim($word)); });
    $actualWords = array_filter($actualWords, function($word) { return !empty(trim($word)); });
    
    // Если есть пересечение ключевых слов
    $intersection = array_intersect($expectedWords, $actualWords);
    if (!empty($intersection)) {
        // Проверяем, что пересечение содержит значимые слова (не предлоги и т.п.)
        $significantWords = array_filter($intersection, function($word) {
            return mb_strlen($word) >= 3 && !in_array($word, ['для', 'при', 'над', 'под', 'без', 'про', 'через']);
        });
        
        if (!empty($significantWords)) {
            return true;
        }
    }
    
    return false;
}

// Функция для проверки соответствия заголовков ожидаемому порядку
function checkHeadersOrder($headers) {
    $expectedFields = Settings::AR_FIELDS;
    $warnings = [];
    $hasErrors = false;
    
    // Проверяем количество столбцов
    if (count($headers) < count($expectedFields)) {
        $warnings[] = "В файле недостаточно столбцов. Ожидается " . count($expectedFields) . ", найдено " . count($headers);
        $hasErrors = true;
    } elseif (count($headers) > count($expectedFields)) {
        $warnings[] = "В файле слишком много столбцов. Ожидается " . count($expectedFields) . ", найдено " . count($headers);
    }
    
    // Проверяем соответствие заголовков
    $maxCheck = min(count($headers), count($expectedFields));
    for ($i = 0; $i < $maxCheck; $i++) {
        $actualHeader = trim($headers[$i] ?? '');
        $expectedHeader = trim($expectedFields[$i] ?? '');
        
        // Если ожидается пустой столбец
        if (empty($expectedHeader)) {
            if (!empty($actualHeader)) {
                $warnings[] = "Столбец " . ($i + 1) . " должен быть пустым, но содержит: '{$actualHeader}' (это не критично)";
            }
        } else {
            // Если ожидается заполненный столбец
            if (empty($actualHeader)) {
                $warnings[] = "Столбец " . ($i + 1) . " должен содержать '{$expectedHeader}', но пустой";
                $hasErrors = true;
            } elseif (!isHeaderMatch($actualHeader, $expectedHeader)) {
                $warnings[] = "Столбец " . ($i + 1) . " должен содержать '{$expectedHeader}', но содержит '{$actualHeader}'";
                $hasErrors = true;
            }
        }
    }
    
    return [
        'has_errors' => $hasErrors,
        'warnings' => $warnings
    ];
}

// Функция для преобразования Excel файла в массив
function parseExcelToArray($filePath) {
    try {
        if ($xlsx = SimpleXLSX::parse($filePath)) {
            $data = [];
            
            // Получаем все листы
            $sheetNames = $xlsx->sheetNames();
            
            // Если нет названий листов, используем индексы
            if (empty($sheetNames)) {
                $sheetNames = ['Sheet1'];
            }
            
            foreach ($sheetNames as $sheetIndex => $sheetName) {
                $rows = $xlsx->rows($sheetIndex);
                if (!empty($rows)) {
                    $data[$sheetName] = $rows;
                }
            }
            
            return $data;
        } else {
            throw new Exception('Ошибка при парсинге Excel файла: ' . SimpleXLSX::parseError());
        }
    } catch (Exception $e) {
        throw new Exception('Ошибка при обработке Excel файла: ' . $e->getMessage());
    }
}

// Проверяем, что файл был загружен
if (!isset($_FILES['uploaded_file']) || $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, 'Ошибка при загрузке файла');
}

$uploadedFile = $_FILES['uploaded_file'];

// Проверяем размер файла (максимум 10MB)
$maxFileSize = 10 * 1024 * 1024; // 10MB в байтах
if ($uploadedFile['size'] > $maxFileSize) {
    sendResponse(false, 'Файл слишком большой. Максимальный размер: 10MB');
}

// Проверяем расширение файла
$fileName = $uploadedFile['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'xlsx') {
    sendResponse(false, 'Неверный формат файла. Разрешены только файлы .xlsx');
}

// Проверяем MIME тип
$allowedMimeTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/octet-stream' // Иногда .xlsx файлы имеют этот MIME тип
];

$fileMimeType = $uploadedFile['type'];
if (!in_array($fileMimeType, $allowedMimeTypes)) {
    // Дополнительная проверка через finfo, если доступно
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($detectedMimeType, $allowedMimeTypes)) {
            sendResponse(false, 'Неверный тип файла. Файл должен быть в формате .xlsx');
        }
    }
}

// Преобразуем Excel файл в массив
try {
    // Проверяем, что временный файл существует
    if (!file_exists($uploadedFile['tmp_name'])) {
        throw new Exception('Временный файл не найден: ' . $uploadedFile['tmp_name']);
    }
    
    // Проверяем размер файла
    $fileSize = filesize($uploadedFile['tmp_name']);
    if ($fileSize === false || $fileSize === 0) {
        throw new Exception('Файл пустой или поврежден');
    }
    
    // Пытаемся парсить Excel файл
    $excelData = parseExcelToArray($uploadedFile['tmp_name']);
    $parseSuccess = true;
    $parseError = null;
    
} catch (Exception $e) {
    // В случае ошибки записываем информацию об ошибке
    $parseSuccess = false;
    $parseError = $e->getMessage();
    $excelData = [
        'Ошибка_парсинга' => [
            ['Параметр', 'Значение'],
            ['Ошибка', $parseError],
            ['Файл', $fileName],
            ['Размер', $fileSize . ' байт'],
            ['Время', date('Y-m-d H:i:s')],
            ['ZipArchive', class_exists('ZipArchive') ? 'Доступно' : 'Недоступно'],
            ['SimpleXML', function_exists('simplexml_load_string') ? 'Доступно' : 'Недоступно']
        ]
    ];
}

// Получаем контакты из первого листа (исключаем заголовок)
$arContacts = $excelData['Sheet1'] ?? [];
$headers = [];

if (!empty($arContacts)) {
    // Сохраняем заголовки перед их удалением
    $headers = $arContacts[0] ?? [];
    unset($arContacts[0]); // Исключаем шапку таблицы из контактов
    $arContacts = array_values($arContacts); // Переиндексируем массив
}

// Проверяем соответствие заголовков ожидаемому порядку
$headerCheck = checkHeadersOrder($headers);
$warningMessage = '';

if (!empty($headerCheck['warnings'])) {
    $warningMessage = "\n\n⚠️ ПРЕДУПРЕЖДЕНИЯ О ПОРЯДКЕ СТОЛБЦОВ:\n";
    foreach ($headerCheck['warnings'] as $warning) {
        $warningMessage .= "• " . $warning . "\n";
    }
    
    if ($headerCheck['has_errors']) {
        $warningMessage .= "\n❌ КРИТИЧЕСКИЕ ОШИБКИ: Импорт может работать некорректно!";
        $warningMessage .= "\nРекомендуется исправить порядок столбцов перед импортом.";
    } else {
        $warningMessage .= "\n⚠️ Обнаружены несоответствия, но импорт может продолжиться.";
    }
}

// Сохраняем заголовки в сессии для использования при скачивании ошибок
$_SESSION['original_file_data'] = [
    'headers' => $headers,
    'filename' => $fileName
];

// Проверяем, что есть контакты для обработки
if (empty($arContacts)) {
    sendResponse(false, 'В файле не найдено контактов для импорта');
}

// Создаем уникальный ID сессии
$sessionId = uniqid('import_', true);
$_SESSION['import_session_id'] = $sessionId;

// Создаем папку temp, если её нет
$tempDir = __DIR__ . '/temp';
if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0755, true)) {
        sendResponse(false, 'Ошибка при создании временной папки');
    }
}

// Полностью очищаем папку temp перед началом нового импорта
clearTempDirectory($tempDir);

// Сохраняем контакты во временный файл
$contactsFile = $tempDir . '/contacts_' . $sessionId . '.json';
$result = file_put_contents($contactsFile, json_encode($arContacts, JSON_UNESCAPED_UNICODE));

if ($result === false) {
    sendResponse(false, 'Ошибка при сохранении контактов во временный файл');
}

// Подготавливаем данные о прогрессе
$totalContacts = count($arContacts);
$batchSize = 10;
$totalBatches = ceil($totalContacts / $batchSize);

$progressData = [
    'session_id' => $sessionId,
    'total_contacts' => $totalContacts,
    'processed_contacts' => 0,
    'total_batches' => $totalBatches,
    'current_batch' => 0,
    'batch_size' => $batchSize,
    'progress_percent' => 0,
    'completed' => false,
    'start_time' => date('Y-m-d H:i:s'),
    'last_update' => date('Y-m-d H:i:s'),
    'end_time' => null,
    'contacts_updated_count' => 0,
    'contacts_upload_count' => 0,
    'companies_upload_count' => 0,
    'contacts_upload_error_count' => 0,
    'contacts_upload_error_data' => [],
    'contacts_upload_error_log' => [],
    'error_file' => null
];

// Сохраняем данные о прогрессе
$progressFile = $tempDir . '/progress_' . $sessionId . '.json';
$result = file_put_contents($progressFile, json_encode($progressData, JSON_UNESCAPED_UNICODE));

if ($result === false) {
    sendResponse(false, 'Ошибка при создании файла прогресса');
}

$successMessage = "Файл успешно загружен и подготовлен к импорту!\n";
$successMessage .= "Всего контактов: " . $totalContacts . "\n";
$successMessage .= "Будет обработано батчей: " . $totalBatches;
$successMessage .= $warningMessage; // Добавляем предупреждения о столбцах

// Отправляем ответ с данными для начала импорта
sendResponse(true, $successMessage, [
    'session_id' => $sessionId,
    'total_contacts' => $totalContacts,
    'total_batches' => $totalBatches,
    'batch_size' => $batchSize,
    'ready_for_import' => true,
    'has_column_warnings' => !empty($headerCheck['warnings']),
    'column_warnings' => $headerCheck['warnings'] ?? [],
    'has_critical_errors' => $headerCheck['has_errors'] ?? false
]);

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

/**
 * Полностью очищает папку temp от всех файлов
 */
function clearTempDirectory($tempDir) {
    if (!is_dir($tempDir)) {
        return;
    }
    
    // Получаем все файлы в папке temp
    $files = glob($tempDir . '/*');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
?>