<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Edit Booking';
$pageSummary = 'Update trip assignment details for this booking record.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">All Bookings</a>';
$errors = [];
$cars = [];
$drivers = [];
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

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE bookings
             SET guest_company_name = ?, car_id = ?, driver_id = ?, operator_name = ?, start_date = ?, end_date = ?, status = ?, remark = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $carId = (int) $booking['car_id'];
            $driverId = (int) $booking['driver_id'];

            $updateStatement->bind_param(
                'siisssssi',
                $booking['guest_company_name'],
                $carId,
                $driverId,
                $booking['operator_name'],
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
