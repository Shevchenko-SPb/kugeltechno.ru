<?php
// Инициализация Битрикса и авторизация
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Подключаем автолоадер для всех классов из lib
require_once 'autoloader.php';
require_once 'Settings.php';

use ImportCompaniesFromDaDaTa\lib\Company;

$ids = [222678];
//$ids = [218672];
$Company = new Company();
$rs = $Company->uploadRevenue($ids);
echo '<pre>';
print_r($rs);
echo '</pre>';