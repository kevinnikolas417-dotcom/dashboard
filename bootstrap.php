<?php
session_start();

$config = require __DIR__ . '/../config.php';

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/SpreadsheetReader.php';
require_once __DIR__ . '/ImportService.php';
require_once __DIR__ . '/Dashboard.php';

$db = new Database($config);
$auth = new Auth($db, $config);
$auth->ensureInitialAdmin();

$dashboard = new Dashboard($db);
$importer = new ImportService($db);

$flashSuccess = '';
$flashError = '';
