<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
Loader::includeModule('crm');

$factory = Container::getInstance()->getFactory(CCrmOwnerType::Company);
$params = [
    'select' => ['ID'],
    'filter' => [
        'UF_CRM_1754991065' => false
    ]
];
$companies = $factory->getItems($params);
$arCompaniesIds = [];
foreach ($companies as $company) {
    $arCompaniesIds[] = $company->getId();
}
echo '<pre>';
print_r($arCompaniesIds);
echo '</pre>';





return;

$AMOUNT = 452000;
$num = round($AMOUNT / 1000000);
$res = $num ? number_format($num, 0, '', ' ') : '';
echo $res;

return;
$instance = \Bitrix\Crm\Service\Container::getInstance();
$factory = $instance->getFactory(\CCrmOwnerType::Company);
$company = $factory->getItem(222617);
$arUserFields = $factory->getUserFieldsInfo();


$num = 14520000;
if(strlen($num) >= 7) {
    $num = round((int)$num / 100000);
    $lastNum = substr($num, -1);
    if($lastNum >= 0 && $lastNum <= 4) {
        $num = round((int)$num / 10);
    }
    if($lastNum >= 5 && $lastNum <= 9) {
        echo (int)$num / 10;
        $num = round((int)$num / 10);
    }

    echo '<pre>';
    print_r($num);
//print_r($company->getData());
    echo '</pre>';
}
