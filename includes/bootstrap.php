<?php

declare(strict_types=1);

$sessionPath = __DIR__ . '/../storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

// HARDENING: Inject secure directives before starting the session
session_start([
    'cookie_lifetime' => 0,                      // Ends when the browser closes
    'cookie_secure'   => isset($_SERVER['HTTPS']), // True if running on HTTPS
    'cookie_httponly' => true,                   // Blocks JavaScript cookie theft (XSS mitigation)
    'cookie_samesite' => 'Strict'                 // Blocks CSRF context requests
]);

// ==========================================
// NEW: SYSTEM SECURITY & AUTHENTICATION
// ==========================================
$currentPage = basename($_SERVER['PHP_SELF']);

// Allow these pages without logging in
$publicPages = [
    'login.php',
    'logout.php'
];

if (
    !isset($_SESSION['admin_logged_in']) ||
    $_SESSION['admin_logged_in'] !== true
) {
    if (!in_array($currentPage, $publicPages, true)) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Please log in to access the system.'
        ];

        header('Location: /fleet-management/login.php');
        exit;
    }
}

if (isset($_SESSION['admin_logged_in'])) {
    if (
        !isset($_SESSION['user_agent']) ||
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
    ) {
        $_SESSION = [];
        session_destroy();
        redirect('login.php');
    }
}
// ==========================================

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

function schema_foreign_key_target(mysqli $db, string $table, string $constraintName): ?array
{
    $statement = $db->prepare(
        'SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
           AND REFERENCED_TABLE_NAME IS NOT NULL
         LIMIT 1'
    );

    if (!$statement instanceof mysqli_stmt) {
        return null;
    }

    $statement->bind_param('ss', $table, $constraintName);
    $statement->execute();
    $result = $statement->get_result();
    $target = $result instanceof mysqli_result ? ($result->fetch_assoc() ?: null) : null;
    $statement->close();

    return is_array($target) ? $target : null;
}

