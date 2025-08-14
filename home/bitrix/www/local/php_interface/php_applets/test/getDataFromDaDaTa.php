<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

class DaDaTaApi
{
    function getDataByINN($inn)
    {
//        $curl = curl_init();
//        $postData = [
//            "query" => $inn
//        ];
//        curl_setopt_array($curl, [
//            CURLOPT_URL => "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_SSL_VERIFYPEER => 0,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => json_encode($postData),
//            CURLOPT_COOKIE => "BITRIX_SM_SALE_UID=3",
//            CURLOPT_HTTPHEADER => [
//                "Content-Type: application/json",
//                "Accept: application/json",
//                "Authorization: Token a97c3ecf0f83c1851d2605046b0e64531d703952",
//                "X-Secret: 5100586fffc546ac80ad172215073bc285836491"
//            ],
//        ]);
////    $err = curl_error($curl);
//        $response = json_decode(curl_exec($curl), true);
//
//        return $response;
    }

    function getFoFromChecko($INN): mixed
    {
        $api_key = '1CXWAe7BkhMmkZba';
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

    function getData($INN)
    {
        $arData = $this->getFoFromChecko($INN)['data'];
        $arAmountForLast3Year = [' ', ' ', ' '];
        $counter = 0;
        foreach ($arData as $YEAR => $arItem) {
            $AMOUNT = intval($arItem['2110']);
//            $arAmountForLast3Year[$counter] = round($AMOUNT / 1000000);
            $arAmountForLast3Year[$counter] = round($AMOUNT);
            $counter++;
            $RATE = NULL;
            if ($LastYearAmount !== NULL) {
                if ($LastYearAmount !== 0)
                    $RATE = round($AMOUNT / $LastYearAmount * 100);
            }
            $LastYearAmount = $AMOUNT;
        }
        $arAmountForLast3Year = array_slice($arAmountForLast3Year, -3);
        return $arAmountForLast3Year;
    }
}

$DaDaTAApi = new DaDaTAapi();
$result = $DaDaTAApi->getData("5254003100");
//$arCompanies = $result['suggestions'];
//$rs = [];
//foreach ($arCompanies as $key => $value) {
//    $rs[$key]['Название'] = $value['value'];
//    $rs[$key]['Название полное'] = $value['data']['name']['full_with_opf'];
//    $rs[$key]['ИНН'] = $value['data']['inn'];
//    $rs[$key]['КПП'] = $value['data']['kpp'];
//    $rs[$key]['Финансы'] = $value['data']['finance'];
//}
echo '<pre>';
print_r($result);
echo '</pre>';