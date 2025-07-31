<?php
require_once 'Settings.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Импорт контактов</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            background-color: #fafafa;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        button:hover:not(:disabled) {
            background-color: #0056b3;
        }
        .file-info {
            margin-top: 10px;
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 4px;
            display: none;
        }
        .error {
            color: #dc3545;
            margin-top: 10px;
        }
        .success {
            color: #28a745;
            margin-top: 10px;
        }
        .progress-container {
            margin-top: 20px;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background-color: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .progress-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .stat-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #495057;
        }
        .import-log {
            max-height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
        }
        .warning-block {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #f39c12;
        }
        .warning-block h3 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .warning-block p {
            color: #856404;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .columns-list {
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        .columns-list h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #495057;
            font-size: 16px;
        }
        .columns-list ol {
            margin: 0;
            padding-left: 20px;
        }
        .columns-list li {
            padding: 3px 0;
            color: #495057;
        }
        .columns-list li.empty-column {
            color: #6c757d;
            font-style: italic;
        }
        .toggle-details {
            background: #007bff;
            border: 1px solid #007bff;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            padding: 8px 16px;
            margin-top: 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .toggle-details:hover {
            background: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Импорт контактов</h1>
        
        <!-- Блок предупреждения о порядке столбцов -->
        <div class="warning-block">
            <h3>⚠️ Важно! Порядок столбцов в Excel файле</h3>
            
            <button type="button" class="toggle-details" onclick="toggleColumnOrder()">
                <span id="toggleOrderText">📋 Показать подробности</span>
            </button>
            
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
                        finalMessage += `<div style="margin-top: 10px;">`;
                        finalMessage += `Обработано контактов: ${data.data.processed_contacts}<br>`;
                        finalMessage += `Добавлено контактов: ${data.data.contacts_upload_count}<br>`;
                        finalMessage += `Обновлено контактов: ${data.data.contacts_updated_count}<br>`;
                        finalMessage += `Добавлено компаний: ${data.data.companies_upload_count}<br>`;
                        if (data.data.contacts_upload_error_count > 0) {
                            finalMessage += `Ошибок: ${data.data.contacts_upload_error_count}<br>`;
                            finalMessage += `<button onclick="downloadErrors('${currentSessionId}')" style="margin-top: 10px; background-color: #dc3545; padding: 8px 16px; border: none; border-radius: 4px; color: white; cursor: pointer;">Скачать файл с пропущенными контактами</button><br>`;
                        }
                        finalMessage += `</div>`;
                        messageDiv.innerHTML = finalMessage;
                    } else {
                        addLogEntry(`Обработан батч ${data.data.current_batch}/${data.data.total_batches}`);
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
            
            fetch(`src/get_progress.php?session_id=${currentSessionId}`)
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
            const downloadUrl = `src/download_errors.php?session_id=${sessionId}`;
            
            // Создаем временную ссылку и кликаем по ней
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `import_errors_${sessionId}.xlsx`;
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
                toggleOrderText.textContent = '📋 Скрыть подробности';
            } else {
                orderDetails.style.display = 'none';
                toggleOrderText.textContent = '📋 Показать подробности';
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
    </script>
</body>
</html>