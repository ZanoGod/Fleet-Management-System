<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cars.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid car ID.');
    redirect('cars.php');
}

if ($db === null) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('cars.php');
}

$statement = $db->prepare('DELETE FROM cars WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('cars.php');
}

$statement->bind_param('i', $id);
$success = $statement->execute();
$statement->close();

if ($success) {
    set_flash('success', 'Car deleted successfully.');
} else {
    set_flash('danger', 'This car cannot be deleted because it is already used in a booking.');
}

redirect('cars.php');
