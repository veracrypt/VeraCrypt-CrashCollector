<?php

namespace Veracrypt\CrashCollector\Storage;

trait Session
{
    protected bool $sessionStarted = false;
    protected array $sessionOptions = [];
    private bool $doAutoCommit = false;

    /**
     * @throws \RuntimeException if the session was not yet started and either http headers have already been sent, or
     *                           (presumably) the session storage fails
     */
    public function set(string|int $key, mixed $value): void
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        $_SESSION[$key] = $value;

        if ($this->doAutoCommit) {
            $this->doCommit();
        }
    }

    /**
     * NB: this will cause the session to start if it was not started already!
     * @todo we could cache the whole of $_SESSION in memory so that we can avoid further calls to session_start on later
     *       calls to get (and either add a $forceRefresh argument, or a separate `refresh` method). This is esp.
     *       useful when in autocommit mode
     * @throws \RuntimeException if the session was not yet started and either http headers have already been sent, or
     *                           (presumably) the session storage fails
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        if (array_key_exists($key, $_SESSION)) {
            $default = $_SESSION[$key];
        }

        if ($this->doAutoCommit) {
            $this->doCommit();
        }

        return $default;
    }

    /**
     * @throws \RuntimeException if the session was not yet started and either http headers have already been sent, or
     *                           (presumably) the session storage fails
     */
    protected function startSession(): void
    {
        if (\PHP_SESSION_NONE === session_status()) {

            // we throw to avoid the php warning 'Session cannot be started after headers have already been sent'
            // which would be generated later by calling `session start`
            if (headers_sent()) {
                throw new \RuntimeException('Session cannot be started after headers have already been sent');
            }

            // NB: in case we did not enforce use_strict_mode, we could look if there is a cookie matching `session_name()`.
            // If there is, validate that the session_id matches a regexp like `/^[a-zA-Z0-9,-]{22,250}$/` (but built upon
            // live values of session.sid_bits_per_character and session.sid_length) and if it does not, call
            // `session_id(session_create_id())` and log the event. See Sf NativeSessionStorage::start for an explanation

            /// @todo once we have improved `regenerate` so that it keeps around the old session and adds specific
            ///       data to it, check here for its presence

            if (!session_start($this->sessionOptions)) {
                throw new \RuntimeException('Failed to start the session');
            }
        }

        $this->sessionStarted = true;
    }

    /**
     * Sets/gets the autocommit mode
     */
    public function autoCommit(?bool $autoCommit): bool
    {
        if ($autoCommit !== null) {
            $this->doAutoCommit = $autoCommit;
        }
        return $this->doAutoCommit;
    }

    /**
     * Saves data and unlocks the session
     * @throws \RuntimeException
     */
    protected function doCommit(): void
    {
        if (!session_write_close()) {
            throw new \RuntimeException('Failed to save the session');
        }
        $this->sessionStarted = false;
    }
}
