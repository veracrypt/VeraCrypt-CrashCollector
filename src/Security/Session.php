<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Singleton;
use Veracrypt\CrashCollector\Storage\Session as SessionTrait;

/**
 * Ties together the ability of storing data in the session with cookie-based session management
 */
class Session
{
    use SessionTrait;
    use Singleton;

    /**
     * NB: we could allow passing in an optional instance of \SessionHandlerInterface, and provide a default one which
     * stores session data in the same database used for the CR data (or, preferably, a separate sqlite file).
     * However, SQLite does not support row level locks but locks the whole database, with the results that only
     * one session could be accessed at a time. Even different sessions would wait for another to finish. So saving
     * sessions in SQLite is not recommended, unless we also use session autocommit.
     */
    protected function __construct()
    {
        if (!\extension_loaded('session')) {
            throw new \LogicException('PHP extension "session" is required');
        }

        // Enforce "safe" session config defaults. See https://www.php.net/manual/en/session.configuration.php
        // NB: when the session save handler is set to the default value of `files`, the session file is locked
        // for both reading and writing for the whole duration of the php script. In order to improve concurrency,
        // one option could be to use the `read_and_close` option for the `session_start` call.
        // However, it seems that doing so has negative effects when the session GC is triggered via an external
        // cronjob instead of via session.gc_probability, as configured by default on Debian/Ubuntu.
        // See: https://stackoverflow.com/questions/37789172/php-session-randomly-dies-when-read-and-close-is-active
        /// @todo should we allow values for session-related options be set via .env?
        $this->sessionOptions = [
            'use_strict_mode' => 1, 'use_cookies' => 1, 'use_only_cookies' => 1, 'cookie_httponly' => 1,
            'cookie_samesite' => 'Strict'/*, 'cache_limiter' => '', 'cache_expire' => 0*/, 'use_trans_sid' => 0, 'lazy_write' => 1,
        ];
    }

    public function regenerate(): void
    {
        /// @todo Should we delete the previous session data, passing in $true, or keep the old session around? See example 2 at
        ///       https://www.php.net/manual/en/function.session-regenerate-id.php#refsect1-function.session-regenerate-id-examples
        ///       for the recommended way to generate new session ids while avoiding issues with unstable networks and
        ///       race conditions between requests
        session_regenerate_id();
    }

    public function destroySession(): void
    {
        if (\PHP_SESSION_ACTIVE === session_status()) {
            $_SESSION = [];
            /// @todo check php source code: does it make sense to call session_write_close() here, or does session_destroy do that already?
            session_destroy();
        }

        $this->sessionStarted = false;

        // Expire the session cookie.
        // Note that, in theory at least, this is not required when session.strict_mode is enabled (which we _try_ to force),
        // as subsequent requests with the same session id cookie will not trigger creation of a session.
        // We prefer taking a belt-and-suspenders approach, and leave no dead cookies on the browser.
        $sessionName = session_name();
        if (isset($_COOKIE[$sessionName])) {
            $params = session_get_cookie_params();
            unset($params['lifetime']);
            setcookie($sessionName, '', $params);
        }
    }

    /**
     * Saves data and unlocks the session
     * @throws \RuntimeException
     */
    public function commit(): void
    {
        $this->doCommit();
    }
}
