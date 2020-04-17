<?php

namespace App\DataProviders;

use DateTime;
use App\Core\TSingleton;

class CBApiOperator
{
    use TSingleton;

    protected function daily(DateTime $date): array
    {
        $url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req={$date->format('d/m/Y')}";

        $res = $this->fetch($url);

        $timestamp = $date->getTimestamp();

        $currency_list = [];

        foreach ($res as $key => $valuta) {
            $ID = (string) $valuta->attributes()['ID'] ?? '';
            if (empty($ID)) {
                continue;
            }

            $data = [
                'ID' => $ID
            ];

            foreach ($valuta as $f_name => $f_value) {
                $data[$f_name] = (string) $f_value;
            }

            $data['date'] = $timestamp;

            $currency_list[$ID] = $data;
        }

        return  $currency_list;
    }

    protected function dynamic(DateTime $date_from, DateTime $date_to, string $valuta_id)
    {
        $params = [
            'date_req1' => $date_from->format('d/m/Y'),
            'date_req2' => $date_to->format('d/m/Y'),
            'VAL_NM_RQ' => $valuta_id
        ];

        $query_params = [];
        foreach ($params as $k => $v) {
            $query_params[] = "{$k}={$v}";
        }

        $query_params = implode("&", $query_params);

        $url = "http://www.cbr.ru/scripts/XML_dynamic.asp?{$query_params}";

        $res = $this->fetch($url);

        $dynamic_list = [];

        foreach ($res as $key => $valuta) {
            $ID = (string) $valuta->attributes()['Id'] ?? '';
            if (empty($ID)) {
                continue;
            }

            $date = DateTime::createFromFormat('d.m.Y', $valuta->attributes()['Date'] ?? '');

            if ($date === false) {
                continue;
            }

            $date->setTime(0, 0);

            $dynamic_list[$ID][] = [
                'date' =>  $date->getTimestamp(),
                'Value' => (string) $valuta->Value
            ];
        }

        return $dynamic_list;
    }

    protected function valuta()
    {
        $url = "http://www.cbr.ru/scripts/XML_val.asp";

        $res = $this->fetch($url);

        $valuta_list = [];

        foreach ($res as $valuta) {
            $ID = (string) $valuta->attributes()['ID'] ?? '';
            $ParentCode = (string) $valuta->ParentCode ?? '';
            if (empty($ID) || empty($ParentCode)) {
                continue;
            }
            $valuta_list[$ID] = [
                'ID' => $ID,
                'ParentCode' => $ParentCode
            ];
        }

        return $valuta_list;
    }

    protected function fetch($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        //$response = iconv('WINDOWS-1251', 'UTF-8', $response);
        $response = simplexml_load_string($response);

        curl_close($curl);
        return $response;
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::getInstance(), $name], $arguments);
    }
}
