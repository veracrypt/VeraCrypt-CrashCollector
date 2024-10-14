<?php

namespace Veracrypt\CrashCollector;

class Router
{
    protected string $rootUrl;
    protected string $rootDir;
    protected bool $stripPhpExtension = false;
    protected bool $stripIndexDotPhp = false;

    public function __construct()
    {
        /// @todo allow setting $stripPhpExtension and $stripIndexDotPhp from $_ENV

        $this->rootUrl = $_ENV['ROOT_URL'];
        // nb: realpath trims the trailing slash
        $this->rootDir = realpath(__DIR__ . '/../public/');
    }

    /**
     * @todo add support for generating absolute URLs, etc...
     *       see fe. https://github.com/symfony/routing/blob/7.1/Generator/UrlGeneratorInterface.php
     * @param string $fileName the absolute file path. If an empty string is passed, the current execution directory is used
     * @throws \DomainException
     */
    public function generate(string $fileName, array $queryStringParts = []): string
    {
        $realFileName = realpath($fileName);
        if (!str_starts_with($realFileName, $this->rootDir . '/') && $realFileName !== $this->rootDir) {
            throw new \DomainException("Given file path is outside web root: '$fileName'");
        }
        $url = $this->rootUrl . substr($realFileName, strlen($this->rootDir) + 1);

        if ($this->stripIndexDotPhp) {
            $url = preg_replace('#/index\.php$#', '/', $url);
        }
        if ($this->stripPhpExtension) {
            $url = preg_replace('#\.php$#', '', $url);
        }

        if ($queryStringParts) {
            $parts = [];
            foreach ($queryStringParts as $name => $value) {
                $parts[] = urlencode($name) . '=' . urlencode($value);
            }
            $url .= '?' . implode('&', $parts);
        }
        return $url;
    }

    /**
     * @todo add support for absolute urls, etc...
     */
    public function match(string $pathInfo): string|false
    {
        $parts = parse_url($pathInfo);
        if (!$parts || !array_key_exists('path', $parts) || $parts['path'] === '' ||
            array_intersect_key(['scheme' => 0, 'host' => 0, 'port' => 0, 'user' => 0, 'pass' => 0], $parts)) {
            return false;
        }

        // nb: realpath normalizes excess slashes - no need to ltrim them. It also removes trailing ones
        $fileName = realpath($this->rootDir . '/' . $parts['path']);

        if ($fileName === false && $this->stripPhpExtension && !preg_match('#(/|\.php)$#', $parts['path'])) {
            $fileName = realpath($this->rootDir . '/' . $parts['path'] . '.php');
        }

        if ($fileName === false || (!str_starts_with($fileName, $this->rootDir . '/') && $fileName !== $this->rootDir)) {
            return false;
        }

        if (is_dir($fileName) && $this->stripIndexDotPhp && is_file($fileName . '/index.php')) {
            $fileName = $fileName . '/index.php';
        }

        return $fileName;
    }
}
