<?php

namespace App\Core;

trait TSingleton
{
    private static $instance = null;

    public static function getInstance()
    {
        return static::$instance ?? (static::$instance = new self());
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
