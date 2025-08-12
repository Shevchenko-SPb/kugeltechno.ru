<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

require_once 'autoloader.php';
global $USER;
$isAdmin = $USER->IsAdmin();
$userId = $USER->GetID();

// Подключаем AuthController и выполняем авторизацию
require_once 'lib/AuthController.php';

AuthController::login($userId);

$Checko = new Checko();

$BitrixController = new BitrixController();


//$arIds = $BitrixController::getAllCompaniesIds();
$arIds = [222617]; // test


echo '<pre>';
print_r($BitrixController::actionUploadCompaniesRevenue($arIds));
echo '</pre>';
