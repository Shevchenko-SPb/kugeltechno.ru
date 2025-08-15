<?php

use ImportCompaniesFromDaDaTa\Settings;
class DaDaTa
{
    function getDataFromDaDaTa($inn): mixed
    {
        $api_key = Settings::DA_DA_TA_API_KEY;
        $curl = curl_init();
        $postData = [
            "query" => $inn
        ];
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_COOKIE => "BITRIX_SM_SALE_UID=3",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: Token $api_key",
                "X-Secret: 5100586fffc546ac80ad172215073bc285836491"
            ],
        ]);
        return json_decode(curl_exec($curl), true);
    }
}