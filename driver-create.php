<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'drivers';
$pageTitle = 'Add Driver';
$pageSummary = 'Create a new driver master record.';
$pageActions = '<a class="btn btn-shell" href="drivers.php">All Drivers</a>';
$errors = [];
$driver = [
    'full_name' => '',
    'phone_number' => '',
    'driver_status' => 'Available',
    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = [
        'full_name' => old($_POST, 'full_name'),
        'phone_number' => old($_POST, 'phone_number'),
        'driver_status' => old($_POST, 'driver_status', 'Available'),
        'note' => old($_POST, 'note'),
    ];

    foreach (['full_name', 'phone_number', 'driver_status'] as $field) {
        if ($driver[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if (!in_allowed_values($driver['driver_status'], driver_statuses())) {
        $errors[] = 'Please choose a valid driver status.';
    }

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($errors === []) {
        if ($db === null) {
            $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
        } else {
            $statement = $db->prepare(
            'INSERT INTO drivers
            (full_name, phone_number, license_no, driver_status, note)
            VALUES (?, ?, NULL, ?, ?)'
        );

            if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $statement->bind_param(
                'ssss',
                $driver['full_name'],
                $driver['phone_number'],
                $driver['driver_status'],
                $driver['note']
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Driver added successfully.');
                redirect('drivers.php');
            }

            $errors[] = 'Unable to save the driver. Please try again.';
            }
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
$formTitle = 'Add Driver';
$submitLabel = 'Save Driver';
require __DIR__ . '/includes/driver-form.php';
require __DIR__ . '/includes/footer.php';
?>
