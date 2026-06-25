<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

// Get parameters
$report_type = $_GET['report_type'] ?? 'sales_summary';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Include the PDF generation function from export_reports.php
require_once 'export_reports.php';

// Generate and output the PDF HTML
$html = generatePDFHTML(getDatabaseConnection(), $report_type, $start_date, $end_date);
echo $html;
?>