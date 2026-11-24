<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
header('Location: ' . BASE_URL . 'reports/monthly_report.php');
exit;