function ensure_booking_status_schema(mysqli $db): void
{
    $statusColumn = schema_column($db, 'bookings', 'status');

    if ($statusColumn === null) {
        return;
    }

    $removedStatus = 'In' . ' Service';
    $columnType = (string) ($statusColumn['Type'] ?? '');

    if (stripos($columnType, $removedStatus) === false) {
        return;
    }

    $replacementStatus = 'Confirm';
    $statement = $db->prepare('UPDATE bookings SET status = ? WHERE status = ?');

    if ($statement instanceof mysqli_stmt) {
        $statement->bind_param('ss', $replacementStatus, $removedStatus);
        $statement->execute();
        $statement->close();
    }

    $db->query(
        "ALTER TABLE bookings
         MODIFY status ENUM('Pending', 'Confirm', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending'"
    );
}

function ensure_admin_schema(mysqli $db): void
{
    $db->query(
        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            email VARCHAR(150) NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    if (schema_column($db, 'admins', 'username') === null) {
        $db->query('ALTER TABLE admins ADD username VARCHAR(80) NULL AFTER id');
        $db->query(
            "UPDATE admins
             SET username = CASE
                 WHEN email IS NOT NULL AND email <> '' THEN SUBSTRING_INDEX(email, '@', 1)
                 ELSE CONCAT('admin', id)
             END
             WHERE username IS NULL OR username = ''"
        );
        $db->query('ALTER TABLE admins MODIFY username VARCHAR(80) NOT NULL');
    }

    if (schema_column($db, 'admins', 'email') === null) {
        $db->query('ALTER TABLE admins ADD email VARCHAR(150) NULL AFTER username');
    }

    $emailColumn = schema_column($db, 'admins', 'email');

    if ($emailColumn !== null && strtoupper((string) ($emailColumn['Null'] ?? 'YES')) !== 'YES') {
        $db->query('ALTER TABLE admins MODIFY email VARCHAR(150) NULL');
    }

    if (!schema_index_exists($db, 'admins', 'idx_admins_username')) {
        $db->query('ALTER TABLE admins ADD UNIQUE INDEX idx_admins_username (username)');
    }

    $username = 'admin';
    $checkStatement = $db->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');

    if ($checkStatement instanceof mysqli_stmt) {
        $checkStatement->bind_param('s', $username);
        $checkStatement->execute();
        $exists = $checkStatement->get_result()->num_rows > 0;
        $checkStatement->close();

        if ($exists) {
            return;
        }
    }

    $email = null;
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $statement = $db->prepare(
        'INSERT INTO admins (username, email, password_hash, is_active)
         VALUES (?, ?, ?, 1)'
    );

    if ($statement instanceof mysqli_stmt) {
        $statement->bind_param('sss', $username, $email, $passwordHash);
        $statement->execute();
        $statement->close();
    }
}

function ensure_monthly_contract_schema(mysqli $db): void
{
    $monthlyCarsExisted = schema_table_exists($db, 'monthly_cars');
    $monthlyDriversExisted = schema_table_exists($db, 'monthly_drivers');

    $db->query(
        "CREATE TABLE IF NOT EXISTS monthly_cars (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            car_type VARCHAR(100) NOT NULL,
            plate_no VARCHAR(50) NOT NULL UNIQUE,
            model_name VARCHAR(100) NOT NULL,
            seat_capacity TINYINT UNSIGNED NOT NULL DEFAULT 4,
            availability_status ENUM('Available', 'Assigned', 'Maintenance', 'Inactive') NOT NULL DEFAULT 'Available',
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    $db->query(
        "CREATE TABLE IF NOT EXISTS monthly_drivers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            phone_number VARCHAR(30) NOT NULL,
            license_no VARCHAR(100) NULL UNIQUE,
            driver_status ENUM('Available', 'Assigned', 'Leave', 'Inactive') NOT NULL DEFAULT 'Available',
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    if (!$monthlyCarsExisted) {
        $db->query(
            "INSERT IGNORE INTO monthly_cars
             (id, car_type, plate_no, model_name, seat_capacity, availability_status, note, created_at, updated_at)
             SELECT id, car_type, plate_no, model_name, seat_capacity, availability_status, note, created_at, updated_at
             FROM cars"
        );
    }

    if (!$monthlyDriversExisted) {
        $db->query(
            "INSERT IGNORE INTO monthly_drivers
             (id, full_name, phone_number, license_no, driver_status, note, created_at, updated_at)
             SELECT
                id,
                full_name,
                phone_number,
                license_no,
                CASE WHEN driver_status = 'On Trip' THEN 'Assigned' ELSE driver_status END,
                note,
                created_at,
                updated_at
             FROM drivers"
        );
    }

    $db->query(
        "CREATE TABLE IF NOT EXISTS monthly_contracts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contract_no VARCHAR(50) NOT NULL UNIQUE,
            customer_name VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50) NULL,
            customer_email VARCHAR(150) NULL,
            customer_address TEXT NULL,
            assignment_type ENUM('Customer Only', 'Vehicle Only', 'Vehicle and Driver') NOT NULL DEFAULT 'Vehicle Only',
            car_id INT UNSIGNED NULL,
            driver_id INT UNSIGNED NULL,
            operator_id INT UNSIGNED NULL,
            operator_name VARCHAR(150) NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            monthly_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            security_deposit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            payment_status ENUM('Pending', 'Paid', 'Overdue') NOT NULL DEFAULT 'Pending',
            status ENUM('Draft', 'Active', 'Expired', 'Renewed', 'Cancelled') NOT NULL DEFAULT 'Active',
            renewal_parent_id INT UNSIGNED NULL,
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_monthly_contracts_car_id (car_id),
            INDEX idx_monthly_contracts_driver_id (driver_id),
            INDEX idx_monthly_contracts_operator_id (operator_id),
            INDEX idx_monthly_contracts_status_end_date (status, end_date),
            INDEX idx_monthly_contracts_renewal_parent_id (renewal_parent_id),
            CONSTRAINT fk_monthly_contracts_car
                FOREIGN KEY (car_id) REFERENCES monthly_cars(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL,
            CONSTRAINT fk_monthly_contracts_driver
                FOREIGN KEY (driver_id) REFERENCES monthly_drivers(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL,
            CONSTRAINT fk_monthly_contracts_operator
                FOREIGN KEY (operator_id) REFERENCES operators(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL,
            CONSTRAINT fk_monthly_contracts_parent
                FOREIGN KEY (renewal_parent_id) REFERENCES monthly_contracts(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
        )"
    );

    if (schema_column($db, 'monthly_contracts', 'operator_id') === null) {
        $db->query('ALTER TABLE monthly_contracts ADD operator_id INT UNSIGNED NULL AFTER driver_id');
    }

    if (schema_column($db, 'monthly_contracts', 'operator_name') === null) {
        $db->query('ALTER TABLE monthly_contracts ADD operator_name VARCHAR(150) NULL AFTER operator_id');
    }

    if (
        schema_table_exists($db, 'customers')
        && schema_column($db, 'monthly_contracts', 'customer_id') === null
    ) {
        $db->query('ALTER TABLE monthly_contracts ADD customer_id INT UNSIGNED NULL AFTER contract_no');
    }

    if (!schema_index_exists($db, 'monthly_contracts', 'idx_monthly_contracts_operator_id')) {
        $db->query('ALTER TABLE monthly_contracts ADD INDEX idx_monthly_contracts_operator_id (operator_id)');
    }

    if (
        schema_table_exists($db, 'customers')
        && !schema_index_exists($db, 'monthly_contracts', 'idx_monthly_contracts_customer_id')
        && !schema_constraint_exists($db, 'monthly_contracts', 'fk_monthly_contract_customer')
    ) {
        $db->query('ALTER TABLE monthly_contracts ADD INDEX idx_monthly_contracts_customer_id (customer_id)');
    }

    $db->query(
        "INSERT IGNORE INTO monthly_cars
         (id, car_type, plate_no, model_name, seat_capacity, availability_status, note, created_at, updated_at)
         SELECT c.id, c.car_type, c.plate_no, c.model_name, c.seat_capacity, c.availability_status, c.note, c.created_at, c.updated_at
         FROM cars AS c
         INNER JOIN monthly_contracts AS mc ON mc.car_id = c.id
         WHERE mc.car_id IS NOT NULL"
    );

    $db->query(
        "INSERT IGNORE INTO monthly_drivers
         (id, full_name, phone_number, license_no, driver_status, note, created_at, updated_at)
         SELECT
            d.id,
            d.full_name,
            d.phone_number,
            d.license_no,
            CASE WHEN d.driver_status = 'On Trip' THEN 'Assigned' ELSE d.driver_status END,
            d.note,
            d.created_at,
            d.updated_at
         FROM drivers AS d
         INNER JOIN monthly_contracts AS mc ON mc.driver_id = d.id
         WHERE mc.driver_id IS NOT NULL"
    );

    $foreignKeys = [
        'fk_monthly_contracts_car' => [
            'column' => 'car_id',
            'table' => 'monthly_cars',
        ],
        'fk_monthly_contracts_driver' => [
            'column' => 'driver_id',
            'table' => 'monthly_drivers',
        ],
        'fk_monthly_contracts_operator' => [
            'column' => 'operator_id',
            'table' => 'operators',
        ],
    ];

    foreach ($foreignKeys as $constraintName => $definition) {
        $target = schema_foreign_key_target($db, 'monthly_contracts', $constraintName);

        if (
            $target !== null
            && (string) ($target['REFERENCED_TABLE_NAME'] ?? '') !== $definition['table']
        ) {
            $db->query("ALTER TABLE monthly_contracts DROP FOREIGN KEY {$constraintName}");
        }

        if (!schema_constraint_exists($db, 'monthly_contracts', $constraintName)) {
            $db->query(
                "ALTER TABLE monthly_contracts
                 ADD CONSTRAINT {$constraintName}
                 FOREIGN KEY ({$definition['column']}) REFERENCES {$definition['table']}(id)
                 ON UPDATE CASCADE
                 ON DELETE SET NULL"
            );
        }
    }

    if (
        schema_table_exists($db, 'customers')
        && !schema_constraint_exists($db, 'monthly_contracts', 'fk_monthly_contracts_customer')
        && !schema_constraint_exists($db, 'monthly_contracts', 'fk_monthly_contract_customer')
    ) {
        $db->query(
            'ALTER TABLE monthly_contracts
             ADD CONSTRAINT fk_monthly_contracts_customer
             FOREIGN KEY (customer_id) REFERENCES customers(id)
             ON UPDATE CASCADE
             ON DELETE SET NULL'
        );
    }

    if (!schema_constraint_exists($db, 'monthly_contracts', 'fk_monthly_contracts_parent')) {
        $db->query(
            'ALTER TABLE monthly_contracts
             ADD CONSTRAINT fk_monthly_contracts_parent
             FOREIGN KEY (renewal_parent_id) REFERENCES monthly_contracts(id)
             ON UPDATE CASCADE
             ON DELETE SET NULL'
        );
    }

    $db->query(
        "UPDATE monthly_contracts AS mc
         INNER JOIN operators AS o ON o.id = mc.operator_id
         SET mc.operator_name = o.full_name
         WHERE mc.operator_id IS NOT NULL
           AND (mc.operator_name IS NULL OR mc.operator_name = '')"
    );

    if (schema_table_exists($db, 'customers')) {
        // Older contracts stored only a customer-name snapshot. Link them when
        // there is exactly one matching customer so status synchronisation and
        // delete protection work without guessing between duplicate names.
        $db->query(
            "UPDATE monthly_contracts AS mc
             INNER JOIN (
                SELECT MIN(id) AS id, company_name
                FROM customers
                WHERE company_name IS NOT NULL
                  AND company_name <> ''
                GROUP BY company_name
                HAVING COUNT(*) = 1
             ) AS customer_match ON customer_match.company_name = mc.customer_name
             SET mc.customer_id = customer_match.id
             WHERE mc.customer_id IS NULL"
        );

        $db->query(
            "UPDATE customers AS c
             INNER JOIN monthly_contracts AS mc ON mc.customer_id = c.id
             SET c.customer_status = 'Active'
             WHERE mc.status = 'Active'
               AND c.customer_status <> 'Active'"
        );
    }
}

function ensure_schema(mysqli $db): void
{
    ensure_admin_schema($db);

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
        'secondary_car_id' => 'ALTER TABLE bookings ADD secondary_car_id INT UNSIGNED NULL AFTER custom_car_name',
        'custom_driver_name' => 'ALTER TABLE bookings ADD custom_driver_name VARCHAR(255) NULL AFTER driver_id',
        'operator_id' => 'ALTER TABLE bookings ADD operator_id INT UNSIGNED NULL AFTER custom_driver_name',
        'even_odd' => "ALTER TABLE bookings ADD even_odd ENUM('Even', 'Odd') NULL AFTER operator_name",
    ];

    foreach ($bookingColumnUpdates as $column => $sql) {
        if (schema_column($db, 'bookings', $column) === null) {
            $db->query($sql);
        }
    }

    ensure_booking_status_schema($db);

    $carIdColumn = schema_column($db, 'bookings', 'car_id');

    if ($carIdColumn !== null && strtoupper((string) ($carIdColumn['Null'] ?? 'NO')) !== 'YES') {
        $db->query('ALTER TABLE bookings MODIFY car_id INT UNSIGNED NULL');
    }

    $secondaryCarIdColumn = schema_column($db, 'bookings', 'secondary_car_id');

    if ($secondaryCarIdColumn !== null && strtoupper((string) ($secondaryCarIdColumn['Null'] ?? 'NO')) !== 'YES') {
        $db->query('ALTER TABLE bookings MODIFY secondary_car_id INT UNSIGNED NULL');
    }

    $driverIdColumn = schema_column($db, 'bookings', 'driver_id');

    if ($driverIdColumn !== null && strtoupper((string) ($driverIdColumn['Null'] ?? 'NO')) !== 'YES') {
        $db->query('ALTER TABLE bookings MODIFY driver_id INT UNSIGNED NULL');
    }

    if (!schema_index_exists($db, 'bookings', 'idx_bookings_secondary_car_id')) {
        $db->query('ALTER TABLE bookings ADD INDEX idx_bookings_secondary_car_id (secondary_car_id)');
    }

    if (!schema_constraint_exists($db, 'bookings', 'fk_bookings_secondary_car')) {
        $db->query(
            'ALTER TABLE bookings
             ADD CONSTRAINT fk_bookings_secondary_car
             FOREIGN KEY (secondary_car_id) REFERENCES cars(id)
             ON UPDATE CASCADE
             ON DELETE SET NULL'
        );
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
         WHERE operator_id IS NULL
           AND TRIM(COALESCE(operator_name, '')) <> ''
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

    ensure_monthly_contract_schema($db);
}

// Only connect to DB if we are NOT on the login page, OR if we are doing something else
// (It's safe to connect anyway, but we check schema to be sure)
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
