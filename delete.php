<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid booking ID.');
    redirect('index.php');
}

if ($db === null) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('index.php');
}

$statement = $db->prepare('DELETE FROM bookings WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('index.php');
}

$statement->bind_param('i', $id);
$statement->execute();
$statement->close();

set_flash('success', 'Booking deleted successfully.');
redirect('index.php');
