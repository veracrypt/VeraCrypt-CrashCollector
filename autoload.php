<?php

// Sneaky file warning! This does other stuff besides setting up autoloading ;-)

// set up a fatal error handler as 1st thing
require_once(__DIR__ . '/src/PHPErrorHandler.php');
register_shutdown_function([new \Veracrypt\CrashCollector\PHPErrorHandler(), 'handle']);

// set up autoload configuration (the std one from Composer)
require_once(__DIR__ . '/vendor/autoload.php');

// load configuration from .env files
use Veracrypt\CrashCollector\DotEnvLoader;
$dotenv = new DotEnvLoader();
$dotenv->loadEnv(__DIR__ . '/.env');
unset($dotenv);

// enable debug mode
if ($_ENV['APP_DEBUG']) {
    ini_set('display_errors', true);
    /// @todo should we also set error_reporting?
}
