<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Edit Booking';
$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid booking ID.');
    redirect('index.php');
}

if ($db === null) {
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="alert alert-warning shadow-sm">
        Database is not connected yet. Please import the SQL file and check <code>config/database.php</code>.
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    return;
}

$statement = $db->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the booking record.');
    redirect('index.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$result = $statement->get_result();
$booking = $result->fetch_assoc();
$statement->close();

if ($booking === null) {
    set_flash('danger', 'Booking record not found.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
        'id' => $id,
        'guest_company_name' => old($_POST, 'guest_company_name'),
        'car_type' => old($_POST, 'car_type'),
        'car_no' => old($_POST, 'car_no'),
        'operator_name' => old($_POST, 'operator_name'),
        'driver_name' => old($_POST, 'driver_name'),
        'start_date' => old($_POST, 'start_date'),
        'end_date' => old($_POST, 'end_date'),
        'status' => old($_POST, 'status', 'Pending'),
        'remark' => old($_POST, 'remark'),
    ];

    foreach (['guest_company_name', 'car_type', 'car_no', 'operator_name', 'driver_name', 'start_date', 'end_date', 'status'] as $field) {
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
             SET guest_company_name = ?, car_type = ?, car_no = ?, operator_name = ?, start_date = ?, end_date = ?, driver_name = ?, status = ?, remark = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $updateStatement->bind_param(
                'sssssssssi',
                $booking['guest_company_name'],
                $booking['car_type'],
                $booking['car_no'],
                $booking['operator_name'],
                $booking['start_date'],
                $booking['end_date'],
                $booking['driver_name'],
                $booking['status'],
                $booking['remark'],
                $id
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                set_flash('success', 'Booking updated successfully.');
                redirect('index.php');
            }

            $errors[] = 'Unable to update the booking.';
            $updateStatement->close();
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger shadow-sm">
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
