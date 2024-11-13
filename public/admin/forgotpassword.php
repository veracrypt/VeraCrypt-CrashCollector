<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Entity\User;
use Veracrypt\CrashCollector\Form\ForgotPasswordForm;
use Veracrypt\CrashCollector\Form\ForgotPasswordEmailForm;
use Veracrypt\CrashCollector\Mailer\Email;
use Veracrypt\CrashCollector\Mailer\Mailer;
use Veracrypt\CrashCollector\Repository\ForgotPasswordTokenRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Security\Firewall;
use Veracrypt\CrashCollector\Security\PasswordHasher;
use Veracrypt\CrashCollector\Templating;

$firewall = Firewall::getInstance();
$router = new Router();
$tpl = new Templating();

// if non-anon user, redirect to resetpassword instead of using this form
$user = $firewall->getUser();
if ($user->isAuthenticated()) {
    header('Location: ' . $router->generate(__DIR__ . '/resetpassword.php'), true, 303);
    exit();
}

$form = new ForgotPasswordForm($router->generate(__FILE__));

if ($form->isSubmitted()) {
    $form->handleRequest();
    if ($form->isValid()) {

        /** @var User $user */
        $user = $form->getUser();

        $ph = new PasswordHasher();
        $fptRepo = new ForgotPasswordTokenRepository();
        $secret = $ph->generateRandomString($fptRepo->tokenLength);
        $token = $fptRepo->createToken($user->username, $ph->hash($secret));
        /// @todo use mime multipart to add an html version besides plain text
        $mailer = new Mailer();
        $email = new Email();
        $form2 = new ForgotPasswordEmailForm(__DIR__ . '/setnewpassword.php', $token->id, $secret);
        $text = $tpl->render('emails/forgotpassword.txt.twig', [
            'link' => $_ENV['WEBSITE'] . $router->generate(__DIR__ . '/setnewpassword.php', $form2->getQueryStringParts())
        ]);
        $email->from($_ENV['MAIL_FROM'])->to($user->email)->subject("VeraCrypt Crash Collector password reset link")->text($text);
        try {
            $mailer->send($email);
        } catch (\RuntimeException $e) {
            $form->setError('There was an error sending the email, please retry later.');
        }
    }
}

echo $tpl->render('admin/forgotpassword.html.twig', [
    'form' => $form,
    'urls' => $firewall->getAdminUrls(),
]);
