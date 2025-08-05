<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
global $USER;

// Проверяем авторизацию
if (!$USER->IsAuthorized()) {
    http_response_code(403);
    die('Доступ запрещен');
}

// Подключаем AuthController и выполняем авторизацию
require_once '../lib/AuthController.php';
$userId = $USER->GetID();
AuthController::login($userId);

// Путь к файлу документации (теперь относительно src папки)
$documentationFile = __DIR__ . '/../docs/Описание работы Импорта контактов.docx';

// Проверяем существование файла
if (!file_exists($documentationFile)) {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Файл не найден</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; }
            .error-container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-title { color: #dc3545; margin-bottom: 20px; }
            .error-message { color: #495057; line-height: 1.6; }
            .back-button { background-color: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 20px; }
            .back-button:hover { background-color: #1976D2; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h2 class="error-title">📄 Файл документации не найден</h2>
            <div class="error-message">
                <p>Файл "Описание работы Импорта контактов.docx" не найден в папке документации.</p>
                <p><strong>Что нужно сделать:</strong></p>
                <ol>
                    <li>Поместите файл документации в папку: <code>/docs/</code></li>
                    <li>Убедитесь, что файл называется точно: <code>Описание работы Импорта контактов.docx</code></li>
                    <li>Проверьте права доступа к файлу</li>
                </ol>
                <p>После размещения файла кнопка скачивания будет работать корректно.</p>
            </div>
            <button class="back-button" onclick="history.back()">← Вернуться назад</button>
        </div>
    </body>
    </html>';
    exit;
}

// Проверяем, что файл читаемый
if (!is_readable($documentationFile)) {
    http_response_code(403);
    die('Файл документации недоступен для чтения');
}

// Устанавливаем заголовки для скачивания
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="Описание работы Импорта контактов.docx"');
header('Content-Length: ' . filesize($documentationFile));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Выводим содержимое файла
readfile($documentationFile);
exit;
?>