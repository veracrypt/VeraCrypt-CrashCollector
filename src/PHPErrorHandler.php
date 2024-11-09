<?php

namespace Veracrypt\CrashCollector;

/**
 * Custom handling of PHP (fatal) errors
 */
class PHPErrorHandler
{
    protected $errorTypesToHandle = array(
        E_ERROR => 'E_ERROR',
        E_PARSE => 'E_PARSE',
        E_USER_ERROR => 'E_USER_ERROR',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    );

    public function handle(): void
    {
        try {
            // Bail if no error found.
            $error = $this->detectError();
            if (!$error) {
                return;
            }

            $this->notifyOfError($error);

        } catch (\Throwable $t) {
            // Catch exceptions and remain silent...
        }
    }

    /**
     * Inspired from WordPress.
     * @return array|null
     */
    protected function detectError(): ?array
    {
        $error = error_get_last();

        // No error, just skip the error handling code
        if (null === $error) {
            return null;
        }

        // Bail if this error should not be handled
        if (!$this->shouldHandleError($error)) {
            return null;
        }

        return $error;
    }

    /**
     * Inspired from WordPress.
     * @param array $error Error information retrieved from `error_get_last()`
     * @return bool
     */
    protected function shouldHandleError(array $error): bool
    {
        return isset($error['type']) && array_key_exists($error['type'], $this->errorTypesToHandle);
    }

    /**
     * NB: this only works insofar at least the dotenv-based loading of env vars has succeeded
     * @todo decide how to handle - log, email, other?
     */
    protected function notifyOfError(array $error): void
    {
        // no need to notify anyone when in debug mode - we rely on the php error log and display_errors being on
        if (!@$_ENV['APP_DEBUG']) {
            $text = "PHP {$this->errorTypesToHandle[$error['type']]}" .
                var_export(['error' => $error, '_SERVER' => $this->scrub($_SERVER)], true);
        }
    }

    /**
     * Removes sensitive data from the PHP environment. Atm that means everything loaded from the .env config
     */
    protected function scrub(array $_server): array
    {
        foreach (explode(',', $_server['DOTENV_VARS']) as $var) {
            $_server[$var] = '**********';
        }
        return $_server;
    }
}
