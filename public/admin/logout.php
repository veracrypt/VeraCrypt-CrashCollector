<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Security\Firewall;
use Veracrypt\CrashCollector\Router;

Firewall::getInstance()->logoutUser(true);

$router = new Router();
// Note: unlike 301, 303 responses are not cacheable by default, unless accompanied by cache-control headers, as
// specified in https://datatracker.ietf.org/doc/html/rfc7231#section-6.1
header('Location: ' . $router->generate(__DIR__ . '/index.php'), true, 303);
