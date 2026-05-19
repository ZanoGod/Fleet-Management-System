<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Edit Booking';
$pageSummary = 'Update trip assignment details with saved operators and flexible car and driver inputs.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">All Bookings</a>';
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid booking ID.');
    redirect('bookings.php');
}

if ($db === null) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/messages.php';
    require __DIR__ . '/includes/footer.php';
    return;
}

$cars = fetch_cars_for_select($db);
$drivers = fetch_drivers_for_select($db);
$operators = fetch_operators_for_select($db);

$statement = $db->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the booking record.');
    redirect('bookings.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$result = $statement->get_result();
$booking = $result->fetch_assoc();
$statement->close();

if ($booking === null) {
    set_flash('danger', 'Booking record not found.');
    redirect('bookings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'id' => $id,
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

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE bookings
             SET guest_company_name = ?, car_id = ?, custom_car_name = ?, driver_id = ?, custom_driver_name = ?, operator_id = ?, operator_name = ?, even_odd = ?, start_date = ?, end_date = ?, status = ?, remark = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $updateStatement->bind_param(
                'sisisissssssi',
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
                $booking['remark'],
                $id
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                set_flash('success', 'Booking updated successfully.');
                redirect('bookings.php');
            }

            $errors[] = 'Unable to update the booking.';
            $updateStatement->close();
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
$formTitle = 'Edit Fleet Booking';
$submitLabel = 'Update Booking';
require __DIR__ . '/includes/booking-form.php';
require __DIR__ . '/includes/footer.php';
?>
