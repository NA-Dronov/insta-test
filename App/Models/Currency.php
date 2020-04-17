<?php

namespace App\Models;

class Currency extends AModel
{
    protected $aliases = [
        'valuteID' => 'ID',
        'numCode' => 'NumCode',
        'charCode' => 'CharCode',
        'name' => 'Name',
        'value' => 'Value',
        'date' => 'date',
    ];

    public $currency_id;
    public $valuteID;
    public $numCode;
    public $charCode;
    public $name;
    public $value;
    public $date;
}
