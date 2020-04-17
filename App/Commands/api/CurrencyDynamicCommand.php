<?php

namespace App\Commands\api;

use App\Core\APIException;
use DateTime;
use App\Core\GetCommand;
use App\Core\TJsonable;
use App\Models\Currency;
use Throwable;

class CurrencyDynamicCommand extends GetCommand
{
    use TJsonable;

    private $error = "";

    protected function processRequest()
    {
        try {
            $params = [];

            if (!empty($_GET['from'])) {
                $params['from'] = $_GET['from'];
            }

            if (!empty($_GET['to'])) {
                $params['to'] = $_GET['to'];
            }

            if (!empty($_GET['valueID'])) {
                $params['valueID'] = $_GET['valueID'];
            } else {
                $this->processError();
            }

            $c = new Currency();
            list($result,) = $c->get('*', array_merge($params, ['assoc' => true]));

            array_walk($result, function (&$v, $k) {
                $date = new DateTime();
                $v['date'] = $date->setTimestamp($v['date'])->format("d/m/Y");
            });

            $this->json($result);
        } catch (Throwable $ex) {
            $this->error = $ex->getMessage();
            $this->processError();
        }
    }

    protected function processError()
    {
        throw new APIException("The required resource not found", 1);
    }
}
