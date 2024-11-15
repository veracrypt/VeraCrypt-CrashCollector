<?php

namespace Veracrypt\CrashCollector;

use Veracrypt\CrashCollector\Exception\ConfigurationException;

class DotEnvLoader
{
    /// @see https://pubs.opengroup.org/onlinepubs/000095399/basedefs/xbd_chap08.html
    protected static string $VARNAME_REGEX = '/^(?:export[ \t]+)?([a-zA-Z_][a-zA-Z0-9_]*)/';

    /**
     * Loads values from a .env file and the corresponding .env.local file if they exist.
     * The values are set into $_ENV and $_SERVER (the latter as long as the env var does not start with HTTP_).
     * Known limitations: atm it
     * - does not support comments at the end of value lines;
     * - does not support backslash escapes within quoted values, such as "\t";
     * - does not support multiline values;
     * - does not resolve env variables found within the .env file, nor backticks or any other shell magic;
     * - has No support for debug mode, nor different environments.
     * API inspired by Symfony DotEnv component.
     *
     * @param string $path the file to load
     * @return void
     * @throws ConfigurationException when a file is not readable or has a syntax error
     */
    public function loadEnv(string $path): void
    {
        if (is_file($path)) {
            $this->doLoad($path, true);
        }
        if (is_file($p = "$path.local")) {
            $this->doLoad($p, true);
        }
    }

    /**
     * @param string $path
     * @param bool $overrideExistingVars
     * @return void
     * @throws ConfigurationException
     */
    protected function doLoad(string $path, bool $overrideExistingVars): void
    {
        $this->populate($this->parse($path), $overrideExistingVars);
    }

    /**
     * @param string $path
     * @return array
     * @throws ConfigurationException
     */
    protected function parse(string $path): array
    {
        if (!is_readable($path) || !is_file($path)) {
            throw new ConfigurationException("Unable to read the '$path' environment file");
        }

        $data = explode("\n", str_replace(["\r\n", "\r"], "\n", file_get_contents($path)));
        array_walk($data, function(&$value, $key) {
            // be wary of NULL and \v chars - we only strip whitespaces and tabs
            $value = trim($value, " \t");
        });
        $data = array_filter($data, function($item) {
            return $item !== '' && !str_starts_with($item, '#');
        });

        $parsed = [];
        foreach($data as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                throw new ConfigurationException("Unable to parse the '$path' environment file - missing = in the environment variable declaration in line '$line'");
            }
            if (!preg_match(static::$VARNAME_REGEX, $parts[0], $nameMatches)) {
                throw new ConfigurationException("Unable to parse the '$path' environment file - unsupported characters in the environment variable name in line '$line'");
            }
            $name = $nameMatches[1];

            if (preg_match('/^[ \t]*(?:#.*)?$/', $parts[1])) {
                $value =  '';
            } else {
                /// @todo do a proper parsing - check for case where there are quotes in the middle of the value,
                ///       and comments at its end
                if (strlen($parts[1]) >= 2 && (str_starts_with($parts[1], '"') || str_starts_with($parts[1], "'")) && $parts[1][strlen($parts[1]) - 2] === $parts[1][0]) {
                    $value = substr($parts[1], 1, -1);
                } else {
                    if (str_starts_with($parts[1], ' ') || str_starts_with($parts[1], "\t")) {
                        throw new ConfigurationException("Unable to parse the '$path' environment file - unquoted variable value should not start with space name in line '$line'");
                    }
                    $value = $parts[1];
                }
            }
            $parsed[$name] = $value;
        }

        return $parsed;
    }

    protected function populate(array $values, bool $overrideExistingVars = true): void
    {
        foreach ($values as $name => $value) {
            // NB: we do allow values from .env files to override values set via proper env vars. If this is not desired,
            // we should rework how loading the env.local file is able to overwrite stuff from .env and also stuff already
            // in _ENV, i.e. this check should look for vars already in $_SERVER['DOTENV_VARS']
            if (!$overrideExistingVars && array_key_exists($name, $_ENV)) {
                unset($values[$name]);
                continue;
            }

            $_ENV[$name] = $value;
            /// @todo should we log a warning in case of a .env var starting with HTTP_ ? Note hat the logger class
            ///       depends on the dotenv config having been set up already...
            if (!str_starts_with($name, 'HTTP_')) {
                $_SERVER[$name] = $value;
            }
        }

        $dotEnvVars = isset($_SERVER['DOTENV_VARS']) && $_SERVER['DOTENV_VARS'] !== '' ? explode(',', $_SERVER['DOTENV_VARS']) : [];
        $_SERVER['DOTENV_VARS'] = implode(',', array_unique($dotEnvVars + array_keys($values)));
    }
}
