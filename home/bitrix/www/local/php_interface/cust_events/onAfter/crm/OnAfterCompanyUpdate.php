<?php

use php_interface\cust_events\onAfter\crm\entity\Company;

require_once 'entity/Company.php';
function OnAfterCompanyUpdate(&$arFields)
{
    $Company = new Company($arFields);

    // Обновляем данные из сервиса DaDaTa
    $Company->updateDataFromDaDaTaAndChecko();


    // Удаляем старые поля компании из сессии
    if(isset($_SESSION[\Consts::SESSION_KEY_OLD_FIELDS_COMPANY])) {
        unset($_SESSION[\Consts::SESSION_KEY_OLD_FIELDS_COMPANY]);
    }
}
