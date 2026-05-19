<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'cars';
$pageTitle = 'Add Car';
$pageSummary = 'Create a new fleet vehicle record.';
$pageActions = '<a class="btn btn-shell" href="cars.php">All Cars</a>';
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

    if ((int) $car['seat_capacity'] <= 0) {
        $errors[] = 'Seat capacity must be greater than zero.';
    }

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'INSERT INTO cars
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

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Car added successfully.');
                redirect('cars.php');
            }

            $errors[] = 'Unable to save the car. Plate number may already exist.';
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
$formTitle = 'Add Fleet Car';
$submitLabel = 'Save Car';
require __DIR__ . '/includes/car-form.php';
require __DIR__ . '/includes/footer.php';
?>
