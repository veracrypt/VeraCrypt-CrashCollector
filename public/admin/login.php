<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Exception\AuthenticationException;
use Veracrypt\CrashCollector\Security\Firewall;
use Veracrypt\CrashCollector\Form\LoginForm;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Security\UsernamePasswordAuthenticator;
use Veracrypt\CrashCollector\Templating;

$router = new Router();
$form = new LoginForm($router->generate(__FILE__), $router->generate(__DIR__ . '/index.php'));

if ($form->isSubmitted()) {
    $form->handleRequest();
    if ($form->isValid()) {
        $data = $form->getData();
        // the redirect url is validated by the form to match a local file within the web root
        $redirectUrl = $data['redirect'];
        unset($data['redirect']);
        $authenticator = new UsernamePasswordAuthenticator();
        try {
            $authenticator->authenticate(...$data);
            header('Location: ' . $redirectUrl, true, 303);
            $form->onSuccessfulLogin();
            exit();
        } catch (AuthenticationException $e) {
            /// @todo should we reduce the level of info shown? Eg. not tell apart unknown user from bad password
            $form->setError($e->getMessage());
        }
    }
} else {
    /// @todo should we give some info or warning if the user is logged in already?
}

$firewall = Firewall::getInstance();
$tpl = new Templating();
echo $tpl->render('admin/login.html.twig', [
    'form' => $form,
    'urls' => $firewall->getAdminUrls(),
]);
