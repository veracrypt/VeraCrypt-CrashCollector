<?php

require_once(__DIR__ . '/../../autoload.php');

use Veracrypt\CrashCollector\Form\CrashReportSearchForm;
use Veracrypt\CrashCollector\Repository\CrashReportRepository;
use Veracrypt\CrashCollector\Router;
use Veracrypt\CrashCollector\Templating;

/// @todo get from .env?
$pageSizes = [10, 25, 50];

/// @todo add ACLs

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

$tpl = new Templating();
$router = new Router();
echo $tpl->render('admin/index.html.twig', [
    'form' => $form, 'reports' => $reports, 'num_reports' => $numReports, 'current_page' => $pageNum,
    'page_size' => $pageSize, 'num_pages' => ceil($numReports / $pageSize), 'page_sizes' => $pageSizes,
    'form_url' => $router->generate(__FILE__, $form->getQueryStringParts(true)),
]);
