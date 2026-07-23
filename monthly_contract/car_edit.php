<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'contract-cars';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Edit Monthly Vehicle';
$pageSummary = 'Update a monthly-only vehicle record.';
$pageActions = '<a class="btn btn-dashboard" href="contract_cars.php">All Monthly Vehicles</a>';
$pageStyles = ['assets/css/cars.css', 'assets/css/monthly-contracts.css'];
$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid monthly vehicle ID.');
    redirect('monthly-contract-cars.php');
}

if (!($db instanceof mysqli)) {
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/messages.php';
    require __DIR__ . '/../includes/footer.php';
    return;
}

$statement = $db->prepare('SELECT * FROM monthly_cars WHERE id = ? LIMIT 1');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the monthly vehicle record.');
    redirect('monthly-contract-cars.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$car = $statement->get_result()->fetch_assoc();
$statement->close();

if ($car === null) {
    set_flash('danger', 'Monthly vehicle record not found.');
    redirect('monthly-contract-cars.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car = [
        'id' => $id,
        'car_type' => old($_POST, 'car_type'),
        'plate_no' => old($_POST, 'plate_no'),
        'model_name' => old($_POST, 'model_name'),
        'seat_capacity' => old($_POST, 'seat_capacity', '4'),
        'availability_status' => old($_POST, 'availability_status', 'Available'),
        'note' => old($_POST, 'note'),
    ];

    foreach (['car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status'] as $field) {
        if ($car[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if (filter_var($car['seat_capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors[] = 'Seat capacity must be greater than zero.';
    }

    if (!in_allowed_values($car['availability_status'], car_statuses())) {
        $errors[] = 'Please choose a valid car status.';
    }

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE monthly_cars
             SET car_type = ?, plate_no = ?, model_name = ?, seat_capacity = ?, availability_status = ?, note = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $seatCapacity = (int) $car['seat_capacity'];
            $updateStatement->bind_param(
                'sssissi',
                $car['car_type'],
                $car['plate_no'],
                $car['model_name'],
                $seatCapacity,
                $car['availability_status'],
                $car['note'],
                $id
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();
                set_flash('success', 'Monthly vehicle updated successfully.');
                redirect('monthly-contract-cars.php');
            }

            $errors[] = 'Unable to update the monthly vehicle. Plate number may already exist.';
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
$formTitle = 'Edit Monthly Vehicle';
$submitLabel = 'Update Vehicle';
$backUrl = 'contract_cars.php';
$backLabel = 'Back to Monthly Vehicles';
$formLead = 'Maintain vehicle master data for monthly contract assignments.';
$assignmentNoteTitle = 'Monthly dates stay in Monthly Contracts.';
$assignmentNoteBody = 'Customer, contract number, start date, and end date are linked from monthly contract records automatically, so update those details from';
$assignmentNoteLinkUrl = 'monthly-contracts.php';
$assignmentNoteLinkText = 'Monthly Contracts';
require __DIR__ . '/../includes/car-form.php';
require __DIR__ . '/../includes/footer.php';
?>
