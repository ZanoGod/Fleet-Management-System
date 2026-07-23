<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'monthly-drivers';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Module';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'MC';
$pageKicker = 'Monthly Contract Module';
$pageTitle = 'Edit Monthly Driver';
$pageSummary = 'Update a monthly-only driver record.';
$pageActions = '<a class="btn btn-dashboard" href="drivers.php">All Monthly Drivers</a>';
$pageStyles = ['assets/css/drivers.css', 'assets/css/monthly-contracts.css'];
$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid monthly driver ID.');
    redirect('monthly-contract-drivers.php');
}

if (!($db instanceof mysqli)) {
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/messages.php';
    require __DIR__ . '/../includes/footer.php';
    return;
}

$statement = $db->prepare('SELECT * FROM monthly_drivers WHERE id = ? LIMIT 1');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the monthly driver record.');
    redirect('monthly-contract-drivers.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$driver = $statement->get_result()->fetch_assoc();
$statement->close();

if ($driver === null) {
    set_flash('danger', 'Monthly driver record not found.');
    redirect('monthly-contract-drivers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = [
        'id' => $id,
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

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE monthly_drivers
             SET full_name = ?, phone_number = ?, license_no = NULL, driver_status = ?, note = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $updateStatement->bind_param(
                'ssssi',
                $driver['full_name'],
                $driver['phone_number'],
                $driver['driver_status'],
                $driver['note'],
                $id
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                set_flash('success', 'Monthly driver updated successfully.');
                redirect('monthly-contract-drivers.php');
            }

            $errors[] = 'Unable to update the monthly driver.';
            $updateStatement->close();
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
$formTitle = 'Edit Monthly Driver';
$submitLabel = 'Update Driver';
$backUrl = 'drivers.php';
$backLabel = 'Back to Monthly Drivers';
$formLead = 'Maintain driver master data for monthly contract assignments.';
$assignmentNoteTitle = 'Monthly dates stay in Monthly Contracts.';
$assignmentNoteBody = 'Customer, contract number, start date, and end date are linked from monthly contract records automatically, so update those details from';
$assignmentNoteLinkUrl = 'monthly-contracts.php';
$assignmentNoteLinkText = 'Monthly Contracts';
require __DIR__ . '/../includes/driver-form.php';
require __DIR__ . '/../includes/footer.php';
?>
