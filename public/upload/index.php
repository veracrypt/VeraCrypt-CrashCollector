<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Form\CrashReportSubmitForm;
use Veracrypt\CrashCollector\Repository\CrashReportRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Templating;

$router = new Router();
$form = new CrashReportSubmitForm($router->generate(__FILE__));

// allow to pre-fill form fields using GET request, but only act on POST
if ($form->isSubmitted()) {
    $form->handleRequest();
    if ($form->isValid()) {
        /// @todo catch db runtime errors and show a nice error msg such as 'try later' - possibly distinguishing
        ///       data-related errors and using an appropriate error message
        $crr = new CrashReportRepository();
        $crr->createReport(...$form->getData());
    }
} elseif ($form->isSubmitted($_GET)) {
    $form->handleRequest($_GET);
}

$tpl = new Templating();
echo $tpl->render('upload/index.html.twig', [
    'form' => $form,
    'urls' => ['root' => $router->generate(__DIR__ . '/..')],
]);
