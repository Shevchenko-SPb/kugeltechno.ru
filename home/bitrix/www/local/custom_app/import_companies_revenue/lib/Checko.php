<?php

use ImportCompaniesRevenue\Settings;
class Checko
{
    function getDataFromChecko($INN): mixed
    {
        $api_key = Settings::CHECKO_API_KEY;
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