<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('bookings.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid booking ID.');
    redirect('bookings.php');
}

if ($db === null) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('bookings.php');
}

$statement = $db->prepare('DELETE FROM bookings WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('bookings.php');
}

$statement->bind_param('i', $id);
$success = $statement->execute();
$affectedRows = $statement->affected_rows;
$statement->close();

if ($success && $affectedRows > 0) {
    set_flash('success', 'Booking deleted successfully.');
} else {
    set_flash('danger', 'Booking could not be deleted or was already removed.');
}

redirect('bookings.php');
