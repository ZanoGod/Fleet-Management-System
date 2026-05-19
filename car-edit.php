<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'cars';
$pageTitle = 'Edit Car';
$pageSummary = 'Update fleet vehicle details and availability.';
$pageActions = '<a class="btn btn-shell" href="cars.php">All Cars</a>';
$errors = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid car ID.');
    redirect('cars.php');
}

if ($db === null) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/messages.php';
    require __DIR__ . '/includes/footer.php';
    return;
}

$statement = $db->prepare('SELECT * FROM cars WHERE id = ? LIMIT 1');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the car record.');
    redirect('cars.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$result = $statement->get_result();
$car = $result->fetch_assoc();
$statement->close();

if ($car === null) {
    set_flash('danger', 'Car record not found.');
    redirect('cars.php');
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

    if ((int) $car['seat_capacity'] <= 0) {
        $errors[] = 'Seat capacity must be greater than zero.';
    }

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE cars
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
                set_flash('success', 'Car updated successfully.');
                redirect('cars.php');
            }

            $errors[] = 'Unable to update the car. Plate number may already exist.';
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
$formTitle = 'Edit Fleet Car';
$submitLabel = 'Update Car';
require __DIR__ . '/includes/car-form.php';
require __DIR__ . '/includes/footer.php';
?>
