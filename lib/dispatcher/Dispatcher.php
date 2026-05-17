<?php

namespace dispatcher;

class Dispatcher
{
    public static function run()
    {
        //Obtener la ruta desde URL
        $uri = trim($_SERVER['REQUEST_URI'], '/');

        //Quitar query string si hay
        if(($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        //Separar segmentos
        $segments = $uri !== '' ? explode('/', $uri) : [];

        $controllerName = !empty($segments[0]) ? $segments[0] : 'main';
        $actionName = !empty($segments[1]) ? $segments[1] : 'index';
        $params = array_slice($segments, 2);

        require_once(__DIR__ . '/../../app/controllers/ApplicationController.php');

        $controllerFile = __DIR__ . '/../../app/controllers/' . ucfirst($controllerName) . 'Controller.php';
        if(file_exists($controllerFile)) {
            require_once($controllerFile);
        }

        //Construir nombre de clase: "main" -> "MainController"
        $className = '\\' . ucfirst($controllerName) . 'Controller';

        if(!\class_exists($className)) {
            http_response_code(404);
            die('Controller no encontrado: ' . $className);
        }

        $controller = new $className();

        if(!method_exists($controller, $actionName)) {
            http_response_code(404);
            die('Acción no encontrada: ' . $actionName);
        }

        call_user_func_array([$controller, $actionName], $params);
    }
}