<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Entity\UserRole;
use Veracrypt\CrashCollector\Exception\AuthorizationException;
use Veracrypt\CrashCollector\Form\CrashReportSearchForm;
use Veracrypt\CrashCollector\Repository\CrashReportRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Security\Firewall;
use Veracrypt\CrashCollector\Templating;

/// @todo get from .env?
$pageSizes = [10, 25, 50];

$firewall = Firewall::getInstance();
$router = new Router();
$tpl = new Templating();

try {
    $firewall->require(UserRole::User);
    $user = $firewall->getUser();
} catch (AuthorizationException $e) {
    $firewall->displayAdminLoginPage($router->generate(__FILE__, $_GET));
    exit();
}

$reports = [];
$numReports = 0;

/// @todo should we move handling of ps, pn to the SearchForm (without it interfering with the paginator)?
$pageSize = isset($_GET['ps']) ? (int)$_GET['ps'] : reset($pageSizes);
if (!in_array($pageSize, $pageSizes)) {
    $pageSize = reset($pageSizes);
}
$pageNum = isset($_GET['pn']) ? (int)$_GET['pn'] : 0;
if ($pageNum < 0) {
    $pageNum = 0;
}

$form = new CrashReportSearchForm();
if ($form->isSubmitted()) {
    $form->handleRequest();
    if ($form->isValid()) {
        /// @todo catch db runtime errors and show a nice error msg such as 'try later'
        $crr = new CrashReportRepository();
        $filters = $form->getData();
        $reports = $crr->searchReports($pageSize, $pageSize * $pageNum, ...$filters);
        $numReports = $crr->countReports(...$filters);
    }
}

echo $tpl->render('admin/index.html.twig', [
    'user' => $user, 'form' => $form, 'reports' => $reports, 'num_reports' => $numReports, 'current_page' => $pageNum,
    'page_size' => $pageSize, 'num_pages' => ceil($numReports / $pageSize), 'page_sizes' => $pageSizes,
    'urls' => array_merge($firewall->getAdminUrls(), [
        'form' => $router->generate(__FILE__, $form->getQueryStringParts(true))
    ]),
]);
