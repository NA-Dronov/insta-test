<?php

namespace App\Core;

use Throwable;

class Controller
{
    use TSingleton;

    public function handleRequest()
    {
        $path = $_REQUEST['request'] ?? '';

        if (empty($path)) {
            $path = 'home';
        }

        $pathArray = explode("/", $path);

        try {
            $commandPseudo = array_pop($pathArray);
            if (!in_array(strpos($commandPseudo, '-'), [false, 0])) {
                $commandPseudo = explode("-", $commandPseudo);
                array_walk($commandPseudo, function (&$v, $k) {
                    $v = ucfirst($v);
                });
                $commandPseudo = implode("", $commandPseudo);
            }
            $commandName = ucfirst($commandPseudo) . "Command";
            $commandFilePath = empty($pathArray) ? $commandName : implode(DIRECTORY_SEPARATOR, $pathArray) . DIRECTORY_SEPARATOR . $commandName;
            $commandPath = COMMANDS_ROOT . DIRECTORY_SEPARATOR . $commandFilePath . ".php";

            if (is_readable($commandPath) && !is_dir($commandPath)) {
                include($commandPath);

                /**
                 * @var ACommand $command
                 */
                $commnadClass = COMMANDS_NS . implode('\\', $pathArray) . (!empty($pathArray) ? '\\' : '') . $commandName;
                $command = new $commnadClass();
                $command->execute();
            } else {
                throw new NotFoundException("The required resource not found", 1);
            }
        } catch (NotFoundException $ex) {
            http_response_code($ex->getResponseCode());
            $ex->view('404', ['title' => '404 Not Found']);
            die();
        } catch (APIException $ex) {
            $ex->json(['error' => $ex->getMessage()]);
        } catch (Throwable $ex) {
            $view = new View();
            $view->view('404', ['msg' => $ex->getMessage()]);
        }
    }
}
