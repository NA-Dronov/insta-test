<?php

namespace App\Commands;

use App\Core\APIException;
use DateTime;
use App\Core\GetCommand;
use App\Core\TViewable;
use App\Models\Currency;

class HomeCommand extends GetCommand
{
    use TViewable;

    private $error = "";

    protected function processRequest()
    {
        $params = [
            'per_page' => 25,
            'sort_by' => 'name',
            'sort_order' => 'ASC',
        ];

        if (!empty($_GET['page'])) {
            $params['page'] = $_GET['page'];
        }

        $current_date = new DateTime();
        $current_date->setTime(0, 0);
        $params['from'] = !empty($_GET['date']) ? $_GET['date'] : $current_date->format('d/m/Y');
        $params['to'] = !empty($_GET['date']) ? $_GET['date'] : $current_date->format('d/m/Y');

        $c = new Currency();
        list($result['data'], $result['params']) = $c->get('*', $params);

        $d = new DateTime();
        foreach ($result['data'] as $currency) {
            $currency->date = $d->setTimestamp($currency->date)->format('d/m/Y');
        }

        $this->view('home', $result);
    }

    protected function processError()
    {
        throw new APIException("The required resource not found", 1);
    }
}
