<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

function booking_statuses(): array
{
    return [
        'Pending',
        'Confirm',
        'In Service',
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

function car_statuses(): array
{
    return [
        'Available',
        'Assigned',
        'Maintenance',
        'Inactive',
    ];
}

function driver_statuses(): array
{
    return [
        'Available',
        'On Trip',
        'Leave',
        'Inactive',
    ];
}

function selected(mixed $actual, mixed $expected): string
{
    return (string) $actual === (string) $expected ? 'selected' : '';
}

function app_navigation(): array
{
    return [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'path' => 'index.php',
            'description' => 'Overview and quick stats',
            'icon' => 'DB',
        ],
        [
            'key' => 'bookings',
            'label' => 'Bookings',
            'path' => 'bookings.php',
            'description' => 'Trip assignments',
            'icon' => 'BK',
        ],
        [
            'key' => 'cars',
            'label' => 'Cars / Fleets',
            'path' => 'cars.php',
            'description' => 'Vehicle master data',
            'icon' => 'CR',
        ],
        [
            'key' => 'drivers',
            'label' => 'Drivers',
            'path' => 'drivers.php',
            'description' => 'Driver directory',
            'icon' => 'DR',
        ],
        [
            'key' => 'reports',
            'label' => 'Reports',
            'path' => 'reports.php',
            'description' => 'Status and planning',
            'icon' => 'RP',
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
        'on trip' => 'resource-assigned',
        'leave' => 'resource-maintenance',
        default => 'resource-inactive',
    };
}

function fetch_cars_for_select(mysqli $db): array
{
    $cars = [];
    $result = $db->query(
        'SELECT id, car_type, plate_no, model_name, availability_status
         FROM cars
         ORDER BY car_type ASC, plate_no ASC'
    );

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
        'SELECT id, full_name, license_no, driver_status
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

function bind_statement_params(mysqli_stmt $statement, string $types, array $values): void
{
    $references = [$types];

    foreach ($values as $key => $value) {
        $references[] = &$values[$key];
    }

    $statement->bind_param(...$references);
}
