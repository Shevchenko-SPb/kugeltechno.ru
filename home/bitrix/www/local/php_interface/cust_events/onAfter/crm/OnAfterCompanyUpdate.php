<?php
require_once 'entity/Company.php';
function OnAfterCompanyUpdate(&$arFields)
{
    $Company = new Company($arFields);

    // Обновляем данные из сервиса DaDaTa
    $Company->updateDataFromDaDaTa();
}
