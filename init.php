<?php

//Autoloader de Doctrine
require_once(__DIR__ . '/lib/vendor/doctrine/Doctrine.php');
spl_autoload_register(['Doctrine', 'autoload']);
spl_autoload_register(['Doctrine_Core', 'modelsAutoload']);

//Autoloader de Smarty
require_once(__DIR__ . '/lib/vendor/smarty/Smarty.class.php');

//Base de datos y configuración
require_once(__DIR__ . '/bootstrap.php');

//Dispatcher
require_once(__DIR__ . '/lib/dispatcher/Dispatcher.php');