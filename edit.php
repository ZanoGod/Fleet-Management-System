<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Edit Booking';
$pageSummary = 'Update trip assignment details. The system will check for overlapping dates.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">All Bookings</a>';
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$activeBookings = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0 || $db === null) {
    redirect('bookings.php');
}

$cars = fetch_cars_for_select($db); 
$drivers = fetch_drivers_for_select($db);
$operators = fetch_operators_for_select($db);
$activeBookings = fetch_active_booking_resources($db, $id);

$statement = $db->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
if ($statement) {
    $statement->bind_param('i', $id);
    $statement->execute();
    $oldBooking = $statement->get_result()->fetch_assoc();
    $statement->close();
}

if (empty($oldBooking)) {
    redirect('bookings.php');
}

$booking = $oldBooking; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'id' => $id,
        'guest_company_name' => old($_POST, 'guest_company_name'),
        'car_id'             => old($_POST, 'car_id'),
        'secondary_car_id'   => old($_POST, 'secondary_car_id'),
        'driver_id'          => old($_POST, 'driver_id'),
        'operator_id'        => old($_POST, 'operator_id'),
        'operator_name'      => '',
        'even_odd'           => old($_POST, 'even_odd'),
        'start_date'         => old($_POST, 'start_date'),
        'end_date'           => old($_POST, 'end_date'),
        'status'             => old($_POST, 'status', 'Pending'),
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

    $finalCarId = null;
    if ($booking['car_id'] !== '') {
        $carId = (int) $booking['car_id'];
        $matchedCar = find_row_by_id($cars, $carId);

        if ($matchedCar === null) {
            $errors[] = 'Please choose a valid Car Type 1.';
        } elseif (strtolower($matchedCar['availability_status']) === 'maintenance') {
            $errors[] = "The selected Car Type 1 ({$matchedCar['plate_no']}) is in maintenance.";
        } else {
            $finalCarId = $carId;
        }
    }

    $finalSecondaryCarId = null;
    if ($booking['secondary_car_id'] !== '') {
        $secondaryCarId = (int) $booking['secondary_car_id'];
        $matchedSecondaryCar = find_row_by_id($cars, $secondaryCarId);

        if ($matchedSecondaryCar === null) {
            $errors[] = 'Please choose a valid Car Type 2.';
        } elseif ($secondaryCarId === $finalCarId) {
            $errors[] = 'Car Type 1 and Car Type 2 must be different cars.';
        } elseif (strtolower($matchedSecondaryCar['availability_status']) === 'maintenance') {
            $errors[] = "The selected Car Type 2 ({$matchedSecondaryCar['plate_no']}) is in maintenance.";
        } else {
            $finalSecondaryCarId = $secondaryCarId;
        }
    }

    $finalDriverId = null;
    if ($booking['driver_id'] !== '') {
        $driverId = (int) $booking['driver_id'];
        $matchedDriver = find_row_by_id($drivers, $driverId);

        if ($matchedDriver === null) {
            $errors[] = 'Please choose a valid fleet driver.';
        } elseif (in_array(strtolower($matchedDriver['driver_status'] ?? ''), ['leave', 'inactive'])) {
            $errors[] = 'The selected driver is currently on leave or inactive.';
        } else {
            $finalDriverId = $driverId;
        }
    }

    $finalOperatorId = null;
    $operator = find_row_by_id($operators, (int) $booking['operator_id']);
    if ($booking['operator_id'] !== '' && $operator === null) {
        $errors[] = 'Please choose a valid operator.';
    } elseif ($operator !== null) {
        $finalOperatorId = (int) $booking['operator_id'];
        $booking['operator_name'] = trim((string) ($operator['full_name'] ?? ''));
    }

    // ==========================================
    // DATE OVERLAP CHECKER (EDIT MODE)
    // ==========================================
    if ($errors === [] && in_allowed_values($booking['status'], booking_assignment_statuses())) {
        
        // 1. Check Car Overlap (IGNORE CURRENT BOOKING ID)
        if ($finalCarId !== null) {
            $carCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings 
                 WHERE (car_id = ? OR secondary_car_id = ?) AND id != ?
                 AND status IN ('Pending', 'Confirm')
                 AND start_date <= ? AND end_date >= ?
                 LIMIT 1"
            );
            if ($carCheck instanceof mysqli_stmt) {
                $carCheck->bind_param('iiiss', $finalCarId, $finalCarId, $id, $booking['end_date'], $booking['start_date']);
                $carCheck->execute();
                $carRes = $carCheck->get_result();
                if ($row = $carRes->fetch_assoc()) {
                    $errors[] = "Car Type 1 is already booked from " . format_display_date($row['start_date']) . " to " . format_display_date($row['end_date']) . ".";
                }
                $carCheck->close();
            } else {
                $errors[] = 'Unable to validate Car Type 1 availability.';
            }
        }

        if ($finalSecondaryCarId !== null) {
            $secondaryCarCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings
                 WHERE (car_id = ? OR secondary_car_id = ?) AND id != ?
                 AND status IN ('Pending', 'Confirm')
                 AND start_date <= ? AND end_date >= ?
                 LIMIT 1"
            );
            if ($secondaryCarCheck instanceof mysqli_stmt) {
                $secondaryCarCheck->bind_param('iiiss', $finalSecondaryCarId, $finalSecondaryCarId, $id, $booking['end_date'], $booking['start_date']);
                $secondaryCarCheck->execute();
                $secondaryCarRes = $secondaryCarCheck->get_result();
                if ($row = $secondaryCarRes->fetch_assoc()) {
                    $errors[] = "Car Type 2 is already booked from " . format_display_date($row['start_date']) . " to " . format_display_date($row['end_date']) . ".";
                }
                $secondaryCarCheck->close();
            } else {
                $errors[] = 'Unable to validate Car Type 2 availability.';
            }
        }

        // 2. Check Driver Overlap (IGNORE CURRENT BOOKING ID)
        if ($finalDriverId !== null) {
            $drvCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings 
                 WHERE driver_id = ? AND id != ?
                 AND status IN ('Pending', 'Confirm')
                 AND start_date <= ? AND end_date >= ?
                 LIMIT 1"
            );
            if ($drvCheck instanceof mysqli_stmt) {
                $drvCheck->bind_param('iiss', $finalDriverId, $id, $booking['end_date'], $booking['start_date']);
                $drvCheck->execute();
                $drvRes = $drvCheck->get_result();
                if ($row = $drvRes->fetch_assoc()) {
                    $errors[] = "This Driver is already booked from " . format_display_date($row['start_date']) . " to " . format_display_date($row['end_date']) . ".";
                }
                $drvCheck->close();
            } else {
                $errors[] = 'Unable to validate driver availability.';
            }
        }
    }

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE bookings
             SET guest_company_name = ?, car_id = ?, custom_car_name = ?, secondary_car_id = ?, driver_id = ?, custom_driver_name = ?, operator_id = ?, operator_name = ?, even_odd = ?, start_date = ?, end_date = ?, status = ?, remark = ?
             WHERE id = ?'
        );

        if ($updateStatement) {
            $emptyStr = '';
            $updateStatement->bind_param(
                'sisiisissssssi',
                $booking['guest_company_name'], $finalCarId, $emptyStr, $finalSecondaryCarId, $finalDriverId, $emptyStr,
                $finalOperatorId, $booking['operator_name'], $booking['even_odd'],
                $booking['start_date'], $booking['end_date'], $booking['status'], $booking['remark'], $id
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                set_flash('success', 'Booking updated successfully.');
                redirect('bookings.php');
            }
            $errors[] = 'Unable to update the booking.';
            $updateStatement->close();
        } else {
            $errors[] = 'Failed to prepare the booking update query.';
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
