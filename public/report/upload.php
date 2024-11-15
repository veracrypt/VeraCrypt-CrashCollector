<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\EnvVarProcessor;
use Veracrypt\CrashCollector\Form\CrashReportSubmitForm;
use Veracrypt\CrashCollector\Repository\CrashReportRepository;
use Veracrypt\CrashCollector\Repository\ManageReportTokenRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Security\PasswordHasher;
use Veracrypt\CrashCollector\Templating;


$router = new Router();

$form = new CrashReportSubmitForm($router->generate(__FILE__));
$confirmUrl = null;

if ($form->isSubmitted()) {
    $form->handleRequest();
    if ($form->isValid()) {
        /// @todo catch db runtime errors and show a nice error msg such as 'try later' - possibly distinguishing
        ///       data-related errors and using an appropriate error message
        $crr = new CrashReportRepository();
        $report = $crr->createReport(...$form->getData());

        $mrr = new ManageReportTokenRepository();
        $ph = new PasswordHasher();
        $secret = $ph->generateRandomString($mrr->tokenLength);
        $token = $mrr->createToken($report->id, $ph->hash($secret));

        $confirmUrl = $router->generate(__DIR__ . '/confirm.php', ['tkn' => $token->id, 'sec' => $secret]);
        header('Location: ' . $confirmUrl, true, 303);
        exit();
    } else {
        http_response_code(400);
        header('Content-Type: text/plain');

        $errors = $form->getFieldsErrors();
        array_walk($errors, function(&$value, $key) use ($form) {
            $value = $form->getField($key)->label . ': ' . $value;
        });
        if ($form->errorMessage != '') {
            array_unshift($errors, $form->errorMessage);
        }
        echo implode("\n", $errors);

        exit();
    }
}

if (!EnvVarProcessor::bool($_ENV['ENABLE_BROWSER_UPLOAD'])) {
    http_response_code(404);
    exit();
}

// uncomment these lines to allow to pre-fill form fields using a GET request, but only act on POST
//if ($form->isSubmitted($_GET)) {
//    $form->handleRequest($_GET);
//}

$tpl = new Templating();
echo $tpl->render('report/upload.html.twig', [
    'form' => $form,
    'urls' => [
        'root' => $router->generate(__DIR__ . '/..'),
        'confirm' => $confirmUrl
    ],
]);
