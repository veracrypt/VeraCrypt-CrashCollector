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

// work around the _current_ way VC implements sending the call stack
if (isset($_GET['st0']) && !isset($_GET['st'])) {
    $_GET['st'] = [];
    $i = 0;
    while (isset($_GET['st' . $i])) {
        $_GET['st'][] = $_GET['st' . $i];
        unset($_GET['st' . $i]);
        $i++;
    }
    $_GET['st'] = implode("\n", $_GET['st']);
}

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
        if (!EnvVarProcessor::bool($_ENV['ENABLE_BROWSER_UPLOAD'])) {
            // Since we do not allow users to use a form to upload report data, there is no need to display it.
            // We just display an error response instead

            http_response_code(400);

            $errors = $form->getFieldsErrors();
            array_walk($errors, function(&$value, $key) use ($form) {
                $value = $form->getField($key)->label . ': ' . $value;
            });
            if ($form->errorMessage != '') {
                array_unshift($errors, $form->errorMessage);
            }

            /// @todo should we add back an option to display a plaintext response?
            //header('Content-Type: text/plain');
            //echo implode("\n", $errors);

            $tpl = new Templating();
            echo $tpl->render('report/upload_failure.html.twig', ['errors' => $errors, 'urls' => ['root' => $router->generate(__DIR__ . '/..')]]);
            exit();
        }
    }
} else {
    if (!EnvVarProcessor::bool($_ENV['ENABLE_BROWSER_UPLOAD'])) {
        http_response_code(404);
        /// @todo should we display some help text instead of just a blank 404 page?
        exit();
    }
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
