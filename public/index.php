<?php

require_once("../Config/bootstrap.php");

use App\Core\Controller;

$c = Controller::getInstance();
$c->handleRequest();
