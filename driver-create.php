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
    'license_no' => '',
    'driver_status' => 'Available',
    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = [
        'full_name' => old($_POST, 'full_name'),
        'phone_number' => old($_POST, 'phone_number'),
        'license_no' => old($_POST, 'license_no'),
        'driver_status' => old($_POST, 'driver_status', 'Available'),
        'note' => old($_POST, 'note'),
    ];

    foreach (['full_name', 'phone_number', 'license_no', 'driver_status'] as $field) {
        if ($driver[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'INSERT INTO drivers
            (full_name, phone_number, license_no, driver_status, note)
            VALUES (?, ?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $statement->bind_param(
                'sssss',
                $driver['full_name'],
                $driver['phone_number'],
                $driver['license_no'],
                $driver['driver_status'],
                $driver['note']
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Driver added successfully.');
                redirect('drivers.php');
            }

            $errors[] = 'Unable to save the driver. License number may already exist.';
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
$formTitle = 'Add Driver';
$submitLabel = 'Save Driver';
require __DIR__ . '/includes/driver-form.php';
require __DIR__ . '/includes/footer.php';
?>
