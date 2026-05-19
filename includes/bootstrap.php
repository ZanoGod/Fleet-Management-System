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

function schema_table_exists(mysqli $db, string $table): bool
{
    $statement = $db->prepare(
        'SELECT TABLE_NAME
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
         LIMIT 1'
    );

    if (!$statement instanceof mysqli_stmt) {
        return false;
    }

    $statement->bind_param('s', $table);
    $statement->execute();
    $result = $statement->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $statement->close();

    return $exists;
}

function schema_column(mysqli $db, string $table, string $column): ?array
{
    $statement = $db->prepare(
        'SELECT
            COLUMN_NAME AS Field,
            COLUMN_TYPE AS Type,
            IS_NULLABLE AS `Null`,
            COLUMN_KEY AS `Key`
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1'
    );

    if (!$statement instanceof mysqli_stmt) {
        return null;
    }

    $statement->bind_param('ss', $table, $column);
    $statement->execute();
    $result = $statement->get_result();
    $definition = $result instanceof mysqli_result ? ($result->fetch_assoc() ?: null) : null;
    $statement->close();

    return $definition;
}

function schema_index_exists(mysqli $db, string $table, string $indexName): bool
{
    $statement = $db->prepare(
        'SELECT INDEX_NAME
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND INDEX_NAME = ?
         LIMIT 1'
    );

    if (!$statement instanceof mysqli_stmt) {
        return false;
    }

    $statement->bind_param('ss', $table, $indexName);
    $statement->execute();
    $result = $statement->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $statement->close();

    return $exists;
}

function schema_constraint_exists(mysqli $db, string $table, string $constraintName): bool
{
    $statement = $db->prepare(
        'SELECT CONSTRAINT_NAME
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
         LIMIT 1'
    );

    if (!$statement instanceof mysqli_stmt) {
        return false;
    }

    $statement->bind_param('ss', $table, $constraintName);
    $statement->execute();
    $result = $statement->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $statement->close();

    return $exists;
}

function ensure_schema(mysqli $db): void
{
    $db->query(
        "CREATE TABLE IF NOT EXISTS operators (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL UNIQUE,
            phone_number VARCHAR(30) NULL,
            operator_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    $driverLicenseColumn = schema_column($db, 'drivers', 'license_no');

    if ($driverLicenseColumn !== null && strtoupper((string) ($driverLicenseColumn['Null'] ?? 'NO')) !== 'YES') {
        $db->query('ALTER TABLE drivers MODIFY license_no VARCHAR(100) NULL');
    }

    $bookingColumnUpdates = [
        'custom_car_name' => 'ALTER TABLE bookings ADD custom_car_name VARCHAR(255) NULL AFTER car_id',
        'custom_driver_name' => 'ALTER TABLE bookings ADD custom_driver_name VARCHAR(255) NULL AFTER driver_id',
        'operator_id' => 'ALTER TABLE bookings ADD operator_id INT UNSIGNED NULL AFTER custom_driver_name',
        'even_odd' => "ALTER TABLE bookings ADD even_odd ENUM('Even', 'Odd') NULL AFTER operator_name",
    ];

    foreach ($bookingColumnUpdates as $column => $sql) {
        if (schema_column($db, 'bookings', $column) === null) {
            $db->query($sql);
        }
    }

    $carIdColumn = schema_column($db, 'bookings', 'car_id');

    if ($carIdColumn !== null && strtoupper((string) ($carIdColumn['Null'] ?? 'NO')) !== 'YES') {
        $db->query('ALTER TABLE bookings MODIFY car_id INT UNSIGNED NULL');
    }

    $driverIdColumn = schema_column($db, 'bookings', 'driver_id');

    if ($driverIdColumn !== null && strtoupper((string) ($driverIdColumn['Null'] ?? 'NO')) !== 'YES') {
        $db->query('ALTER TABLE bookings MODIFY driver_id INT UNSIGNED NULL');
    }

    if (!schema_index_exists($db, 'bookings', 'idx_bookings_operator_id')) {
        $db->query('ALTER TABLE bookings ADD INDEX idx_bookings_operator_id (operator_id)');
    }

    if (!schema_constraint_exists($db, 'bookings', 'fk_bookings_operator')) {
        $db->query(
            'ALTER TABLE bookings
             ADD CONSTRAINT fk_bookings_operator
             FOREIGN KEY (operator_id) REFERENCES operators(id)
             ON UPDATE CASCADE
             ON DELETE RESTRICT'
        );
    }

    $db->query(
        "INSERT INTO operators (full_name, operator_status)
         SELECT DISTINCT TRIM(operator_name), 'Active'
         FROM bookings
         WHERE TRIM(COALESCE(operator_name, '')) <> ''
         ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)"
    );

    $db->query(
        "UPDATE bookings AS b
         INNER JOIN operators AS o
             ON o.full_name = b.operator_name
         SET b.operator_id = o.id
         WHERE b.operator_id IS NULL
           AND TRIM(COALESCE(b.operator_name, '')) <> ''"
    );
}

try {
    $db = Database::connect();
    $requiredTables = ['bookings', 'cars', 'drivers'];

    foreach ($requiredTables as $table) {
        if (!schema_table_exists($db, $table)) {
            throw new RuntimeException(
                'Database schema is outdated. Please import the latest database/fleet_management.sql file.'
            );
        }
    }

    ensure_schema($db);
} catch (Throwable $throwable) {
    $dbError = $throwable->getMessage();
}
