<?php
use Bitrix\Crm\Service\Container;

use ImportCompaniesRevenue\Settings;
use ImportCompaniesRevenue\lib\Company;
class BitrixController
{
    public static function actionUploadCompaniesRevenue ($arIds)
    {
        $Company = new Company();
        return $Company->uploadRevenue($arIds);
    }

    public static function getAllCompaniesIds($updateOnlyNewCompanies = true)
    {
        $factory = Container::getInstance()->getFactory(CCrmOwnerType::Company);

        // Первоначальная проверка наличия пользовательских полей. Если такие поля не найдена, то загрузка прерывается
        $UFs = $factory->getUserFieldsInfo();
        if(!$UFs[Settings::UF_COMPANY_INN]) {
            return ['alert_error' => 'Отсутствует пользовательское поле ' . Settings::UF_COMPANY_INN];
        }
        if(!$UFs[Settings::UF_COMPANY_3_YEARS_REVENUE]) {
            return ['alert_error' => 'Отсутствует пользовательское поле ' . Settings::UF_COMPANY_3_YEARS_REVENUE];
        }
        if(!$UFs[Settings::UF_COMPANY_LAST_YEAR_REVENUE]) {
            return ['alert_error' => 'Отсутствует пользовательское поле ' . Settings::UF_COMPANY_LAST_YEAR_REVENUE];
        }

        $params = [
            'select' => ['ID'],
        ];

        if($updateOnlyNewCompanies) {
            $params['filter'] = [
                Settings::UF_COMPANY_3_YEARS_REVENUE => false
            ];
        }

        $companies = $factory->getItems($params);
        $arCompaniesIds = [];
        foreach ($companies as $company) {
            $arCompaniesIds[] = $company->getId();
        }
        return $arCompaniesIds;
    }
}