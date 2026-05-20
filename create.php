<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Add Booking';
$pageSummary = 'Create a new trip assignment using saved operators and either saved or custom cars and drivers.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">All Bookings</a>';
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$booking = [
    'guest_company_name' => '',
    'car_value' => '',
    'car_id' => '',
    'custom_car_name' => '',
    'driver_value' => '',
    'driver_id' => '',
    'custom_driver_name' => '',
    'operator_id' => '',
    'operator_name' => '',
    'even_odd' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'Pending',
    'remark' => '',
];

if ($db instanceof mysqli) {
    $cars = fetch_cars_for_select($db);
    $drivers = fetch_drivers_for_select($db);
    $operators = fetch_operators_for_select($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'guest_company_name' => old($_POST, 'guest_company_name'),
        'car_value' => old($_POST, 'car_value'),
        'car_id' => old($_POST, 'car_id'),
        'custom_car_name' => '',
        'driver_value' => old($_POST, 'driver_value'),
        'driver_id' => old($_POST, 'driver_id'),
        'custom_driver_name' => '',
        'operator_id' => old($_POST, 'operator_id'),
        'operator_name' => old($_POST, 'operator_name'),
        'even_odd' => old($_POST, 'even_odd'),
        'start_date' => old($_POST, 'start_date'),
        'end_date' => old($_POST, 'end_date'),
        'status' => old($_POST, 'status', 'Pending'),
        'remark' => old($_POST, 'remark'),
    ];

    foreach (['guest_company_name', 'operator_id', 'start_date', 'end_date', 'status'] as $field) {
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

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($operators === []) {
        $errors[] = 'Please create at least one operator before saving a booking.';
    }

    if ($booking['even_odd'] !== '' && !in_allowed_values($booking['even_odd'], booking_even_odd_options())) {
        $errors[] = 'Please choose a valid Even / Odd value.';
    }

    $carId = null;
    $carValue = trim($booking['car_value']);

    if ($booking['car_id'] !== '') {
        $carId = (int) $booking['car_id'];

        if (!row_id_exists($cars, $carId)) {
            $errors[] = 'Please choose a valid car.';
        }
    } elseif ($carValue !== '') {
        $booking['custom_car_name'] = $carValue;
    } else {
        $errors[] = 'Please choose or type a car.';
    }

    $driverId = null;
    $driverValue = trim($booking['driver_value']);

    if ($booking['driver_id'] !== '') {
        $driverId = (int) $booking['driver_id'];

        if (!row_id_exists($drivers, $driverId)) {
            $errors[] = 'Please choose a valid driver.';
        }
    } elseif ($driverValue !== '') {
        $booking['custom_driver_name'] = $driverValue;
    } else {
        $errors[] = 'Please choose or type a driver.';
    }

    $operator = find_row_by_id($operators, (int) $booking['operator_id']);

    if ($operator === null) {
        $errors[] = 'Please choose a valid operator.';
    } else {
        $booking['operator_name'] = trim((string) ($operator['full_name'] ?? ''));
    }

    if ($errors === [] && $db instanceof mysqli) {
        $statement = $db->prepare(
            'INSERT INTO bookings
            (guest_company_name, car_id, custom_car_name, driver_id, custom_driver_name, operator_id, operator_name, even_odd, start_date, end_date, status, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $statement->bind_param(
                'sisisissssss',
                $booking['guest_company_name'],
                $carId,
                $booking['custom_car_name'],
                $driverId,
                $booking['custom_driver_name'],
                $booking['operator_id'],
                $booking['operator_name'],
                $booking['even_odd'],
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
