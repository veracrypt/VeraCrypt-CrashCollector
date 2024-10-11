<?php

namespace Veracrypt\CrashCollector;

use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

class Templating
{
    protected $twig;

    public function __construct()
    {
        # @see https://twig.symfony.com/doc/3.x/api.html#environment-options for all possible options and their meaning
        $this->twig = new TwigEnvironment(
            new TwigFilesystemLoader(__DIR__ . "/../resources/templates"),
            [
                'debug' => (bool)$_ENV['APP_DEBUG'],
                'cache' => __DIR__ . '/../var/cache/twig',
            ]
        );
    }

    /**
     * @param $name
     * @param array $context
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render($name, array $context = []): string
    {
        return $this->twig->render($name, $context);
    }
}
