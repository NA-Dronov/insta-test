<?php
define('APP_KEY', 'DJIwRSa9');
define('APP_NAME', 'Курс Валют');
define('DATABASE_CONFIG', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.xml');
define('APP_ROOT', dirname(dirname(__FILE__)));
define('VIEWS_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'views');
define('COMMANDS_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Commands');
define('COMMANDS_NS', "\App\Commands\\");
define('URL_ROOT', 'http://localhost/insta/');

spl_autoload_register(function ($class) {
    require_once(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class) . ".php");
});
