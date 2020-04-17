<?php

namespace App\Core;

abstract class ACommand
{
    protected abstract function processRequest();
    protected abstract function processError();

    public function execute()
    {
        if ($_SERVER['REQUEST_METHOD'] == $this->getMethod()) {
            $this->processRequest();
        } else {
            $this->processError();
        }
    }

    public abstract function getMethod(): string;
}
