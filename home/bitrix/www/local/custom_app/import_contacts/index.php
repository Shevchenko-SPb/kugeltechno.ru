<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
global $USER;
$isAdmin = $USER->IsAdmin();
$userId = $USER->GetID();

// Подключаем AuthController и выполняем авторизацию
require_once 'lib/AuthController.php';
AuthController::login($userId);

require_once 'Settings.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Импорт контактов</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
            font-size: 1em;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
            font-size: 14px;
            color: #495057;
        }
        
        input[type="file"]:hover {
            border-color: #2196F3;
            background-color: #f0f8ff;
        }
        
        button {
            background-color: #2196F3;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.2s ease;
        }
        
        button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        button:hover:not(:disabled) {
            background-color: #1976D2;
        }
        
        .file-info {
            margin-top: 10px;
            padding: 15px;
            background-color: #d4edda;
            border-radius: 6px;
            display: none;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
        }
        
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        
        .progress-container {
            margin-top: 20px;
            display: none;
        }
        
        .progress-bar {
            width: 100%;
            height: 24px;
            background-color: #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #2196F3;
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 500;
            font-size: 12px;
        }
        
        .progress-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
        }
        
        .progress-info h3 {
            color: #495057;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.2em;
            font-weight: 500;
        }
        
        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }
        
        .stat-item {
            background-color: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 600;
            color: #495057;
        }
        
        .import-log {
            max-height: 180px;
            overflow-y: auto;
            background-color: #f8f9fa;
            color: #495057;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            margin-top: 15px;
            border: 1px solid #dee2e6;
        }
        
        .warning-block {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .warning-block h3 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1em;
            font-weight: 500;
        }
        
        .warning-block p {
            color: #856404;
            margin-bottom: 10px;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .columns-list {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .columns-list h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #495057;
            font-size: 1em;
            font-weight: 500;
        }
        
        .columns-list ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .columns-list li {
            padding: 3px 0;
            color: #495057;
            font-size: 14px;
        }
        
        .columns-list li.empty-column {
            color: #6c757d;
            font-style: italic;
        }
        
        .toggle-details {
            background-color: #2196F3;
            border: none;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 16px;
            margin-top: 10px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
            width: auto;
        }
        
        .toggle-details:hover {
            background-color: #1976D2;
        }
        
        .download-docs-btn {
            background-color: #28a745 !important;
        }
        
        .download-docs-btn:hover {
            background-color: #218838 !important;
        }
        
        .warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
            border-left: 4px solid #ffc107;
            font-size: 14px;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5em;
            }
            
            .progress-stats {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 8px;
            }
            
            .stat-item {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Импорт контактов</h1>
        
        <!-- Блок предупреждения о порядке столбцов -->
        <div class="warning-block">
            <h3>⚠️ Важно! Порядок столбцов в Excel файле</h3>
            
            <div style="margin-bottom: 15px;">
                <button type="button" class="toggle-details download-docs-btn" onclick="downloadDocumentation()" style="margin-right: 10px;">
                    📄 Скачать описание работы
                </button>
                <button type="button" class="toggle-details" onclick="toggleColumnOrder()">
                    <span id="toggleOrderText">📋 Показать описание Excel файла</span>
                </button>
            </div>
            
            <div id="columnOrderDetails" style="display: none; margin-top: 15px;">
                <p><strong>Столбцы в вашем Excel файле должны быть расположены строго в определенном порядке.</strong></p>
                <p><strong>Внимание:</strong> Если порядок столбцов будет нарушен, контакты будут загружены с ошибками или неправильными данными!</p>
                
                <div class="columns-list">
                    <h4>Обязательный порядок столбцов:</h4>
                    <ol>
                        <?php
                        foreach (Settings::AR_FIELDS as $index => $field) {
                            $columnNumber = $index + 1;
                            if (empty($field)) {
                                echo "<li class='empty-column'>Столбец {$columnNumber}: (пустой столбец)</li>";
                            } else {
                                echo "<li>Столбец {$columnNumber}: <strong>{$field}</strong></li>";
                            }
                        }
                        ?>
                    </ol>
                </div>
                
                <button type="button" class="toggle-details" onclick="toggleColumnDetails()" style="margin-top: 10px;">
                    <span id="toggleText">Показать дополнительные подробности</span>
                </button>
                
                <div id="columnDetails" style="display: none; margin-top: 15px;">
                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 3px solid #007bff;">
                        <h4 style="margin-top: 0; color: #495057;">Дополнительная информация:</h4>
                        <ul style="margin: 0; color: #495057;">
                            <li>Пустые столбцы (столбцы 3, 9, 10) должны присутствовать в файле, но могут быть без данных</li>
                            <li>Первая строка файла должна содержать заголовки столбцов</li>
                            <li>Данные контактов начинаются со второй строки</li>
                            <li>Убедитесь, что все столбцы присутствуют, даже если некоторые из них пустые</li>
                            <li><strong>Гибкость названий:</strong> система допускает небольшие отличия в названиях столбцов (пробелы, регистр, синонимы)</li>
                            <li>Например: "Мобильный телефон" = "МобильныйТелефон" = "Мобильный" = "Сотовый"</li>
                            <li>Например: "Должность" = "ДолжностьПоВизитке" = "Позиция" = "Роль"</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fileInput">Выберите файл .xlsx:</label>
                <input type="file" id="fileInput" name="uploaded_file" accept=".xlsx">
                <div id="fileInfo" class="file-info"></div>
            </div>
            
            <button type="submit" id="uploadBtn" disabled>Начать загрузку</button>
        </form>
        
        <div id="message"></div>
        
        <!-- Контейнер для отображения прогресса -->
        <div id="progressContainer" class="progress-container">
            <div class="progress-info">
                <h3>Импорт контактов</h3>
                <div class="progress-bar">
                    <div id="progressFill" class="progress-fill">0%</div>
                </div>
                
                <div class="progress-stats">
                    <div class="stat-item">
                        <div class="stat-label">Обработано контактов</div>
                        <div id="processedContacts" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Всего контактов</div>
                        <div id="totalContacts" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Добавлено контактов</div>
                        <div id="uploadedContacts" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Обновлено контактов</div>
                        <div id="updatedContacts" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Добавлено компаний</div>
                        <div id="uploadedCompanies" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Ошибок</div>
                        <div id="errorCount" class="stat-value">0</div>
                    </div>
                </div>
                
                <div id="importLog" class="import-log"></div>
            </div>
        </div>
    </div>

    <script>
        // ID пользователя для авторизации
        const userId = <?php echo $userId; ?>;
        
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const fileInfo = document.getElementById('fileInfo');
        const uploadForm = document.getElementById('uploadForm');
        const messageDiv = document.getElementById('message');
        const progressContainer = document.getElementById('progressContainer');
        
        // Элементы прогресса
        const progressFill = document.getElementById('progressFill');
        const processedContacts = document.getElementById('processedContacts');
        const totalContacts = document.getElementById('totalContacts');
        const uploadedContacts = document.getElementById('uploadedContacts');
        const updatedContacts = document.getElementById('updatedContacts');
        const uploadedCompanies = document.getElementById('uploadedCompanies');
        const errorCount = document.getElementById('errorCount');
        const importLog = document.getElementById('importLog');
        
        // Переменные для управления импортом
        let currentSessionId = null;
        let importInterval = null;

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Проверяем расширение файла
                const fileName = file.name.toLowerCase();
                const isXlsx = fileName.endsWith('.xlsx');
                
                if (isXlsx) {
                    // Показываем информацию о файле
                    fileInfo.innerHTML = `
                        <strong>Выбранный файл:</strong><br>
                        Название: ${file.name}<br>
                        Размер: ${(file.size / 1024).toFixed(2)} KB<br>
                        Тип: ${file.type || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}
                    `;
                    fileInfo.style.display = 'block';
                    
                    // Активируем кнопку
                    uploadBtn.disabled = false;
                    messageDiv.innerHTML = '';
                } else {
                    // Показываем ошибку
                    alert('Ошибка: Пожалуйста, выберите файл в формате .xlsx');
                    fileInfo.style.display = 'none';
                    uploadBtn.disabled = true;
                    this.value = ''; // Очищаем input
                }
            } else {
                fileInfo.style.display = 'none';
                uploadBtn.disabled = true;
            }
        });

        // Функция для обновления прогресса
        function updateProgress(progressData) {
            const percent = progressData.progress_percent || 0;
            progressFill.style.width = percent + '%';
            progressFill.textContent = percent + '%';
            
            processedContacts.textContent = progressData.processed_contacts || 0;
            totalContacts.textContent = progressData.total_contacts || 0;
            uploadedContacts.textContent = progressData.contacts_upload_count || 0;
            updatedContacts.textContent = progressData.contacts_updated_count || 0;
            uploadedCompanies.textContent = progressData.companies_upload_count || 0;
            errorCount.textContent = progressData.contacts_upload_error_count || 0;
        }
        
        // Функция для добавления записи в лог
        function addLogEntry(message) {
            const timestamp = new Date().toLocaleTimeString();
            importLog.innerHTML += `[${timestamp}] ${message}<br>`;
            importLog.scrollTop = importLog.scrollHeight;
        }
        
        // Функция для обработки следующего батча
        function processNextBatch() {
            if (!currentSessionId) return;
            
            const formData = new FormData();
            formData.append('session_id', currentSessionId);
            formData.append('user_id', userId);
            
            fetch('src/process_batch.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    updateProgress(data.data);
                    
                    if (data.data.completed) {
                        // Импорт завершен
                        clearInterval(importInterval);
                        addLogEntry('Импорт завершен успешно!');
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = 'Начать загрузку';
                        
                        // Показываем итоговое сообщение
                        let finalMessage = '<div class="success">Импорт контактов завершен!</div>';
                        if (data.data.contacts_upload_error_count > 0) {
                            finalMessage += `<div style="margin-top: 10px;">`;
                            finalMessage += `<button onclick="downloadErrors('${currentSessionId}')" style="margin-top: 10px; margin-right: 10px; background-color: #dc3545; padding: 8px 16px; border: none; border-radius: 4px; color: white; cursor: pointer;">Скачать файл с пропущенными контактами</button>`;
                            
                            // Проверяем, есть ли лог ошибок для скачивания
                            if (data.data.contacts_upload_error_log && data.data.contacts_upload_error_log.length > 0) {
                                finalMessage += `<button onclick="downloadErrorLog('${currentSessionId}')" style="margin-top: 10px; background-color: #6c757d; padding: 8px 16px; border: none; border-radius: 4px; color: white; cursor: pointer;">Скачать лог ошибок</button>`;
                            }
                            
                            finalMessage += `</div>`;
                        }
                        messageDiv.innerHTML = finalMessage;
                    } else {
                        addLogEntry(`Обработано контактов ${data.data.processed_contacts} из ${data.data.total_contacts}`);
                    }
                } else {
                    addLogEntry('Ошибка при обработке батча: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                addLogEntry('Ошибка сети при обработке батча');
                console.error('Error:', error);
            });
        }
        
        // Функция для проверки прогресса
        function checkProgress() {
            if (!currentSessionId) return;
            
            fetch(`src/get_progress.php?session_id=${currentSessionId}&user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    updateProgress(data.data);
                    
                    if (!data.data.completed) {
                        // Если импорт не завершен, обрабатываем следующий батч
                        processNextBatch();
                    }
                }
            })
            .catch(error => {
                console.error('Error checking progress:', error);
            });
        }
        
        // Функция для начала импорта
        function startImport(sessionId) {
            currentSessionId = sessionId;
            progressContainer.style.display = 'block';
            messageDiv.innerHTML = '';
            
            // Очищаем лог
            importLog.innerHTML = '';
            addLogEntry('Начинаем импорт контактов...');
            
            // Запускаем проверку прогресса каждые 2 секунды
            importInterval = setInterval(checkProgress, 2000);
            
            // Сразу проверяем прогресс
            checkProgress();
        }

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = fileInput.files[0];
            if (!file) {
                alert('Пожалуйста, выберите файл');
                return;
            }
            
            // Дополнительная проверка формата перед отправкой
            if (!file.name.toLowerCase().endsWith('.xlsx')) {
                alert('Ошибка: Файл должен быть в формате .xlsx');
                return;
            }
            
            // Отправляем форму
            const formData = new FormData(this);
            formData.append('user_id', userId);
            
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Подготовка...';
            
            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.ready_for_import) {
                    // Проверяем наличие предупреждений о столбцах
                    if (data.data.has_column_warnings) {
                        let warningHtml = '<div class="' + (data.data.has_critical_errors ? 'error' : 'warning') + '">';
                        warningHtml += '<strong>' + (data.data.has_critical_errors ? '❌ Критические ошибки в порядке столбцов!' : '⚠️ Предупреждения о порядке столбцов') + '</strong><br>';
                        
                        if (data.data.column_warnings && data.data.column_warnings.length > 0) {
                            warningHtml += '<ul style="margin: 10px 0; padding-left: 20px;">';
                            data.data.column_warnings.forEach(warning => {
                                warningHtml += '<li>' + warning + '</li>';
                            });
                            warningHtml += '</ul>';
                        }
                        
                        if (data.data.has_critical_errors) {
                            warningHtml += '<p><strong>Рекомендуется исправить порядок столбцов перед импортом!</strong></p>';
                            warningHtml += '<button onclick="location.reload()" style="background-color: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Исправить файл</button>';
                            warningHtml += '<button onclick="continueImportAnyway(\'' + data.data.session_id + '\')" style="background-color: #ffc107; color: #212529; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Продолжить импорт</button>';
                        } else {
                            warningHtml += '<p>Импорт может продолжиться, но рекомендуется проверить результат.</p>';
                            warningHtml += '<button onclick="continueImportAnyway(\'' + data.data.session_id + '\')" style="background-color: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Продолжить импорт</button>';
                        }
                        
                        warningHtml += '</div>';
                        messageDiv.innerHTML = warningHtml;
                        
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = 'Начать загрузку';
                    } else {
                        // Файл успешно подготовлен без предупреждений, начинаем импорт
                        uploadBtn.textContent = 'Импорт...';
                        startImport(data.data.session_id);
                    }
                } else {
                    messageDiv.innerHTML = `<div class="error">${data.message}</div>`;
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Начать загрузку';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = `<div class="error">Произошла ошибка при обработке файла</div>`;
                console.error('Error:', error);
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Начать загрузку';
            });
        });
        
        // Функция для скачивания файла с ошибками
        function downloadErrors(sessionId) {
            if (!sessionId) {
                alert('Ошибка: не найден ID сессии');
                return;
            }
            
            // Создаем ссылку для скачивания
            const downloadUrl = `src/download_errors.php?session_id=${sessionId}&user_id=${userId}`;
            
            // Создаем временную ссылку и кликаем по ней
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `import_errors_${sessionId}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Функция для скачивания лога ошибок
        function downloadErrorLog(sessionId) {
            if (!sessionId) {
                alert('Ошибка: не найден ID сессии');
                return;
            }
            
            // Создаем ссылку для скачивания
            const downloadUrl = `src/download_error_log.php?session_id=${sessionId}&user_id=${userId}`;
            
            // Создаем временную ссылку и кликаем по ней
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `import_error_log_${sessionId}.txt`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Функция для переключения отображения порядка столбцов
        function toggleColumnOrder() {
            const orderDetails = document.getElementById('columnOrderDetails');
            const toggleOrderText = document.getElementById('toggleOrderText');
            
            if (orderDetails.style.display === 'none') {
                orderDetails.style.display = 'block';
                toggleOrderText.textContent = '📋 Скрыть описание';
            } else {
                orderDetails.style.display = 'none';
                toggleOrderText.textContent = '📋 Показать описание Excel файла';
            }
        }
        
        // Функция для переключения отображения дополнительных подробностей
        function toggleColumnDetails() {
            const details = document.getElementById('columnDetails');
            const toggleText = document.getElementById('toggleText');
            
            if (details.style.display === 'none') {
                details.style.display = 'block';
                toggleText.textContent = 'Скрыть дополнительные подробности';
            } else {
                details.style.display = 'none';
                toggleText.textContent = 'Показать дополнительные подробности';
            }
        }
        
        // Функция для продолжения импорта несмотря на предупреждения
        function continueImportAnyway(sessionId) {
            if (!sessionId) {
                alert('Ошибка: не найден ID сессии');
                return;
            }
            
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Импорт...';
            messageDiv.innerHTML = '<div class="warning">Импорт начат несмотря на предупреждения о столбцах. Внимательно проверьте результат!</div>';
            
            startImport(sessionId);
        }
        
        // Функция для скачивания документации
        function downloadDocumentation() {
            // Сначала проверяем доступность файла
            fetch('src/download_documentation.php', { method: 'HEAD' })
            .then(response => {
                if (response.ok) {
                    // Файл доступен, начинаем скачивание
                    const downloadUrl = 'src/download_documentation.php';
                    
                    // Создаем временную ссылку и кликаем по ней
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = 'Описание работы Импорта контактов.docx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else if (response.status === 404) {
                    // Файл не найден, открываем страницу с инструкцией
                    window.open('src/download_documentation.php', '_blank');
                } else {
                    alert('Ошибка при скачивании документации. Попробуйте позже.');
                }
            })
            .catch(error => {
                console.error('Error checking documentation file:', error);
                // В случае ошибки сети все равно пытаемся скачать
                const downloadUrl = 'src/download_documentation.php';
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    </script>
</body>
</html>