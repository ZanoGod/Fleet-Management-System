<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset_url(string $path): string
{
    $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);

    if (!is_file($absolutePath)) {
        return $normalizedPath;
    }

    return $normalizedPath . '?v=' . filemtime($absolutePath);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function old(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) ? trim((string) $source[$key]) : $default;
}

function format_display_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d M Y', $timestamp) : $date;
}

function format_compact_date_range(?string $startDate, ?string $endDate): string
{
    if (($startDate === null || $startDate === '') && ($endDate === null || $endDate === '')) {
        return '-';
    }

    $startTimestamp = $startDate !== null && $startDate !== '' ? strtotime($startDate) : false;
    $endTimestamp = $endDate !== null && $endDate !== '' ? strtotime($endDate) : false;

    if (!$startTimestamp || !$endTimestamp) {
        $parts = array_values(array_filter([
            format_display_date($startDate),
            format_display_date($endDate),
        ], static fn (string $value): bool => $value !== '-'));

        return $parts === [] ? '-' : implode(' - ', $parts);
    }

    if (date('Y-m-d', $startTimestamp) === date('Y-m-d', $endTimestamp)) {
        return date('d M Y', $startTimestamp);
    }

    if (date('Y-m', $startTimestamp) === date('Y-m', $endTimestamp)) {
        return date('d', $startTimestamp) . ' - ' . date('d M Y', $endTimestamp);
    }

    if (date('Y', $startTimestamp) === date('Y', $endTimestamp)) {
        return date('d M', $startTimestamp) . ' - ' . date('d M Y', $endTimestamp);
    }

    return date('d M Y', $startTimestamp) . ' - ' . date('d M Y', $endTimestamp);
}

function booking_assignment_statuses(): array
{
    return [
        'Pending',
        'Confirm',
        'In Service',
    ];
}

function booking_statuses(): array
{
    return [
        'Pending',
        'Confirm',
        'Completed',
        'Cancelled',
    ];
}

function status_badge_class(string $status): string
{
    return match (strtolower($status)) {
        'confirm' => 'status-confirm',
        'in service' => 'status-service',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default => 'status-pending',
    };
}


function booking_even_odd_options(): array
{
    return [
        'Even',
        'Odd',
    ];
}


function car_statuses(): array
{
    return [
        'Available',
        'Assigned',
        'Maintenance',
    ];
}

function operator_statuses(): array
{
    return [
        'Active',
        'Inactive',
    ];
}

function driver_statuses(): array
{
    return [
        'Available',
        'Assigned',
        'Leave',
        'Inactive',
    ];
}


function selected(mixed $actual, mixed $expected): string
{
    return (string) $actual === (string) $expected ? 'selected' : '';
}

function in_allowed_values(string $value, array $allowedValues): bool
{
    return in_array($value, $allowedValues, true);
}

function row_id_exists(array $rows, int $expectedId): bool
{
    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $expectedId) {
            return true;
        }
    }

    return false;
}

function find_row_by_id(array $rows, int $expectedId): ?array
{
    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $expectedId) {
            return $row;
        }
    }

    return null;
}

function app_navigation(): array
{
    return [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'path' => 'index.php',
            'description' => 'Overview and quick stats',
            // 'icon' => 'DB',
            'icon' => '<i class="bi bi-grid-1x2-fill"></i>',
        ],
        [
            'key' => 'bookings',
            'label' => 'Bookings',
            'path' => 'bookings.php',
            'description' => 'Trip assignments',
           // 'icon' => 'BK',
            'icon' => '<i class="bi bi-calendar-check-fill"></i>',
        ],
        [
            'key' => 'cars',
            'label' => 'Cars / Vehicles',
            'path' => 'cars.php',
            'description' => 'Vehicle master data',
          //  'icon' => 'CR',
            'icon' => '<i class="bi bi-car-front-fill"></i>',
        ],
        [
            'key' => 'drivers',
            'label' => 'Drivers',
            'path' => 'drivers.php',
            'description' => 'Driver directory',
           // 'icon' => 'DR',
           'icon' => '<i class="bi bi-person-vcard-fill"></i>',
        ],
        [
            'key' => 'operators',
            'label' => 'Operators',
            'path' => 'operators.php',
            'description' => 'Operator directory',
           // 'icon' => 'OP',
            'icon' => '<i class="bi bi-headset"></i>',
        ],
        [
            'key' => 'reports',
            'label' => 'Reports',
            'path' => 'reports.php',
            'description' => 'Status and planning',
            //'icon' => 'RP',
            'icon' => '<i class="bi bi-bar-chart-fill"></i>',
        ],
    ];
}

