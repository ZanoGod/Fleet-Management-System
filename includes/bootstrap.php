<?php

declare(strict_types=1);

$sessionPath = __DIR__ . '/../storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

$db = null;
$dbError = null;

try {
    $db = Database::connect();

    $requiredTables = ['bookings', 'cars', 'drivers'];

    foreach ($requiredTables as $table) {
        $tableResult = $db->query("SHOW TABLES LIKE '{$table}'");

        if (!$tableResult instanceof mysqli_result || $tableResult->num_rows === 0) {
            $dbError = 'Database schema is outdated. Please import the latest database/fleet_management.sql file.';
            $db = null;
            break;
        }
    }

    if ($db instanceof mysqli) {
        $columnChecks = [
            ['table' => 'bookings', 'column' => 'car_id'],
            ['table' => 'bookings', 'column' => 'driver_id'],
        ];

        foreach ($columnChecks as $check) {
            $columnResult = $db->query(
                "SHOW COLUMNS FROM {$check['table']} LIKE '{$check['column']}'"
            );

            if (!$columnResult instanceof mysqli_result || $columnResult->num_rows === 0) {
                $dbError = 'Database schema is outdated. Please import the latest database/fleet_management.sql file.';
                $db = null;
                break;
            }
        }
    }
} catch (Throwable $throwable) {
    $dbError = $throwable->getMessage();
}
