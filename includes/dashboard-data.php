<?php

declare(strict_types=1);

function dashboard_default_state(): array
{
    return [
        'summary' => [
            'total_bookings' => 0,
            'active_bookings' => 0,
            'total_cars' => 0,
            'available_cars' => 0,
            'total_drivers' => 0,
            'available_drivers' => 0,
        ],
        'recentBookings' => [],
        'bookingStatusCounts' => array_fill_keys(booking_statuses(), 0),
        'carStatusCounts' => array_fill_keys(car_statuses(), 0),
        'driverStatusCounts' => array_fill_keys(driver_statuses(), 0),
    ];
}

function dashboard_fetch_summary(mysqli $db): array
{
    $defaultSummary = dashboard_default_state()['summary'];

    $summaryResult = $db->query(
        "SELECT
            (SELECT COUNT(*) FROM bookings) AS total_bookings,

            (SELECT COUNT(*) 
             FROM bookings 
             WHERE status = 'Confirm'
             AND end_date >= CURDATE()) AS active_bookings,

            (SELECT COUNT(*) FROM cars) AS total_cars,

            (SELECT COUNT(*) 
             FROM cars 
             WHERE availability_status = 'Available') AS available_cars,

            (SELECT COUNT(*) FROM drivers) AS total_drivers,

            (SELECT COUNT(*) 
             FROM drivers 
             WHERE driver_status = 'Available') AS available_drivers"
    );

    if (!$summaryResult instanceof mysqli_result) {
        return $defaultSummary;
    }

    return array_merge($defaultSummary, $summaryResult->fetch_assoc() ?: []);
}

function dashboard_fetch_recent_bookings(mysqli $db, int $limit = 6): array
{
    $recentBookings = [];
    $safeLimit = max(1, $limit);
    $recentResult = $db->query(
        "SELECT
            b.id,
            b.operator_id,
            b.guest_company_name,
            b.operator_name,
            b.custom_car_name,
            b.custom_driver_name,
            b.secondary_car_id,
            b.start_date,
            b.end_date,
            b.status,
            c.car_type,
            c.plate_no,
            c2.car_type AS secondary_car_type,
            c2.plate_no AS secondary_plate_no,
            d.full_name AS driver_name,
            o.full_name AS operator_full_name
         FROM bookings AS b
         LEFT JOIN cars AS c ON c.id = b.car_id
         LEFT JOIN cars AS c2 ON c2.id = b.secondary_car_id
         LEFT JOIN drivers AS d ON d.id = b.driver_id
         LEFT JOIN operators AS o ON o.id = b.operator_id
         ORDER BY b.id DESC
         LIMIT {$safeLimit}"
    );

    if (!$recentResult instanceof mysqli_result) {
        return $recentBookings;
    }

    while ($row = $recentResult->fetch_assoc()) {
        $recentBookings[] = $row;
    }

    return $recentBookings;
}

function dashboard_fetch_label_counts(mysqli $db, string $query, array $defaultCounts): array
{
    $counts = $defaultCounts;
    $result = $db->query($query);

    if (!$result instanceof mysqli_result) {
        return $counts;
    }

    while ($row = $result->fetch_assoc()) {
        $label = (string) ($row['label'] ?? '');

        if ($label === '') {
            continue;
        }

        $counts[$label] = (int) ($row['total'] ?? 0);
    }

    return $counts;
}

function load_dashboard_data(?mysqli $db): array
{
    $dashboardData = dashboard_default_state();

    if (!$db instanceof mysqli) {
        return $dashboardData;
    }

    $dashboardData['summary'] = dashboard_fetch_summary($db);
    $dashboardData['recentBookings'] = dashboard_fetch_recent_bookings($db);
    $dashboardData['bookingStatusCounts'] = dashboard_fetch_label_counts(
        $db,
        "SELECT status AS label, COUNT(*) AS total
         FROM bookings
         GROUP BY status",
        $dashboardData['bookingStatusCounts']
    );
    $dashboardData['carStatusCounts'] = dashboard_fetch_label_counts(
        $db,
        "SELECT availability_status AS label, COUNT(*) AS total
         FROM cars
         GROUP BY availability_status",
        $dashboardData['carStatusCounts']
    );
    $dashboardData['driverStatusCounts'] = dashboard_fetch_label_counts(
        $db,
        "SELECT driver_status AS label, COUNT(*) AS total
         FROM drivers
         GROUP BY driver_status",
        $dashboardData['driverStatusCounts']
    );

    return $dashboardData;
}
