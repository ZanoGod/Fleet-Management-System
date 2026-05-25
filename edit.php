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
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0 || $db === null) {
    redirect('bookings.php');
}

$cars = fetch_cars_for_select($db); 
$drivers = fetch_drivers_for_select($db);
$operators = fetch_operators_for_select($db);

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

    $finalCarId = $booking['car_id'] !== '' ? (int) $booking['car_id'] : null;
    $finalDriverId = $booking['driver_id'] !== '' ? (int) $booking['driver_id'] : null;

    // ==========================================
    // DATE OVERLAP CHECKER (EDIT MODE)
    // ==========================================
    if ($errors === [] && in_array($booking['status'], ['Pending', 'Confirm'])) {
        
        // 1. Check Car Overlap (IGNORE CURRENT BOOKING ID)
        if ($finalCarId !== null) {
            $carCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings 
                 WHERE car_id = ? AND id != ?
                 AND status IN ('Pending', 'Confirm') 
                 AND start_date <= ? AND end_date >= ?"
            );
            $carCheck->bind_param('iiss', $finalCarId, $id, $booking['end_date'], $booking['start_date']);
            $carCheck->execute();
            $carRes = $carCheck->get_result();
            if ($row = $carRes->fetch_assoc()) {
                $errors[] = "This Car is already booked from " . format_display_date($row['start_date']) . " to " . format_display_date($row['end_date']) . ".";
            }
            $carCheck->close();
        }

        // 2. Check Driver Overlap (IGNORE CURRENT BOOKING ID)
        if ($finalDriverId !== null) {
            $drvCheck = $db->prepare(
                "SELECT start_date, end_date FROM bookings 
                 WHERE driver_id = ? AND id != ?
                 AND status IN ('Pending', 'Confirm') 
                 AND start_date <= ? AND end_date >= ?"
            );
            $drvCheck->bind_param('iiss', $finalDriverId, $id, $booking['end_date'], $booking['start_date']);
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

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE bookings
             SET guest_company_name = ?, car_id = ?, custom_car_name = ?, driver_id = ?, custom_driver_name = ?, operator_id = ?, operator_name = ?, even_odd = ?, start_date = ?, end_date = ?, status = ?, remark = ?
             WHERE id = ?'
        );

        if ($updateStatement) {
            $emptyStr = '';
            $updateStatement->bind_param(
                'sisisissssssi',
                $booking['guest_company_name'], $finalCarId, $emptyStr, $finalDriverId, $emptyStr,
                $booking['operator_id'], $booking['operator_name'], $booking['even_odd'],
                $booking['start_date'], $booking['end_date'], $booking['status'], $booking['remark'], $id
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