<?php

namespace App\Core;

abstract class PostCommand extends ACommand
{
    public final function getMethod(): string
    {
        return "POST";
    }
}
