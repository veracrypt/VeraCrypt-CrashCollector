<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\EnvVarProcessor;
use Veracrypt\CrashCollector\Form\ForgotPasswordEmailForm;
use Veracrypt\CrashCollector\Form\SetNewPasswordForm;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Repository\UserRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Security\Firewall;
use Veracrypt\CrashCollector\Security\PasswordHasher;
use Veracrypt\CrashCollector\Templating;

$firewall = Firewall::getInstance();
$router = new Router();

if (!EnvVarProcessor::bool($_ENV['ENABLE_FORGOTPASSWORD'])) {
    header('Location: ' . $router->generate(__DIR__ . '/index.php'), true, 303);
    exit();
}

// if non-anon user, redirect to resetpassword instead of using this form
$currentUser = $firewall->getUser();
if ($currentUser->isAuthenticated()) {
    header('Location: ' . $router->generate(__DIR__ . '/resetpassword.php'), true, 303);
    exit();
}

// as per owasp recommendations - https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html#url-tokens
header('Referrer-Policy: no-referrer');

$errorMessage = null;
$tokenId = null;
$secret = null;

$form1 = new ForgotPasswordEmailForm($router->generate(__FILE__));
if ($form1->isSubmitted()) {
    $form1->handleRequest();
    if ($form1->isValid()) {
        $tokenId = $form1->getFieldData('token');
        $secret = $form1->getFieldData('secret');
    } else {
        $errorMessage = $form1->errorMessage;
    }
}

$form2 = new SetNewPasswordForm($router->generate(__FILE__), $tokenId, $secret);
if ($errorMessage === null && $form2->isSubmitted()) {
    $form2->handleRequest();
    if ($form2->isValid()) {
        // update the user
        $user = $form2->getUser();
        $passwordHasher = new PasswordHasher();
        $repository = new UserRepository();
        $repository->updateUser($user->username, $passwordHasher->hash($form2->getFieldData('newPassword')));
        // 'consume' (remove) the token
        $form2->getTokenRepository()->delete($form2->getFieldData('token'));
    }
}

if (!$form1->isSubmitted() && !$form2->isSubmitted()) {
    $errorMessage = 'Nothing to see here, move along.';

    $logger = Logger::getInstance('audit');
    $logger->debug("A request for setnewpassword.php was received, with no form submitted. Hacking attempt?");
}

$tpl = new Templating();
echo $tpl->render('admin/setnewpassword.html.twig', [
    'error' => $errorMessage,
    'form' => $form2,
    'urls' => $firewall->getAdminUrls(),
]);
