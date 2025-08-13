<?php
namespace ImportCompaniesRevenue\lib;

use Bitrix\Crm\Service\Container;
use ImportCompaniesRevenue\Settings;
class Company
{
    public $companyFactory;
    public $companyUFs;

    function __construct()
    {
        $this->companyFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $this->companyUFs = $this->companyFactory->getUserFieldsInfo();
    }
    public function uploadRevenue($companyIds)
    {
        $countErrors = 0;
        $countUpdatedInn = 0;
        $countUpdatedRevenue = 0;
        $countRevenueNotFound = 0;
        $countInnNotFound = 0;
        $errorsLog = [];

        foreach ($companyIds as $companyId)
        {
            $company = $this->companyFactory->getItem($companyId);
            if(!$company) {
                $countErrors++;
                $errorsLog[] = "Не удалось получить компанию с ID: {$companyId}";
                continue;
            }
            $inn = $company->get(Settings::UF_COMPANY_INN);
            // Если ИНН не найден, то пытаемся найти его по названию компании
            if(!$inn) {
                $title = $company->getTitle();
                if(str_contains($title, 'ИНН:')) {
                    $arTitle = explode('ИНН:', $title);
                    $inn = (int)$arTitle[1];
                }
            }
            if(!$inn) {
                $countInnNotFound++;
                $errorsLog[] = "Не найден ИНН для компании с ID: {$companyId}";
                continue;
            }
            if($inn == '1234567890') {
                $countInnNotFound++;
                $errorsLog[] = "ИНН 1234567890 для компании с ID: {$companyId} Загрузка прервана";
                continue;
            }
            $Checko = new \Checko();
            $res_checko = $Checko->getDataFromChecko($inn);
            if ($res_checko['meta']['status'] == 'error') {
                $countErrors++;
                $errorsLog[] = "Ошибка при загрузке данных из Checko для компании с ID: {$companyId}. Сообщение: \"{$res_checko['meta']['message']}\"";
                continue;
            }
            if (!$res_checko['data']) { // Если в ответе нет данных о компании
                $countRevenueNotFound++;
                $errorsLog[] = "Данных об оборотах не найдено для компании с ID: {$companyId}";
                continue;
            }
            $arData = $res_checko['data'];
            $arAmountForLast3Year = [' ', ' ', ' '];
            $counter = 0;
            foreach ($arData as $YEAR => $arItem) {
                $AMOUNT = intval($arItem['2110']);
                $arAmountForLast3Year[$counter] = round($AMOUNT / 1000000);
                $counter++;
            }
            $arAmountForLast3Year = array_slice($arAmountForLast3Year, -3);

            $arAmountFormated = [];
            foreach ($arAmountForLast3Year as $num) {
                $arAmountFormated[] = $num ? number_format($num, 0, '', ' ') : '';
            }
            $lastYearAmount = (int)array_pop($arAmountForLast3Year);

            if($arAmountFormated) {
                $company->set(Settings::UF_COMPANY_3_YEARS_REVENUE, implode(" || ", $arAmountFormated));
            }
            $company->set(Settings::UF_COMPANY_INN, $inn);
            if($lastYearAmount) {
                $company->set(Settings::UF_COMPANY_LAST_YEAR_REVENUE, $lastYearAmount);
            }
            $result = $company->save();
            if ($result->isSuccess()) {
                $countUpdatedRevenue++;
            } else {
                $countErrors++;
                $errorsLog[] = ["Ошибка обновление компании id $companyId" => $result->getErrorMessages()];
            }
        }

        return [
            'companies_updated_inn' => $countUpdatedInn, // Обновлено ИНН у компаний
            'companies_inn_not_found' => $countInnNotFound, // Не найдено ИНН у компании
            'companies_updated_revenue' => $countUpdatedRevenue, // Количество компаний у которых обновлены данные об обороте
            'companies_revenue_not_found' => $countRevenueNotFound, // Количество компаний у которых не найдено данных об обороте
            'errors' => $countErrors, // Количество ошибок
            'errors_log' => $errorsLog, // Описание ошибок
        ];
    }
}