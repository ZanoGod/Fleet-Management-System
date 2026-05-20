<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('drivers.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid driver ID.');
    redirect('drivers.php');
}

if ($db === null) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('drivers.php');
}

$statement = $db->prepare('DELETE FROM drivers WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('drivers.php');
}

$statement->bind_param('i', $id);

try {

    $success = $statement->execute();
    $affectedRows = $statement->affected_rows;

    if ($success && $affectedRows > 0) {
        set_flash('success', 'Driver deleted successfully.');
    } else {
        set_flash('danger', 'Driver could not be found.');
    }

} catch (mysqli_sql_exception $e) {

    set_flash(
        'danger',
        'This driver cannot be deleted because the driver is already used in a booking.'
    );

}

$statement->close();

redirect('drivers.php');