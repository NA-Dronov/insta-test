<?php

namespace App\Core;

use Exception;

trait TViewable
{
    public function view($view, $data = [])
    {
        $layoutPath = VIEWS_ROOT . DIRECTORY_SEPARATOR . 'layout.php';
        $layoutCheck = file_exists($layoutPath);
        $view = str_replace('.php', '', $view);
        $view = str_replace(['.', '/', '\\'], DIRECTORY_SEPARATOR, $view);
        $viewPath = VIEWS_ROOT . DIRECTORY_SEPARATOR . $view . '.php';
        $viewCheck = file_exists($viewPath);

        if (!$layoutCheck) {
            throw new Exception("The requested view " . $layoutPath . " not found");
        }

        if (!$viewCheck) {
            throw new Exception("The requested view " . $viewPath . " not found");
        }

        include($layoutPath);
        die();
    }
}
