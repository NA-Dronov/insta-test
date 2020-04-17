<?php

namespace App\Core;

use Exception;

class APIException extends Exception
{
    use TJsonable;
}
