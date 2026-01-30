<?php
require_once 'sanitize.php';
require_once 'class_report_purchases_pdf.php';
/** Error reporting  */
error_reporting(E_ALL);
ini_set('display_errors', FALSE);
ini_set('display_startup_errors', FALSE);

$id = sanitize($_GET['id'], INT);

$report_acquisti = new iWDReportPurchasesPdf();
$report_acquisti->generatePDF($id);