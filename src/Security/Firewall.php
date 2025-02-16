<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\EnvVarProcessor;
use Veracrypt\CrashCollector\Exception\UserNotAuthorizedException;
use Veracrypt\CrashCollector\Exception\UserNotFoundException;
use Veracrypt\CrashCollector\Form\LoginForm;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Repository\UserRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Singleton;
use Veracrypt\CrashCollector\Templating;

class Firewall
{
    use Singleton;

    protected ?UserInterface $user = null;
    protected string $storageKey = 'username';

    protected function __construct()
    {
        if ($this->supportsRequest()) {
            $this->authenticate();
        }
    }

    /**
     * @todo implement some logic when/if we'll add support for non-session auth such as eg. basic auth, etc...
     */
    protected function supportsRequest(): bool
    {
        return true;
    }

    protected function authenticate(): void
    {
        $session = Session::getInstance();
        // we avoid calling session_start (triggered by `get`) unless there is a session cookie present, in order to make it
        // possible to have the fw run on pages which work both for non and authenticated users without creating sessions
        // all the time
        if (!$session->cookieIsPresent() && !$session->isStarted()) {
            return;
        }
        $username = $session->get($this->storageKey);
        if ($username !== null) {
            // we always refresh the user details from the repo, to check if his/her roles or active status have changed
            $userProvider = new UserProvider();
            try {
                // NB: we do not catch \PDOException - in case f.e. the db connection is down, we let the error bubble all the way up
                $user = $userProvider->loadUserByIdentifier($username);
                if ($user->isActive()) {
                    $this->user = $user;
                    /// @todo here we could add $session->commit() - or use autocommitting sessions
                    return;
                }
            } catch (UserNotFoundException $e) {
            }

            // the current user either disappeared from the db or got invalidated - clean up the session stuff
            $this->logoutUser(true);

            $logger = Logger::getInstance('audit');
            $logger->debug("A session for user '$username' has been forcibly terminated as his/her profile was updated");
        }
    }

    public function getUser(): UserInterface
    {
        return $this->user === null ? new AnonymousUser() : $this->user;
    }

    /**
     * NB: this does not check for user roles nor active status. Doing that is left to the caller!
     */
    public function loginUser(UserInterface $user): void
    {
        if (!$user->isAuthenticated()) {
            throw new \DomainException('Non-authenticated user ' . $user->getUserIdentifier() . ' can not be used for logging in');
        }

        if ($user === $this->user) {
            return;
        }

        $this->user = $user;

        $session = Session::getInstance();
        $previousUserIdentifier = $session->get($this->storageKey);
        if ($user->getUserIdentifier() !== $previousUserIdentifier) {
            $session->regenerate();

            $antiCSRF = new AntiCSRF();
            $antiCSRF->purgeTokens();
        }
        $session->set($this->storageKey, $user->getUserIdentifier());
        /// @todo here we could add $session->commit() - or use autocommitting sessions

        if ($user instanceof User) {
            $repo = new UserRepository();
            try {
                $repo->userLoggedIn($user->username);
            } catch (\PDOException) {
                $logger = Logger::getInstance('audit');
                $logger->warning("Failed updating last login time for user '{$user->username}'");
            }
        }
    }

    public function logoutUser(bool $force = false): void
    {
        if ($this->user === null && !$force) {
            return;
        }

        $this->user = null;

        $session = Session::getInstance();
        $session->destroySession();

        /// @todo clean up remember-me tokens if not stored in the Session
        /// @todo send Clear-Site-Data header? Investigate more the pros and cons...
    }

    /**
     * @param UserRole|UserRole[] $roles
     * @throws UserNotAuthorizedException
     */
    public function require(UserRole|array $roles): void
    {
        $userRoles = $this->getUser()->getRoles();
        if (!is_array($roles)) {
            $roles = array($roles);
        }
        foreach($roles as $role) {
            if (!in_array($role, $userRoles)) {
                throw new UserNotAuthorizedException("Current user does not have required role " . $role->value);
            }
        }
    }

    public function displayAdminLoginPage(string $successRedirectUrl): void
    {
        /// @todo should we give some info or warning if the user is logged in already? Eg. if this is used to display
        ///       the login form on a page which requires admin perms...

        // avoid browsers and proxies caching the login-form version of the current page - we send he same no-cache headers
        // as sent by php when setting session_cache_limiter to nocache
        if (!headers_sent()) {
            header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
        $router = new Router();
        $tpl = new Templating();
        echo $tpl->render('admin/login.html.twig', [
            'form' => new LoginForm($router->generate(__DIR__ . '/../../public/admin/login.php'), $successRedirectUrl),
            'urls' => $this->getAdminUrls(),
        ]);
    }

    /**
     * @return string[]
     */
    public function getAdminUrls(): array
    {
        $router = new Router();
        $urls = [
            'root' => $router->generate(__DIR__ . '/../../public'),
            'home' => $router->generate(__DIR__ . '/../../public/admin/index.php'),
            'login' => $router->generate(__DIR__ . '/../../public/admin/login.php'),
            'logout' => $router->generate(__DIR__ . '/../../public/admin/logout.php'),
            'resetpassword' => $router->generate(__DIR__ . '/../../public/admin/resetpassword.php'),

        ];
        if (EnvVarProcessor::bool($_ENV['ENABLE_FORGOTPASSWORD'])) {
            $urls['forgotpassword'] = $router->generate(__DIR__ . '/../../public/admin/forgotpassword.php');
        }
        return $urls;
    }
}
