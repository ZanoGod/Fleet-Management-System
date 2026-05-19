<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Add Booking';
$pageSummary = 'Create a new trip assignment using saved cars and drivers.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">All Bookings</a>';
$errors = [];
$cars = [];
$drivers = [];
$booking = [
    'guest_company_name' => '',
    'car_id' => '',
    'driver_id' => '',
    'operator_name' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'Pending',
    'remark' => '',
];

if ($db instanceof mysqli) {
    $cars = fetch_cars_for_select($db);
    $drivers = fetch_drivers_for_select($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'guest_company_name' => old($_POST, 'guest_company_name'),
        'car_id' => old($_POST, 'car_id'),
        'driver_id' => old($_POST, 'driver_id'),
        'operator_name' => old($_POST, 'operator_name'),
        'start_date' => old($_POST, 'start_date'),
        'end_date' => old($_POST, 'end_date'),
        'status' => old($_POST, 'status', 'Pending'),
        'remark' => old($_POST, 'remark'),
    ];

    foreach (['guest_company_name', 'car_id', 'driver_id', 'operator_name', 'start_date', 'end_date', 'status'] as $field) {
        if ($booking[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if ($booking['start_date'] !== '' && $booking['end_date'] !== '' && $booking['end_date'] < $booking['start_date']) {
        $errors[] = 'End date cannot be earlier than start date.';
    }

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($cars === [] || $drivers === []) {
        $errors[] = 'Please create at least one car and one driver before saving a booking.';
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'INSERT INTO bookings
            (guest_company_name, car_id, driver_id, operator_name, start_date, end_date, status, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $carId = (int) $booking['car_id'];
            $driverId = (int) $booking['driver_id'];

            $statement->bind_param(
                'siisssss',
                $booking['guest_company_name'],
                $carId,
                $driverId,
                $booking['operator_name'],
                $booking['start_date'],
                $booking['end_date'],
                $booking['status'],
                $booking['remark']
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Booking created successfully.');
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
