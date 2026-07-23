<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'monthly-drivers';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Driver';
$pageSummary = 'Create a driver record that is only used for monthly contracts.';
$pageActions = '<a class="btn btn-dashboard" href="drivers.php">All Monthly Drivers</a>';
$pageStyles = ['assets/css/drivers.css', 'assets/css/monthly-contracts.css'];
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

    if (!($db instanceof mysqli)) {
        $errors[] = 'Database is not connected yet. Please check the database settings.';
    }

    if ($errors === [] && $db instanceof mysqli) {
        $statement = $db->prepare(
            'INSERT INTO monthly_drivers
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
                set_flash('success', 'Monthly driver added successfully.');
                redirect('drivers.php');
            }

            $errors[] = 'Unable to save the monthly driver. Please try again.';
            $statement->close();
        }
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
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
$formTitle = 'Add Monthly Driver';
$submitLabel = 'Save Driver';
$backUrl = 'drivers.php';
$backLabel = 'Back to Drivers';
$formLead = 'Maintain driver master data for monthly contract assignments.';
$assignmentNoteTitle = 'Monthly dates stay in Monthly Contracts.';
$assignmentNoteBody = 'Customer, contract number, start date, and end date are linked from monthly contract records automatically, so update those details from';
$assignmentNoteLinkUrl = 'monthly-contracts.php';
$assignmentNoteLinkText = 'Monthly Contracts';
require __DIR__ . '/../includes/driver-form.php';
require __DIR__ . '/../includes/footer.php';
?>
