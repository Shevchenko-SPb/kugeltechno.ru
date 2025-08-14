<?php
if(file_exists($_SERVER['DOCUMENT_ROOT']."/local/php_interface/Consts.php")){
    require_once($_SERVER['DOCUMENT_ROOT']."/local/php_interface/Consts.php");
}
use Bitrix\Main\Loader;
Loader::includeModule('crm');

$rootActivity = $this->GetRootActivity();
$arCompanies = $rootActivity->GetVariable("AR_COMPANIES");
class List_get_total_revenue
{
    function get($arCompanies)
    {
        if(!$arCompanies) {
            return '';
        }
        $instance = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $instance->getFactory(\CCrmOwnerType::Company);
        $arUserFields = $factory->getUserFieldsInfo();
        if(!isset($arUserFields[Consts::COMPANY_UF_LAST_YEAR_REVENUE])) {
            return '';
        }
        $totalRevenue = 0;
        foreach ($arCompanies as $companyId) {
            if(!$companyId) {
                continue;
            }
            $company = $factory->getItem($companyId);
            if($company) {
                $val = $company->get(Consts::COMPANY_UF_LAST_YEAR_REVENUE);
                if($val) {
                    $totalRevenue += intval($val);
                }
            }
        }
        if($totalRevenue == 0) {
            return '';
        }
        return number_format($totalRevenue, 0, '', ' ');
    }
}
$List_get_total_revenue = new List_get_total_revenue;
$revenue = $List_get_total_revenue->get($arCompanies);
$rootActivity->SetVariable("TOTAL_REVENUE", $revenue);
