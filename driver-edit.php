<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'drivers';
$pageTitle = 'Edit Driver';
$pageSummary = 'Update driver details and availability.';
$pageActions = '<a class="btn btn-shell" href="drivers.php">All Drivers</a>';
$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid driver ID.');
    redirect('drivers.php');
}

if ($db === null) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/messages.php';
    require __DIR__ . '/includes/footer.php';
    return;
}

$statement = $db->prepare('SELECT * FROM drivers WHERE id = ? LIMIT 1');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the driver record.');
    redirect('drivers.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$result = $statement->get_result();
$driver = $result->fetch_assoc();
$statement->close();

if ($driver === null) {
    set_flash('danger', 'Driver record not found.');
    redirect('drivers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = [
        'id' => $id,
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

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE drivers
             SET full_name = ?, phone_number = ?, license_no = ?, driver_status = ?, note = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $updateStatement->bind_param(
                'sssssi',
                $driver['full_name'],
                $driver['phone_number'],
                $driver['license_no'],
                $driver['driver_status'],
                $driver['note'],
                $id
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                set_flash('success', 'Driver updated successfully.');
                redirect('drivers.php');
            }

            $errors[] = 'Unable to update the driver. License number may already exist.';
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
$formTitle = 'Edit Driver';
$submitLabel = 'Update Driver';
require __DIR__ . '/includes/driver-form.php';
require __DIR__ . '/includes/footer.php';
?>
