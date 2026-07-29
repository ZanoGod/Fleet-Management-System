<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'contract-cars';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Add Monthly Vehicle';
$pageSummary = 'Create a vehicle record that is only used for monthly contracts.';
$pageActions = '<a class="btn btn-dashboard" href="contract_cars.php">All Monthly Vehicles</a>';
$pageStyles = ['assets/css/cars.css', 'assets/css/monthly-contracts.css'];
$errors = [];
$car = [
    'car_type' => '',
    'plate_no' => '',
    'model_name' => '',
    'seat_capacity' => 4,
    'availability_status' => 'Available',
    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car = [
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

    if (!($db instanceof mysqli)) {
        $errors[] = 'Database is not connected yet. Please check the database settings.';
    }

    if ($errors === [] && $db instanceof mysqli) {
        $statement = $db->prepare(
            'INSERT INTO monthly_cars
             (car_type, plate_no, model_name, seat_capacity, availability_status, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $seatCapacity = (int) $car['seat_capacity'];
            $statement->bind_param(
                'sssiss',
                $car['car_type'],
                $car['plate_no'],
                $car['model_name'],
                $seatCapacity,
                $car['availability_status'],
                $car['note']
            );

            try {

                if ($statement->execute()) {
                    $statement->close();
                    set_flash('success', 'Monthly vehicle added successfully.');
                    redirect('contract_cars.php');
                }
            } catch (mysqli_sql_exception $e) {

                if ($e->getCode() == 1062) {
                    $errors[] = 'This plate number already exists. Please use another plate number.';
                } else {
                    $errors[] = 'Database Error: ' . $e->getMessage();
                }
            }

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
$formTitle = 'Add Monthly Vehicle';
$submitLabel = 'Save Vehicle';
$backUrl = 'contract_cars.php';
$backLabel = 'Back to Vehicles';
$formLead = 'Maintain vehicle master data for monthly contract assignments.';
$assignmentNoteTitle = 'Monthly dates stay in Monthly Contracts.';
$assignmentNoteBody = 'Customer, contract number, start date, and end date are linked from monthly contract records automatically, so update those details from';
$assignmentNoteLinkUrl = 'monthly-contracts.php';
$assignmentNoteLinkText = 'Monthly Contracts';
require __DIR__ . '/../includes/car-form.php';
require __DIR__ . '/../includes/footer.php';
?>