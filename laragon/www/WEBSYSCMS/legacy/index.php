<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('BASE_PATH', __DIR__);

require BASE_PATH.'/app/Core/helpers.php';
require BASE_PATH.'/app/Core/Flash.php';
require BASE_PATH.'/app/Core/Database.php';
require BASE_PATH.'/app/Core/View.php';
require BASE_PATH.'/app/Core/Auth.php';
require BASE_PATH.'/app/Core/Router.php';
require BASE_PATH.'/app/Core/Controller.php';

$config = require BASE_PATH.'/config/config.php';
date_default_timezone_set($config['app']['timezone']);

if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

$router = new Router;
require BASE_PATH.'/routes.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $e) {
    http_response_code(500);
    if ($config['app']['debug']) {
        echo '<h1>Application error</h1><pre>'.htmlspecialchars($e->getMessage()."\n\n".$e->getTraceAsString()).'</pre>';
    } else {
        echo 'Something went wrong. Please try again.';
    }
}
