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

    public static function getAllCompaniesIds()
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

        $companies = $factory->getItems(
            [
                'select' => ['ID'],
            ]);
        $arCompaniesIds = [];
        foreach ($companies as $company) {
            $arCompaniesIds[] = $company->getId();
        }
        return $arCompaniesIds;
    }
}