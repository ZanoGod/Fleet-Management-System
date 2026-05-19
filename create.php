<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Add Booking';
$errors = [];
$booking = [
    'guest_company_name' => '',
    'car_type' => '',
    'car_no' => '',
    'operator_name' => '',
    'driver_name' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'Pending',
    'remark' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = [
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

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'INSERT INTO bookings
            (guest_company_name, car_type, car_no, operator_name, start_date, end_date, driver_name, status, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $statement->bind_param(
                'sssssssss',
                $booking['guest_company_name'],
                $booking['car_type'],
                $booking['car_no'],
                $booking['operator_name'],
                $booking['start_date'],
                $booking['end_date'],
                $booking['driver_name'],
                $booking['status'],
                $booking['remark']
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Booking created successfully.');
                redirect('index.php');
            }

            $errors[] = 'Unable to save the booking. Please try again.';
            $statement->close();
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
$formTitle = 'Add Fleet Booking';
$submitLabel = 'Save Booking';
require __DIR__ . '/includes/booking-form.php';
require __DIR__ . '/includes/footer.php';
?>
