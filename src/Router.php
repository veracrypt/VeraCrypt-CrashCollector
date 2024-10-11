<?php

namespace Veracrypt\CrashCollector;

class Router
{
    protected string $rootUrl;
    protected string $rootDir;

    public function __construct()
    {
        $this->rootUrl = $_ENV['ROOT_URL'];
        $this->rootDir = realpath(__DIR__ . '/../public/');
    }

    /**
     * @todo add support for generating absolute URLs, etc...
     *       see fe. https://github.com/symfony/routing/blob/7.1/Generator/UrlGeneratorInterface.php
     * @throws \DomainException
     */
    public function generate(string $fileName, array $queryStringParts = []): string
    {
        $fileName = realpath($fileName);
        if (!str_starts_with($fileName, $this->rootDir)) {
            throw new \DomainException("Given file path is outside web root: '$fileName'");
        }
        $url = $this->rootUrl . substr($fileName, strlen($this->rootDir) + 1);

        /// @todo strip trailing `/index.php` based on analysis of $_SERVER

        if ($queryStringParts) {
            $parts = [];
            foreach ($queryStringParts as $name => $value) {
                $parts[] = urlencode($name) . '=' . urlencode($value);
            }
            $url .= '?' . implode('&', $parts);
        }
        return $url;
    }
}
