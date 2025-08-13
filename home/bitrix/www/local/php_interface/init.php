<?php
// Подключаем класс с константами
require_once('Consts.php');
// region Company

AddEventHandler("crm", "OnAfterCrmCompanyUpdate", "OnAfterCompanyUpdate");
require_once ('cust_events/onAfter/crm/OnAfterCompanyUpdate.php');

AddEventHandler("crm", "OnBeforeCrmCompanyUpdate", "OnBeforeCompanyUpdate");
require_once ('cust_events/onBefore/crm/OnBeforeCompanyUpdate.php');

// endregion
