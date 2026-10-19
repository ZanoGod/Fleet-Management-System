<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'cars';
$pageTitle = 'Add Car';
$pageSummary = 'Create a new fleet vehicle record.';
$pageActions = '<a class="btn btn-shell" href="cars.php">View Cars</a>';

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

    // Get submitted values
    $car = [
        'car_type' => old($_POST, 'car_type'),
        'plate_no' => old($_POST, 'plate_no'),
        'model_name' => old($_POST, 'model_name'),
        'seat_capacity' => old($_POST, 'seat_capacity', '4'),
        'availability_status' => old($_POST, 'availability_status', 'Available'),
        'note' => old($_POST, 'note'),
    ];

    // Clean plate number
    $car['plate_no'] = trim($car['plate_no']);

    // Validate required fields
    foreach (
        ['car_type', 'plate_no', 'model_name', 'seat_capacity', 'availability_status']
        as $field
    ) {
        if ($car[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    // Validate seat capacity
    if (
        filter_var(
            $car['seat_capacity'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        ) === false
    ) {
        $errors[] = 'Seat capacity must be greater than zero.';
    }

    // Validate availability status
    if (!in_allowed_values($car['availability_status'], car_statuses())) {
        $errors[] = 'Please choose a valid car status.';
    }

    // Check database connection
    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    // Save car
    if ($errors === [] && $db instanceof mysqli) {

        $statement = $db->prepare(
            'INSERT INTO cars
            (
                car_type,
                plate_no,
                model_name,
                seat_capacity,
                availability_status,
                note
            )
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

                    set_flash(
                        'success',
                        'Car added successfully.'
                    );

                    redirect('cars.php');
                }

                // If execute() returns false without throwing an exception
                $errors[] = 'Unable to save the car. Please try again.';

            } catch (mysqli_sql_exception $exception) {

                // MySQL error 1062 = Duplicate entry
                if ($exception->getCode() === 1062) {

                    $errors[] =
                        'This plate number already exists. Please enter a different plate number.';

                } else {

                    // Generic database error
                    $errors[] =
                        'Unable to save the car. Please try again.';
                }

            } finally {

                $statement->close();
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

$formTitle = 'Add Fleet Car';
$submitLabel = 'Save Car';

require __DIR__ . '/includes/car-form.php';
require __DIR__ . '/includes/footer.php';

?>