<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Add Booking';
$pageSummary = 'Select dates first to automatically filter available cars and drivers for your trip.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">All Bookings</a>';
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$activeBookings = []; // NEW: Array to hold current bookings for JS filtering
$booking = [
    'guest_company_name' => '',
    'car_id' => '',
    'driver_id' => '',
    'operator_id' => '',
    'operator_name' => '',
    'even_odd' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'Confirm',
    'remark' => '',
];

if ($db instanceof mysqli) {
    $cars = fetch_cars_for_select($db); 
    $drivers = fetch_drivers_for_select($db);
    $operators = fetch_operators_for_select($db);

    // Fetch all active bookings to pass to the frontend form for real-time date filtering
    $res = $db->query("SELECT car_id, driver_id, start_date, end_date FROM bookings WHERE status IN ('Pending', 'Confirm', 'In Service')");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $activeBookings[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'guest_company_name' => old($_POST, 'guest_company_name'),
        'car_id'             => old($_POST, 'car_id'),
        'driver_id'          => old($_POST, 'driver_id'),
        'operator_id'        => old($_POST, 'operator_id'),
        'operator_name'      => '',
        'even_odd'           => old($_POST, 'even_odd'),
        'start_date'         => old($_POST, 'start_date'),
        'end_date'           => old($_POST, 'end_date'),
        'status'             => old($_POST, 'status', 'Confirm'),
        'remark'             => old($_POST, 'remark'),
    ];

    foreach (['guest_company_name', 'car_id', 'driver_id', 'operator_id', 'start_date', 'end_date', 'status'] as $field) {
        if ($booking[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if ($booking['start_date'] !== '' && $booking['end_date'] !== '' && $booking['end_date'] < $booking['start_date']) {
        $errors[] = 'End date cannot be earlier than start date.';
    }

    if (!in_allowed_values($booking['status'], booking_statuses())) {
        $errors[] = 'Please choose a valid booking status.';
    }

    // --- CAR VALIDATION ---
    $finalCarId = null;
    if ($booking['car_id'] !== '') {
        $cId = (int) $booking['car_id'];
        $matchedCar = find_row_by_id($cars, $cId);
        
        if ($matchedCar === null) {
            $errors[] = 'Please choose a valid fleet car.';
        } elseif (strtolower($matchedCar['availability_status']) === 'maintenance') {
            // We no longer block 'Assigned' cars globally, only ones broken down in 'Maintenance'
            $errors[] = "The selected car ({$matchedCar['plate_no']}) is in maintenance.";
        } else {
            $finalCarId = $cId;
        }
    }

    // --- DRIVER VALIDATION ---
    $finalDriverId = null;
    if ($booking['driver_id'] !== '') {
        $dId = (int) $booking['driver_id'];
        $matchedDriver = find_row_by_id($drivers, $dId);

        if ($matchedDriver === null) {
            $errors[] = 'Please choose a valid fleet driver.';
        } elseif (in_array(strtolower($matchedDriver['driver_status'] ?? ''), ['leave', 'inactive'])) {
            $errors[] = "The selected driver is currently on leave or inactive.";
        } else {
            $finalDriverId = $dId;
        }
    }

    // ==========================================
    // BACKEND DATE OVERLAP CHECKER
    // ==========================================
    if ($errors === [] && $db instanceof mysqli && in_array($booking['status'], ['Pending', 'Confirm'])) {
        
        // 1. Check Car Overlap
        if ($finalCarId !== null) {
            $carCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings 
                 WHERE car_id = ? 
                 AND status IN ('Pending', 'Confirm', 'In Service') 
                 AND start_date <= ? AND end_date >= ?"
            );
            $carCheck->bind_param('iss', $finalCarId, $booking['end_date'], $booking['start_date']);
            $carCheck->execute();
            $carRes = $carCheck->get_result();
            if ($row = $carRes->fetch_assoc()) {
                $errors[] = "This Car is already booked from " . format_display_date($row['start_date']) . " to " . format_display_date($row['end_date']) . ".";
            }
            $carCheck->close();
        }

        // 2. Check Driver Overlap
        if ($finalDriverId !== null) {
            $drvCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings 
                 WHERE driver_id = ? 
                 AND status IN ('Pending', 'Confirm', 'In Service') 
                 AND start_date <= ? AND end_date >= ?"
            );
            $drvCheck->bind_param('iss', $finalDriverId, $booking['end_date'], $booking['start_date']);
            $drvCheck->execute();
            $drvRes = $drvCheck->get_result();
            if ($row = $drvRes->fetch_assoc()) {
                $errors[] = "This Driver is already booked from " . format_display_date($row['start_date']) . " to " . format_display_date($row['end_date']) . ".";
            }
            $drvCheck->close();
        }
    }

    $operator = find_row_by_id($operators, (int) $booking['operator_id']);
    if ($operator !== null) {
        $booking['operator_name'] = trim((string) ($operator['full_name'] ?? ''));
    }

    if ($errors === [] && $db instanceof mysqli) {
        $statement = $db->prepare(
            'INSERT INTO bookings
            (guest_company_name, car_id, custom_car_name, driver_id, custom_driver_name, operator_id, operator_name, even_odd, start_date, end_date, status, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if ($statement) {
            $emptyStr = '';
            $statement->bind_param(
                'sisisissssss',
                $booking['guest_company_name'], $finalCarId, $emptyStr, $finalDriverId, $emptyStr,
                $booking['operator_id'], $booking['operator_name'], $booking['even_odd'],
                $booking['start_date'], $booking['end_date'], $booking['status'], $booking['remark']
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Booking created successfully!');
                redirect('bookings.php');
            }
            $errors[] = 'Unable to save the booking. Please try again.';
            $statement->close();
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php
$formTitle = 'Add Fleet Booking';
$submitLabel = 'Save Booking';
require __DIR__ . '/includes/booking-form.php';
require __DIR__ . '/includes/footer.php';
?>
