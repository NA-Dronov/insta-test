<?php

namespace App\Commands;

use DateTime;
use App\Core\GetCommand;
use App\Core\Config;
use App\Core\NotFoundException;
use App\Core\TViewable;
use App\DataProviders\CBApiOperator;
use App\DataProviders\DatabaseOperator;
use App\Models\Currency;

class SeedCommand extends GetCommand
{
    use TViewable;

    protected function processRequest()
    {
        if (isset($_GET['key']) && $_GET['key'] == APP_KEY) {
            /**
             * @var Config $gc
             */
            $gc = Config::getInstance();

            foreach ($gc->get('db.init_quieries') as $query) {
                DatabaseOperator::getInstance()->rawOperation($query);
            }

            $valuta_list = CBApiOperator::valuta();

            $current_date = new DateTime();
            $start_date = new DateTime();
            $start_date->modify('-1 month');

            /**
             * @var Currency $value 
             */
            $valutes_insert_data = [];
            $currencies_data = [];
            foreach ($valuta_list as $index => $valuta) {
                $valuta_id = $valuta['ID'];
                $dynamic_list = CBApiOperator::dynamic($start_date, $current_date, $valuta_id);
                if (!empty($dynamic_list)) {
                    $dynamic_list_key = array_key_first($dynamic_list);
                    $valuta_id = $valuta_id == $dynamic_list_key ? $valuta_id : $dynamic_list_key;
                    $date = new DateTime();

                    if (empty($valutes_insert_data[$valuta_id])) {
                        $daily = CBApiOperator::daily($date->setTimestamp($dynamic_list[$valuta_id][0]['date']));

                        foreach ($daily as $id => $data) {
                            if (empty($valutes_insert_data[$id])) {
                                $valutes_insert_data[$id] = $data;
                                unset($valutes_insert_data[$id]['Value']);
                                unset($valutes_insert_data[$id]['date']);
                            }
                        }
                    }

                    if (!empty($dynamic_list[$valuta_id]) && !isset($valutes_insert_data[$valuta_id]['completed'])) {
                        $valutes_insert_data[$valuta_id]['completed'] = true;
                        foreach ($dynamic_list[$valuta_id] as $dynamic_value) {
                            $data = array_merge($valutes_insert_data[$valuta_id], $dynamic_value);
                            $currencies_data[] = new Currency($data);
                        }
                    }
                }
            }

            usort($currencies_data, function ($record1, $record2) {
                if ($record1->date == $record2->date) return 0;
                return ($record1->date > $record2->date) ? -1 : 1;
            });

            array_walk($currencies_data, function ($v, $k) {
                /**
                 * @var Currency $v
                 */
                $v->insert();
            });

            $this->view('seed/success');
        } else {
            $this->processError();
        }
    }

    protected function processError()
    {
        throw new NotFoundException("The required resource not found", 1);
    }
}
