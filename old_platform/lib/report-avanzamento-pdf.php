<?php
require_once 'sanitize.php';
require_once 'class_report_avanzamento_pdf.php';
/** Error reporting  */
error_reporting(E_ALL);
ini_set('display_errors', FALSE);
ini_set('display_startup_errors', FALSE);

$id = sanitize($_GET['id'], INT);
$course_id = sanitize($_GET['course_id'], INT);

$report = new iWDReportAvanzamentoPdf();
$report->generatePDF($id, $course_id);