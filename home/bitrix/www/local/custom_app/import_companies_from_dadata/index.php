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
    <title>Загрузка филиалов компаний из DaDaTa</title>
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
        
        #pauseBtn {
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        #pauseBtn:hover:not(:disabled) {
            background-color: #e0a800 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        #pauseBtn[style*="background-color: #28a745"]:hover:not(:disabled) {
            background-color: #218838 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
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
        
        /* Стили для фильтров */
        #filtersContent {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .filter-item {
            padding: 10px 12px;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        }
        
        .filter-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .filter-item select,
        .filter-item input {
            width: 100%;
            height: 40px;
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }
        
        .filter-item select[multiple] {
            min-height: 80px;
        }
        
        .filter-buttons {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .filter-buttons button {
            margin-right: 10px;
            width: auto;
            padding: 8px 16px;
        }
        
        /* Стили для кнопки переключения фильтров */
        #toggleFiltersBtn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        
        #toggleFiltersIcon {
            margin-left: 10px;
            transition: transform 0.3s ease;
        }
        
        .filters-hidden #toggleFiltersIcon {
            transform: rotate(-90deg);
        }
        
        /* Анимация для блока фильтров */
        .filters-container {
            transition: all 0.3s ease;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }
        
        .filters-container.show {
            max-height: 2000px;
            opacity: 1;
        }
        
        /* Стили для множественного выбора с поиском */
        .multi-select-container {
            position: relative;
        }
        
        .multi-select-dropdown {
            position: relative;
            width: 100%;
        }
        
        .multi-select-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }
        
        .multi-select-header:hover {
            border-color: #007bff;
        }
        
        .dropdown-text {
            color: #6c757d;
            font-size: 14px;
        }
        
        .dropdown-arrow {
            color: #6c757d;
            transition: transform 0.2s ease;
        }
        
        .multi-select-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 300px;
            overflow: hidden;
        }
        
        .multi-select-search {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-bottom: 1px solid #ced4da;
            font-size: 14px;
            outline: none;
        }
        
        .multi-select-options {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .multi-select-option {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s ease;
        }
        
        .multi-select-option:hover {
            background-color: #f8f9fa;
        }
        
        .multi-select-option:last-child {
            border-bottom: none;
        }
        
        .multi-select-option input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.1);
        }
        
        .multi-select-option label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            font-size: 14px;
        }
        
        .multi-select-option.hidden {
            display: none;
        }
        
        .selected-values {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        
        .selected-tag {
            background-color: #e9f4ff;
            color: #1976D2;
            padding: 4px 10px;
            border-radius: 14px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cfe8ff;
        }
        
        .selected-tag .remove-tag {
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        
        .selected-tag .remove-tag:hover {
            color: #ffc107;
        }
        
        /* Стили для одиночного выбора с поиском */
        .single-select-container {
            position: relative;
        }
        
        .single-select-dropdown {
            position: relative;
            width: 100%;
        }
        
        .single-select-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }
        
        .single-select-header:hover {
            border-color: #007bff;
        }
        
        .single-select-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 300px;
            overflow: hidden;
        }
        
        .single-select-search {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-bottom: 1px solid #ced4da;
            font-size: 14px;
            outline: none;
        }
        
        .single-select-options {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .single-select-option {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s ease;
        }
        
        .single-select-option:hover {
            background-color: #f8f9fa;
        }
        
        .single-select-option:last-child {
            border-bottom: none;
        }
        
        .single-select-option.hidden {
            display: none;
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
            
            .filter-buttons button {
                width: 100%;
                margin-right: 0;
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Загрузка филиалов компаний из DaDaTa</h1>
        
        <!-- Информационный блок -->
        <div class="info-block">
            <h3>ℹ️ Информация о процессе</h3>
            <p><strong>Что делает это приложение:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px; color: #1976D2;">
                <li>Получает список всех компаний из CRM, используя фильтры</li>
                <li>Загружает данные о компаниях порциями по 10 компаний</li>
                <li>Создаёт филиалы компаний, если таких нет в Битриксе</li>
                <li>Заполняет поля с реквизитами филиалов</li>
            </ul>
            <p><strong>Внимание:</strong> Процесс может занять некоторое время в зависимости от количества компаний в системе.</p>
        </div>
        
        <!-- Кнопка для показа/скрытия фильтров -->
        <div style="margin-bottom: 15px;">
            <button type="button" id="toggleFiltersBtn" onclick="toggleFilters()" style="background-color: #17a2b8; width: auto; padding: 10px 20px; margin-bottom: 0;">
                <span id="toggleFiltersText">Скрыть фильтры</span>
                <span id="toggleFiltersIcon">▲</span>
            </button>
        </div>
        
        <!-- Фильтры -->
        <div id="filtersContainer" class="filters-container show" style="margin-bottom: 20px; padding: 16px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e9ecef; box-shadow: 0 2px 12px rgba(0,0,0,0.06); display: block"">
            <h3 style="margin-top: 0; margin-bottom: 15px; color: #495057; font-size: 1.1em;">Фильтры для выбора компаний</h3>
            <div id="filtersContent"></div>
            <!-- Чекбокс для обновления данных -->
            <div style="margin-top: 20px; padding: 15px; background-color: white; border-radius: 6px; border: 1px solid #dee2e6;">
                <label style="display: flex; align-items: center; cursor: pointer; font-weight: 500; color: #495057;">
                    <input type="checkbox" id="updateExistingCompanies" style="margin-right: 10px; transform: scale(1.2);">
                    Обновить данные ранее загруженных компаний
                </label>
                <div style="margin-top: 8px; font-size: 13px; color: #6c757d;">
                    Если выбрано - обновляются данные всех компаний. (Увеличение количество запросов может привести к увеличению стоимости загрузки)
                    Если не выбрано - обрабатываются только компании с полем "Выполнена проверка DaDaTa" со значением НЕТ.
                </div>
            </div>
            
            <div class="filter-buttons">
                <button type="button" onclick="clearFilters()" style="background-color: #6c757d;">Очистить фильтры</button>
                <button type="button" onclick="applyFilters()" style="background-color: #28a745;">Применить фильтры</button>
            </div>
        </div>
        
        <button id="startBtn" onclick="startRevenueUpload()">Начать загрузку данных</button>
        <button id="pauseBtn" onclick="togglePause()" style="display: none; background-color: #ffc107; margin-top: 10px;">Пауза</button>
        
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
                    <div class="stat-item">
                        <div class="stat-label">Баланс checko.ru</div>
                        <div id="balance" class="stat-value">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Запросов сегодня</div>
                        <div id="todayRequestCount" class="stat-value">0</div>
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
        
        // Переменные для фильтров
        let filtersData = {};
        let currentFilters = {};
        
        // Элементы прогресса
        const progressFill = document.getElementById('progressFill');
        const processedCompanies = document.getElementById('processedCompanies');
        const totalCompanies = document.getElementById('totalCompanies');
        const updatedCompanies = document.getElementById('updatedCompanies');
        const errorCount = document.getElementById('errorCount');
        const balance = document.getElementById('balance');
        const todayRequestCount = document.getElementById('todayRequestCount');
        const importLog = document.getElementById('importLog');
        
        // Переменные для управления импортом
        let currentSessionId = null;
        let importInterval = null;
        let isPaused = false;
        let pauseBtn = document.getElementById('pauseBtn');

        // Функция для обновления прогресса
        function updateProgress(progressData) {
            const percent = progressData.progress_percent || 0;
            progressFill.style.width = percent + '%';
            progressFill.textContent = percent + '%';
            
            processedCompanies.textContent = progressData.processed_companies || 0;
            totalCompanies.textContent = progressData.total_companies || 0;
            updatedCompanies.textContent = progressData.companies_updated_count || 0;
            errorCount.textContent = progressData.companies_error_count || 0;
            balance.textContent = progressData.balance || 0;
            todayRequestCount.textContent = progressData.today_request_count || 0;
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
                        
                        // Скрываем кнопку паузы
                        pauseBtn.style.display = 'none';
                        
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
                    
                    if (!data.data.completed && !isPaused) {
                        // Если импорт не завершен и не на паузе, обрабатываем следующий батч
                        processNextBatch();
                    }
                }
            })
            .catch(error => {
                console.error('Error checking progress:', error);
            });
        }
        
        // Функция для переключения паузы
        function togglePause() {
            isPaused = !isPaused;
            
            if (isPaused) {
                pauseBtn.textContent = 'Продолжить';
                pauseBtn.style.backgroundColor = '#28a745';
                addLogEntry('Загрузка приостановлена');
                
                // Останавливаем интервал проверки прогресса
                if (importInterval) {
                    clearInterval(importInterval);
                    importInterval = null;
                }
            } else {
                pauseBtn.textContent = 'Пауза';
                pauseBtn.style.backgroundColor = '#ffc107';
                addLogEntry('Загрузка возобновлена');
                
                // Возобновляем интервал проверки прогресса
                importInterval = setInterval(checkProgress, 2000);
                
                // Сразу проверяем прогресс
                checkProgress();
            }
        }
        
        // Функция для начала импорта
        function startImport(sessionId) {
            currentSessionId = sessionId;
            progressContainer.style.display = 'block';
            messageDiv.innerHTML = '';
            
            // Показываем кнопку паузы
            pauseBtn.style.display = 'block';
            
            // Очищаем лог
            importLog.innerHTML = '';
            addLogEntry('Начинаем загрузку оборотов компаний...');
            
            // Запускаем проверку прогресса каждые 2 секунды
            importInterval = setInterval(checkProgress, 2000);
            
            // Сразу проверяем прогресс
            checkProgress();
        }

        // Функция для загрузки фильтров при открытии страницы
        function loadFilters() {
            fetch('src/get_filters.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    filtersData = data.data;
                    renderFilters();
                    // Не показываем фильтры автоматически, пользователь сам решит когда их показать
                } else {
                    console.error('Ошибка загрузки фильтров:', data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка сети при загрузке фильтров:', error);
            });
        }
        
        // Функция для отрисовки фильтров
        function renderFilters() {
            const filtersContent = document.getElementById('filtersContent');
            let html = '';
            
            for (const [fieldCode, filterInfo] of Object.entries(filtersData)) {
                const fieldName = filterInfo.title || fieldCode;
                
                html += '<div class="filter-item">';
                html += `<label>${fieldName}</label>`;
                
                if (filterInfo.vals) {
                    const valuesCount = filterInfo.vals.length;
                    
                    // Выпадающий список с множественным выбором
                    if (filterInfo.multiple) {
                        html += `<div class="multi-select-container">`;
                        html += `<div class="multi-select-dropdown" id="dropdown_${fieldCode}">`;
                        html += `<div class="multi-select-header" onclick="toggleDropdown('${fieldCode}')">`;
                        html += `<span class="dropdown-text">Выберите значения...</span>`;
                        html += `<span class="dropdown-arrow">▼</span>`;
                        html += `</div>`;
                        html += `<div class="multi-select-content" id="content_${fieldCode}" style="display: none;">`;
                        html += `<input type="text" id="search_${fieldCode}" class="multi-select-search" placeholder="Поиск по значениям..." oninput="filterOptions('${fieldCode}')">`;
                        html += `<div class="multi-select-options" id="options_${fieldCode}">`;
                        filterInfo.vals.forEach(val => {
                            html += `<div class="multi-select-option" data-value="${val.id}" onclick="toggleOption('${fieldCode}', '${val.id}', '${val.title}')">`;
                            html += `<input type="checkbox" id="option_${fieldCode}_${val.id}">`;
                            html += `<label for="option_${fieldCode}_${val.id}">${val.title}</label>`;
                            html += `</div>`;
                        });
                        html += `</div>`;
                        html += `</div>`;
                        html += `</div>`;
                        html += `<div class="selected-values" id="selected_${fieldCode}"></div>`;
                        html += `</div>`;
                    } else {
                        // Обычный выпадающий список
                        if (valuesCount > 10) {
                            // Для списков с более чем 10 значениями добавляем поиск
                            html += `<div class="single-select-container">`;
                            html += `<div class="single-select-dropdown" id="dropdown_${fieldCode}">`;
                            html += `<div class="single-select-header" onclick="toggleSingleDropdown('${fieldCode}')">`;
                            html += `<span class="dropdown-text">Выберите значение...</span>`;
                            html += `<span class="dropdown-arrow">▼</span>`;
                            html += `</div>`;
                            html += `<div class="single-select-content" id="content_${fieldCode}" style="display: none;">`;
                            html += `<input type="text" id="search_${fieldCode}" class="single-select-search" placeholder="Поиск по значениям..." oninput="filterSingleOptions('${fieldCode}')">`;
                            html += `<div class="single-select-options" id="options_${fieldCode}">`;
                            filterInfo.vals.forEach(val => {
                                html += `<div class="single-select-option" data-value="${val.id}" onclick="selectSingleOption('${fieldCode}', '${val.id}', '${val.title}')">`;
                                html += `${val.title}`;
                                html += `</div>`;
                            });
                            html += `</div>`;
                            html += `</div>`;
                            html += `</div>`;
                            html += `</div>`;
                        } else {
                            // Единый выпадающий список с поиском
                            html += `<div class="single-select-container">`;
                            html += `<div class="single-select-dropdown" id="dropdown_${fieldCode}">`;
                            html += `<div class="single-select-header" onclick="toggleSingleDropdown('${fieldCode}')">`;
                            html += `<span class="dropdown-text">Выберите значение...</span>`;
                            html += `<span class="dropdown-arrow">▼</span>`;
                            html += `</div>`;
                            html += `<div class="single-select-content" id="content_${fieldCode}" style="display: none;">`;
                            html += `<input type="text" id="search_${fieldCode}" class="single-select-search" placeholder="Поиск по значениям..." oninput="filterSingleOptions('${fieldCode}')">`;
                            html += `<div class="single-select-options" id="options_${fieldCode}">`;
                            filterInfo.vals.forEach(val => {
                                html += `<div class="single-select-option" data-value="${val.id}" onclick="selectSingleOption('${fieldCode}', '${val.id}', '${val.title}')">`;
                                html += `${val.title}`;
                                html += `</div>`;
                            });
                            html += `</div>`;
                            html += `</div>`;
                            html += `</div>`;
                            html += `</div>`;
                        }
                    }
                } else if (filterInfo.type === 'string') {
                    // Текстовое поле
                    html += `<input type="text" id="filter_${fieldCode}" placeholder="Введите значение...">`;
                }
                
                html += '</div>';
            }
            
            filtersContent.innerHTML = html;
        }
        

        
        // Функция для применения фильтров
        function applyFilters() {
            currentFilters = {};
            
            for (const [fieldCode, filterInfo] of Object.entries(filtersData)) {
                if (filterInfo.vals) {
                    if (filterInfo.multiple) {
                        // Множественный выбор - собираем из чекбоксов
                        const selectedCheckboxes = document.querySelectorAll(`#options_${fieldCode} input[type="checkbox"]:checked`);
                        const selectedValues = Array.from(selectedCheckboxes).map(checkbox => checkbox.id.replace(`option_${fieldCode}_`, ''));
                        if (selectedValues.length > 0) {
                            currentFilters[fieldCode] = selectedValues;
                        }
                    } else {
                        // Одиночный выбор: берем из dropdown заголовка data-value
                        const dropdown = document.getElementById(`dropdown_${fieldCode}`);
                        const headerEl = dropdown ? dropdown.querySelector('.dropdown-text') : null;
                        const selectedValue = headerEl ? headerEl.getAttribute('data-value') : '';
                        if (selectedValue) {
                            currentFilters[fieldCode] = selectedValue;
                        }
                    }
                } else if (filterInfo.type === 'string') {
                    // Текстовое поле
                    const element = document.getElementById(`filter_${fieldCode}`);
                    if (element) {
                        const value = element.value.trim();
                        if (value !== '') {
                            currentFilters[fieldCode] = value;
                        }
                    }
                }
            }
            
            // Показываем информацию о примененных фильтрах
            const appliedFiltersCount = Object.keys(currentFilters).length;
            if (appliedFiltersCount > 0) {
                messageDiv.innerHTML = `<div class="success">Применено фильтров: ${appliedFiltersCount}</div>`;
            } else {
                messageDiv.innerHTML = `<div class="success">Фильтры очищены</div>`;
            }
        }
        
        // Функция для очистки фильтров
        function clearFilters() {
            currentFilters = {};
            
            for (const [fieldCode, filterInfo] of Object.entries(filtersData)) {
                if (filterInfo.vals) {
                    if (filterInfo.multiple) {
                        // Снимаем выделение со всех чекбоксов
                        const checkboxes = document.querySelectorAll(`#options_${fieldCode} input[type="checkbox"]`);
                        checkboxes.forEach(checkbox => checkbox.checked = false);
                        
                        // Очищаем выбранные значения
                        const selectedContainer = document.getElementById(`selected_${fieldCode}`);
                        if (selectedContainer) {
                            selectedContainer.innerHTML = '';
                        }
                        
                        // Очищаем поиск
                        const searchInput = document.getElementById(`search_${fieldCode}`);
                        if (searchInput) {
                            searchInput.value = '';
                            filterOptions(fieldCode); // Показываем все опции
                        }
                    } else {
                        // Сбрасываем на пустое значение
                        const element = document.getElementById(`filter_${fieldCode}`);
                        if (element) {
                            element.value = '';
                        }
                        
                        // Очищаем поиск и выбранное значение для одиночных списков с поиском
                        const searchInput = document.getElementById(`search_${fieldCode}`);
                        if (searchInput) {
                            searchInput.value = '';
                            filterSingleOptions(fieldCode); // Показываем все опции
                        }
                        const header = document.querySelector(`#dropdown_${fieldCode} .dropdown-text`);
                        if (header) {
                            header.textContent = 'Выберите значение...';
                            header.removeAttribute('data-value');
                        }
                        
                        // Закрываем выпадающий список
                        const content = document.getElementById(`content_${fieldCode}`);
                        const arrow = document.querySelector(`#dropdown_${fieldCode} .dropdown-arrow`);
                        if (content && arrow) {
                            content.style.display = 'none';
                            arrow.style.transform = 'rotate(0deg)';
                        }
                    }
                } else if (filterInfo.type === 'string') {
                    // Очищаем текстовое поле
                    const element = document.getElementById(`filter_${fieldCode}`);
                    if (element) {
                        element.value = '';
                    }
                }
            }
            
            messageDiv.innerHTML = `<div class="success">Фильтры очищены</div>`;
        }
        
        // Функция для начала загрузки оборотов
        function startRevenueUpload() {
            startBtn.disabled = true;
            startBtn.textContent = 'Подготовка...';
            messageDiv.innerHTML = '';
            
            // Скрываем кнопку паузы и сбрасываем состояние
            pauseBtn.style.display = 'none';
            isPaused = false;
            
            const formData = new FormData();
            formData.append('user_id', userId);
            
            // Получаем значение чекбокса
            const updateExistingCompanies = document.getElementById('updateExistingCompanies').checked;
            formData.append('update_existing_companies', updateExistingCompanies ? '1' : '0');
            
            // Добавляем фильтры
            formData.append('filters', JSON.stringify(currentFilters));
            
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
        
        // Функция для фильтрации опций по поиску (множественный выбор)
        function filterOptions(fieldCode) {
            const searchInput = document.getElementById(`search_${fieldCode}`);
            const searchTerm = searchInput.value.toLowerCase();
            const options = document.querySelectorAll(`#options_${fieldCode} .multi-select-option`);
            
            options.forEach(option => {
                const label = option.querySelector('label').textContent.toLowerCase();
                if (label.includes(searchTerm)) {
                    option.classList.remove('hidden');
                } else {
                    option.classList.add('hidden');
                }
            });
        }
        
        // Функция для фильтрации опций по поиску (одиночный выбор)
        function filterSingleOptions(fieldCode) {
            const searchInput = document.getElementById(`search_${fieldCode}`);
            const searchTerm = searchInput.value.toLowerCase();
            const options = document.querySelectorAll(`#options_${fieldCode} .single-select-option`);
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    option.classList.remove('hidden');
                } else {
                    option.classList.add('hidden');
                }
            });
        }
        
        // Функция для переключения выпадающего списка (множественный выбор)
        function toggleDropdown(fieldCode) {
            const content = document.getElementById(`content_${fieldCode}`);
            const arrow = document.querySelector(`#dropdown_${fieldCode} .dropdown-arrow`);
            
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                arrow.style.transform = 'rotate(180deg)';
                // Фокусируемся на поле поиска
                setTimeout(() => {
                    document.getElementById(`search_${fieldCode}`).focus();
                }, 100);
            } else {
                content.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Функция для переключения выпадающего списка (одиночный выбор)
        function toggleSingleDropdown(fieldCode) {
            const content = document.getElementById(`content_${fieldCode}`);
            const arrow = document.querySelector(`#dropdown_${fieldCode} .dropdown-arrow`);
            
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                arrow.style.transform = 'rotate(180deg)';
                // Фокусируемся на поле поиска
                setTimeout(() => {
                    document.getElementById(`search_${fieldCode}`).focus();
                }, 100);
            } else {
                content.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Функция для выбора одиночной опции
        function selectSingleOption(fieldCode, value, title) {
            const header = document.querySelector(`#dropdown_${fieldCode} .dropdown-text`);
            const content = document.getElementById(`content_${fieldCode}`);
            const arrow = document.querySelector(`#dropdown_${fieldCode} .dropdown-arrow`);
            
            header.textContent = title;
            header.setAttribute('data-value', value);
            content.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
            
            // Сохраняем выбранное значение
            if (!currentFilters[fieldCode]) {
                currentFilters[fieldCode] = {};
            }
            currentFilters[fieldCode] = value;
        }
        
        // Функция для переключения выбора опции
        function toggleOption(fieldCode, value, title) {
            const checkbox = document.getElementById(`option_${fieldCode}_${value}`);
            const selectedValuesContainer = document.getElementById(`selected_${fieldCode}`);
            
            if (checkbox.checked) {
                // Убираем из выбранных
                checkbox.checked = false;
                const tagToRemove = selectedValuesContainer.querySelector(`[data-value="${value}"]`);
                if (tagToRemove) {
                    tagToRemove.remove();
                }
            } else {
                // Добавляем к выбранным
                checkbox.checked = true;
                const tag = document.createElement('div');
                tag.className = 'selected-tag';
                tag.setAttribute('data-value', value);
                tag.innerHTML = `
                    ${title}
                    <span class="remove-tag" onclick="removeSelectedOption('${fieldCode}', '${value}', '${title}')">&times;</span>
                `;
                selectedValuesContainer.appendChild(tag);
            }
        }
        
        // Функция для удаления выбранной опции
        function removeSelectedOption(fieldCode, value, title) {
            const checkbox = document.getElementById(`option_${fieldCode}_${value}`);
            const selectedValuesContainer = document.getElementById(`selected_${fieldCode}`);
            
            checkbox.checked = false;
            const tagToRemove = selectedValuesContainer.querySelector(`[data-value="${value}"]`);
            if (tagToRemove) {
                tagToRemove.remove();
            }
        }
        
        // Функция для переключения показа/скрытия фильтров
        function toggleFilters() {
            const filtersContainer = document.getElementById('filtersContainer');
            const toggleBtn = document.getElementById('toggleFiltersBtn');
            const toggleText = document.getElementById('toggleFiltersText');
            const toggleIcon = document.getElementById('toggleFiltersIcon');
            if (filtersContainer.style.display === 'none' || filtersContainer.style.display === '') {
                // Показываем фильтры
                filtersContainer.style.display = 'block';
                setTimeout(() => {
                    filtersContainer.classList.add('show');
                }, 10);
                toggleText.textContent = 'Скрыть фильтры';
                toggleIcon.textContent = '▲';
                toggleBtn.classList.remove('filters-hidden');
            } else {
                // Скрываем фильтры
                filtersContainer.classList.remove('show');
                setTimeout(() => {
                    filtersContainer.style.display = 'none';
                }, 300);
                toggleText.textContent = 'Показать фильтры';
                toggleIcon.textContent = '▼';
                toggleBtn.classList.add('filters-hidden');
            }
        }
        
        // Загружаем фильтры при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadFilters();
            
            // Закрываем выпадающие списки при клике вне их
            document.addEventListener('click', function(event) {
                const dropdowns = document.querySelectorAll('.multi-select-dropdown, .single-select-dropdown');
                dropdowns.forEach(dropdown => {
                    if (!dropdown.contains(event.target)) {
                        const content = dropdown.querySelector('.multi-select-content, .single-select-content');
                        const arrow = dropdown.querySelector('.dropdown-arrow');
                        if (content && arrow) {
                            content.style.display = 'none';
                            arrow.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
