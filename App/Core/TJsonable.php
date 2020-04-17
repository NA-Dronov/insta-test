<?php

namespace App\Core;

trait TJsonable
{
    public function json($data = [])
    {
        header('Content-type: application/json');
        echo json_encode($data);
        die();
    }
}
