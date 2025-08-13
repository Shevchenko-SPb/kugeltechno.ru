<?php
namespace php_interface\cust_events\onAfter\crm\entity;
class Company
{
    public $fields;

    function __construct($arFields)
    {
        $this->fields = $arFields;
    }

    function updateDataFromDaDaTaAndChecko()
    {
        $newFields = $this->fields;
        $oldFields = null;
        if(isset($_SESSION[\Consts::SESSION_KEY_OLD_FIELDS_COMPANY])) {
            $oldFields = unserialize($_SESSION[\Consts::SESSION_KEY_OLD_FIELDS_COMPANY]);
        }
        if(!$oldFields) {
            // Старые значения не найдены
            return;
        }
        if($newFields['ID'] != $oldFields['ID']) {
            // В сессии хранится информация о другой компании
            return;
        }
        if(!$newFields[\Consts::COMPANY_UF_CRM_INN]) {
            // Значение ИНН не заполнено
            return;
        }
        if($newFields[\Consts::COMPANY_UF_CRM_INN] != $oldFields[\Consts::COMPANY_UF_CRM_INN]) {
            // Номер ИНН изменился - обновляем данные из ДАДАТА и CHECKO
            $companyId = $newFields['ID'];
            $INN = $newFields[\Consts::COMPANY_UF_CRM_INN];
            $this->updateDataFromChecko($companyId, $INN);
        }
    }
    function updateDataFromChecko($companyId, $INN)
    {
        $res_checko = $this->getDataFromChecko($INN);
        if ($res_checko['meta']['status'] == 'error') {
            $res_checko['INN'] = $INN;
            $res_checko['DATE'] = date("Y-m-d H:i:s");
            file_put_contents(__DIR__.'/log/checko_error.txt', print_r($res_checko ,1));
            return;
        }
        if (!$res_checko['data']) { // Если в ответе нет данных о компании
            $res_checko['INN'] = $INN;
            $res_checko['DATE'] = date("Y-m-d H:i:s");
            file_put_contents(__DIR__.'/log/checko_error_data.txt', print_r($res_checko ,1));
            return;
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

        $instance = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $instance->getFactory(\CCrmOwnerType::Company);
        $UFs = $factory->getUserFieldsInfo();
        $company = $factory->getItem($companyId);

        // Проверяем на наличие пользовательских полей
        if($UFs[\Consts::COMPANY_UF_3_YEARS_REVENUE]) {
            if($arAmountFormated) {
                $company->set(\Consts::COMPANY_UF_3_YEARS_REVENUE, implode(" || ", $arAmountFormated));
            }
        }
        if($lastYearAmount) {
            if($UFs[\Consts::COMPANY_UF_LAST_YEAR_REVENUE]) {
                $company->set(\Consts::COMPANY_UF_LAST_YEAR_REVENUE, $lastYearAmount);
            }

        }
        $company->save();
    }
    function getDataFromChecko($INN): mixed
    {
        $api_key = \Consts::CHECKO_API_KEY;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.checko.ru/v2/finances?key=" . $api_key . "&inn=" . $INN,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);
        $err = curl_error($curl);
        $response = json_decode(curl_exec($curl), true);
        unset($curl);
        if (!$err) {
            return $response;
        } else return $err;
    }
}
