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
    <title>Загрузка оборотов компаний</title>
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
        
        .info-block {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        .info-block h3 {
            color: #1976D2;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1em;
            font-weight: 500;
        }
        
        .info-block p {
            color: #1976D2;
            margin-bottom: 10px;
            line-height: 1.5;
            font-size: 14px;
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
        <h1>Загрузка оборотов компаний</h1>
        
        <!-- Информационный блок -->
        <div class="info-block">
            <h3>ℹ️ Информация о процессе</h3>
            <p><strong>Что делает это приложение:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px; color: #1976D2;">
                <li>Получает список всех компаний из CRM</li>
                <li>Загружает данные об оборотах компаний порциями по 10 компаний</li>
                <li>Обновляет пользовательские поля компаний с данными об оборотах</li>
                <li>Показывает прогресс выполнения в реальном времени</li>
            </ul>
            <p><strong>Внимание:</strong> Процесс может занять некоторое время в зависимости от количества компаний в системе.</p>
        </div>
        
        <button id="startBtn" onclick="startRevenueUpload()">Начать загрузку оборотов компаний</button>
        
        <div id="message"></div>
        
        <!-- Контейнер для отображения прогресса -->
        <div id="progressContainer" class="progress-container">
            <div class="progress-info">
                <h3>Загрузка оборотов компаний</h3>
                <div class="progress-bar">
                    <div id="progressFill" class="progress-fill">0%</div>
                </div>
                
                <div class="progress-stats">
                    <div class="stat-item">
                        <div class="stat-label">Обработано компаний</div>
                        <div id="processedCompanies" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Всего компаний</div>
                        <div id="totalCompanies" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Обновлено компаний</div>
                        <div id="updatedCompanies" class="stat-value">0</div>
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
        
        const startBtn = document.getElementById('startBtn');
        const messageDiv = document.getElementById('message');
        const progressContainer = document.getElementById('progressContainer');
        
        // Элементы прогресса
        const progressFill = document.getElementById('progressFill');
        const processedCompanies = document.getElementById('processedCompanies');
        const totalCompanies = document.getElementById('totalCompanies');
        const updatedCompanies = document.getElementById('updatedCompanies');
        const errorCount = document.getElementById('errorCount');
        const importLog = document.getElementById('importLog');
        
        // Переменные для управления импортом
        let currentSessionId = null;
        let importInterval = null;

        // Функция для обновления прогресса
        function updateProgress(progressData) {
            const percent = progressData.progress_percent || 0;
            progressFill.style.width = percent + '%';
            progressFill.textContent = percent + '%';
            
            processedCompanies.textContent = progressData.processed_companies || 0;
            totalCompanies.textContent = progressData.total_companies || 0;
            updatedCompanies.textContent = progressData.companies_updated_count || 0;
            errorCount.textContent = progressData.companies_error_count || 0;
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
                        addLogEntry('Загрузка оборотов компаний завершена успешно!');
                        startBtn.disabled = false;
                        startBtn.textContent = 'Начать загрузку оборотов компаний';
                        
                        // Показываем итоговое сообщение
                        let finalMessage = '<div class="success">Загрузка оборотов компаний завершена!</div>';
                        if (data.data.companies_error_count > 0) {
                            finalMessage += `<div style="margin-top: 10px; color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 4px; border-left: 3px solid #ffc107;">
                                Обработано компаний: ${data.data.processed_companies}, Обновлено: ${data.data.companies_updated_count}, Ошибок: ${data.data.companies_error_count}
                            </div>`;
                        }
                        messageDiv.innerHTML = finalMessage;
                    } else {
                        addLogEntry(`Обработано компаний ${data.data.processed_companies} из ${data.data.total_companies}`);
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
            addLogEntry('Начинаем загрузку оборотов компаний...');
            
            // Запускаем проверку прогресса каждые 2 секунды
            importInterval = setInterval(checkProgress, 2000);
            
            // Сразу проверяем прогресс
            checkProgress();
        }

        // Функция для начала загрузки оборотов
        function startRevenueUpload() {
            startBtn.disabled = true;
            startBtn.textContent = 'Подготовка...';
            messageDiv.innerHTML = '';
            
            const formData = new FormData();
            formData.append('user_id', userId);
            
            fetch('src/start_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.ready_for_import) {
                    startBtn.textContent = 'Загрузка...';
                    startImport(data.data.session_id);
                } else {
                    messageDiv.innerHTML = `<div class="error">${data.message}</div>`;
                    startBtn.disabled = false;
                    startBtn.textContent = 'Начать загрузку оборотов компаний';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = `<div class="error">Произошла ошибка при подготовке загрузки</div>`;
                console.error('Error:', error);
                startBtn.disabled = false;
                startBtn.textContent = 'Начать загрузку оборотов компаний';
            });
        }
        
        // Обработчик события beforeunload для прерывания загрузки при перезагрузке страницы
        window.addEventListener('beforeunload', function(e) {
            if (currentSessionId && importInterval) {
                // Отправляем запрос на прерывание загрузки
                const formData = new FormData();
                formData.append('session_id', currentSessionId);
                formData.append('user_id', userId);
                
                // Используем sendBeacon для отправки запроса при закрытии страницы
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('src/cancel_upload.php', formData);
                }
            }
        });
    </script>
</body>
</html>
