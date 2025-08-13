<?php

use php_interface\cust_events\onBefore\crm\entity\Company;

require_once 'entity/Company.php';
function OnBeforeCompanyUpdate(&$arFields)
{
    $Company = new Company($arFields);

    // Обновляем данные из сервиса DaDaTa
    $Company->addFieldsInSession();
}