function nav_is_active(string $activePage, string $expected): string
{
    return $activePage === $expected ? 'is-active' : '';
}

function vehicle_status_class(string $status): string
{
    return match (strtolower($status)) {
        'available' => 'resource-available',
        'assigned' => 'resource-assigned',
        'maintenance' => 'resource-maintenance',
        default => 'resource-inactive',
    };
}

function driver_status_class(string $status): string
{
    return match (strtolower($status)) {
        'available' => 'resource-available',
        'assigned', 'on trip' => 'resource-assigned',
        'leave' => 'resource-maintenance',
        default => 'resource-inactive',
    };
}

function operator_status_class(string $status): string
{
    return match (strtolower($status)) {
        'active' => 'resource-available',
        default => 'resource-inactive',
    };
}


function fetch_cars_for_select(mysqli $db, bool $availableOnly = false): array
{
    $cars = [];
    $sql = 'SELECT id, car_type, plate_no, model_name, availability_status FROM cars';
    
    // If we only want available cars, append the WHERE clause
    if ($availableOnly) {
        $sql .= " WHERE availability_status = 'Available'";
    }
    
    $sql .= ' ORDER BY car_type ASC, plate_no ASC';
    
    $result = $db->query($sql);

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
    }

    return $cars;
}

function fetch_drivers_for_select(mysqli $db): array
{
    $drivers = [];
    $result = $db->query(
        'SELECT id, full_name, phone_number, driver_status
         FROM drivers
         ORDER BY full_name ASC'
    );

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }
    }

    return $drivers;
}

function fetch_operators_for_select(mysqli $db, bool $activeOnly = false): array
{
    $operators = [];
    $sql = 'SELECT id, full_name, phone_number, operator_status
            FROM operators';

    if ($activeOnly) {
        $sql .= " WHERE operator_status = 'Active'";
    }

    $sql .= ' ORDER BY full_name ASC';
    $result = $db->query($sql);

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $operators[] = $row;
        }
    }

    return $operators;
}

function fetch_resource_booking_assignments(mysqli $db, string $resourceColumn): array
{
    if (!in_array($resourceColumn, ['car_id', 'driver_id'], true)) {
        return [];
    }

    $assignments = [];
    $statuses = booking_assignment_statuses();
    $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
    $sql = "SELECT
                {$resourceColumn} AS resource_id,
                guest_company_name,
                start_date,
                end_date
            FROM bookings
            WHERE {$resourceColumn} IS NOT NULL
              AND status IN ({$placeholders})
            ORDER BY start_date ASC, end_date ASC, id ASC";

    $statement = $db->prepare($sql);

    if (!$statement instanceof mysqli_stmt) {
        return [];
    }

    bind_statement_params($statement, str_repeat('s', count($statuses)), $statuses);
    $statement->execute();
    $result = $statement->get_result();

    while ($row = $result->fetch_assoc()) {
        $resourceId = (int) ($row['resource_id'] ?? 0);

        if ($resourceId <= 0) {
            continue;
        }

        unset($row['resource_id']);
        $assignments[$resourceId][] = $row;
    }

    $statement->close();

    return $assignments;
}

function booking_car_display(array $booking): string
{
    $customCarName = trim((string) ($booking['custom_car_name'] ?? ''));

    if ($customCarName !== '') {
        return $customCarName;
    }

    $parts = array_values(array_filter([
        trim((string) ($booking['car_type'] ?? '')),
        trim((string) ($booking['plate_no'] ?? '')),
    ], static fn (?string $value): bool => $value !== null && $value !== ''));

    return $parts === [] ? '-' : implode(' | ', $parts);
}

function booking_driver_display(array $booking): string
{
    $customDriverName = trim((string) ($booking['custom_driver_name'] ?? ''));

    if ($customDriverName !== '') {
        return $customDriverName;
    }

    $driverName = trim((string) ($booking['driver_name'] ?? ''));

    return $driverName !== '' ? $driverName : '-';
}

function booking_operator_display(array $booking): string
{
    $operatorName = trim((string) ($booking['operator_full_name'] ?? $booking['operator_name'] ?? ''));

    return $operatorName !== '' ? $operatorName : '-';
}

function bind_statement_params(mysqli_stmt $statement, string $types, array $values): void
{
    $references = [$types];

    foreach ($values as $key => $value) {
        $references[] = &$values[$key];
    }

    $statement->bind_param(...$references);
}
