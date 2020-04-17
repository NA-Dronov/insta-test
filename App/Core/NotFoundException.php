<?php

namespace App\Core;

use Exception;

class NotFoundException extends Exception
{
    use TViewable;

    protected $httpResponseCode = 404;

    public function getResponseCode()
    {
        return $this->httpResponseCode;
    }
}
