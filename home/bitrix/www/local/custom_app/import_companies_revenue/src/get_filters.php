<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once '../autoloader.php';

use ImportCompaniesRevenue\lib\Filter;

// Проверяем авторизацию
global $USER;
if (!$USER->IsAuthorized()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

try {
    $filter = new Filter();
    $filters = $filter->getFilters();
    
    echo json_encode([
        'success' => true,
        'data' => $filters
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при получении фильтров: ' . $e->getMessage()
    ]);
}
