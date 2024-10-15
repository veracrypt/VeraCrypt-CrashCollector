<?php

namespace Veracrypt\CrashCollector\Security;

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Entity\UserRole;
use Veracrypt\CrashCollector\Exception\AuthorizationException;
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

    protected function supportsRequest()
    {
        return true;
    }

    protected function authenticate(): void
    {
        $session = Session::getInstance();
        $username = $session->get($this->storageKey);
        if ($username !== null) {
            // we always refresh the user details from the repo, to check if his/her roles or active status have changed
            $userProvider = new UserProvider();
            try {
                // NB: we do not catch \PDOException - in case f.e. the db connection is down, we let the error bubble all the way up
                $user = $userProvider->loadUserByIdentifier($username);
                if ($user->isActive()) {
                    $this->user = $user;
                    /// @todo here we could add $session->commit();
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
        if ($user instanceof AnonymousUser) {
            throw new \DomainException('Anonymous user can not be used for logging in');
        }

        if ($user === $this->user) {
            return;
        }

        $this->user = $user;

        $session = Session::getInstance();
        $previousUserIdentifier = $session->get($this->storageKey);
        if ($user->getUserIdentifier() !== $previousUserIdentifier) {
            $session->regenerate();
        }
        $session->set($this->storageKey, $user->getUserIdentifier());
        /// @todo here we could add $session->commit();

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

        /// @todo clean up csrf tokens and remember-me tokens if not stored in the Session
        /// @todo send Clear-Site-Data header? (using an event system?)
    }

    /**
     * @param UserRole|UserRole[] $roles
     * @throws AuthorizationException
     */
    public function require(UserRole|array $roles): void
    {
        foreach(array($roles) as $role) {
            if (!in_array($role, $this->getUser()->getRoles())) {
                throw new AuthorizationException("Current user does not have required role " . $role->value);
            }
        }
    }

    public function displayAdminLoginPage(string $successRedirectUrl): void
    {
        /// @todo should we give some info or warning if the user is logged in already? Eg. if this is used to
        ///       display the login form on a page which requires admin perms...

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
            'form' => new LoginForm($successRedirectUrl),
            'form_url' => $router->generate(__DIR__ . '/../../public/admin/login.php'), 'root_url' => $router->generate(__DIR__ . '/../../public')
        ]);
    }
}
