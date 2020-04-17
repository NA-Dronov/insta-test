<?php

namespace App\Core;

abstract class GetCommand extends ACommand
{
    public final function getMethod(): string
    {
        return "GET";
    }
}
