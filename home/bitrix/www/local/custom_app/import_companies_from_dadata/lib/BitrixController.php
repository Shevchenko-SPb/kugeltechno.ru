<?php
use Bitrix\Crm\Service\Container;

use ImportCompaniesFromDaDaTa\Settings;
use ImportCompaniesFromDaDaTa\lib\Company;
class BitrixController
{
    public static function actionUploadCompaniesRevenue ($arIds)
    {
        $Company = new Company();
        return $Company->uploadDaDaTa($arIds);
    }

    public static function getAllCompaniesIds($updateOnlyNewCompanies = true, $filters = [])
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

        $filterConditions = [];

        if($updateOnlyNewCompanies) {
            $filterConditions[Settings::UF_COMPANY_IS_CHECK_DA_DA_TA] = false;
        }

        // Добавляем пользовательские фильтры
        if (!empty($filters)) {
            foreach ($filters as $fieldCode => $value) {
                if (empty($value)) continue;
                
                // Обработка множественных значений
                if (is_array($value)) {
                    if (!empty($value)) {
                        $filterConditions[$fieldCode] = $value;
                    }
                } else {
                    // Обработка строковых значений
                    $filterConditions[$fieldCode] = $value;
                }
            }
        }

        if (!empty($filterConditions)) {
            $params['filter'] = $filterConditions;
        }

        $companies = $factory->getItems($params);
        $arCompaniesIds = [];
        foreach ($companies as $company) {
            $arCompaniesIds[] = $company->getId();
        }
//        file_put_contents(__DIR__.'/$filters.txt', print_r($filters, 1));
//        file_put_contents(__DIR__.'/$params.txt', print_r($params, 1));
//        file_put_contents(__DIR__.'/$arCompaniesIds.txt', print_r($arCompaniesIds, 1));
//        return [];

        return $arCompaniesIds;
    }
}