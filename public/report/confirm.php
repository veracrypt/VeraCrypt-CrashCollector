<?php

use Veracrypt\CrashCollector\Templating;
use Veracrypt\CrashCollector\Form\CrashReportConfirmForm;
use Veracrypt\CrashCollector\Form\CrashReportRemoveForm;
use Veracrypt\CrashCollector\Logger;
use Veracrypt\CrashCollector\Repository\CrashReportRepository;
use Veracrypt\CrashCollector\Router;

require_once(__DIR__ . '/../../autoload.php');

$router = new Router();

header('Referrer-Policy: no-referrer');

$errorMessage = null;
$report = null;
$tokenId = null;
$secret = null;

$form1 = new CrashReportConfirmForm($router->generate(__FILE__));
if ($form1->isSubmitted()) {
    $form1->handleRequest();
    if ($form1->isValid()) {
        $report = $form1->getReport();
        $tokenId = $form1->getFieldData('token');
        $secret = $form1->getFieldData('secret');
    } else {
        $errorMessage = $form1->errorMessage;
    }
}

$form2 = new CrashReportRemoveForm($router->generate(__FILE__), $tokenId, $secret, $report);
if ($errorMessage === null && $form2->isSubmitted()) {
    $form2->handleRequest();
    if ($form2->isValid()) {
        // delete the report
        $report = $form2->getReport();
        $repository = new CrashReportRepository();
        $repository->deleteReport($report->id);
        // 'consume' (remove) the token
        $form2->getTokenRepository()->delete($form2->getFieldData('token'));
    }
}

if (!$form1->isSubmitted() && !$form2->isSubmitted()) {
    $errorMessage = 'Nothing to see here, move along.';

    $logger = Logger::getInstance('audit');
    $logger->debug("A request for /report/confirm.php was received, with no form submitted. Hacking attempt?");
}

$tpl = new Templating();
echo $tpl->render('report/confirm.html.twig', [
    'error' => $errorMessage,
    'report' => $report,
    'form' => $form2,
    'urls' => [
        'root' => $router->generate(__DIR__ . '/..'),
    ],
]);
