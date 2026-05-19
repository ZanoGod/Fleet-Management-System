<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

$db = null;
$dbError = null;

try {
    $db = Database::connect();
} catch (Throwable $throwable) {
    $dbError = $throwable->getMessage();
}
