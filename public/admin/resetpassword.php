<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Entity\UserRole;
use Veracrypt\CrashCollector\Exception\AuthorizationException;
use Veracrypt\CrashCollector\Form\ResetPasswordForm;
use Veracrypt\CrashCollector\Repository\UserRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Security\Firewall;
use Veracrypt\CrashCollector\Security\PasswordHasher;
use Veracrypt\CrashCollector\Templating;

$firewall = Firewall::getInstance();
$router = new Router();
$tpl = new Templating();

try {
    $firewall->require(UserRole::User);
    $user = $firewall->getUser();
} catch (AuthorizationException $e) {
    $firewall->displayAdminLoginPage($router->generate(__FILE__));
    exit();
}

$form = new ResetPasswordForm($user);
if ($form->isSubmitted()) {
    $form->handleRequest();
    if ($form->isValid()) {
        $passwordHasher = new PasswordHasher();
        $repository = new UserRepository();
        $repository->updateUser($user->getUserIdentifier(), $passwordHasher->hash($form->getField('newPassword')->getData()));
    }
}

echo $tpl->render('admin/resetpassword.html.twig', [
    'user' => $user,
    'form' => $form,
    'urls' => array_merge($firewall->getAdminUrls(), ['form' => $router->generate(__FILE__)]),
]);
