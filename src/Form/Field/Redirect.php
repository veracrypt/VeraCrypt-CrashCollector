<?php

namespace Veracrypt\CrashCollector\Form\Field;

use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Router;

class Redirect extends Hidden
{
    protected function validateValue(mixed $value): null|string
    {
        if (null === $value) {
            return null;
        }
        $value = trim($value);
        $router = new Router();
        if (!$router->match($value)) {
            $this->errorMessage = 'Tsk tsk tsk. Pen testing redirects?';

            $logger = Logger::getInstance('audit');
            $logger->info("Hacking attempt? form submitted with invalid redirect url '$value'");
        }
        // We reset the redirect to the previous (current) value - as otherwise the displayed form will keep showing
        // a non-acceptable value. Also, pen-testing tools might believe that they achieve some injection of sorts...
        return $this->value;
    }
}
