<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Add near the top of delete.php for production debugging
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Delete failed: Incoming method was ' . $_SERVER['REQUEST_METHOD']);
    set_flash('danger', 'Invalid delete request.');
    redirect('bookings.php');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    set_flash('danger', 'Invalid booking ID.');
    redirect('bookings.php');
}

if (!($db instanceof mysqli)) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('bookings.php');
}
$db->begin_transaction();
try {
    $statement = $db->prepare('DELETE FROM bookings WHERE id = ?');
    if (!$statement instanceof mysqli_stmt) {
        throw new RuntimeException('Failed to prepare the delete query.');
    }
    $statement->bind_param('i', $id);
    if (!$statement->execute()) {
        $error = $statement->error;
        $statement->close();
        throw new RuntimeException('Failed to delete booking: ' . $error);
    }
    $affectedRows = $statement->affected_rows;
    $statement->close();
    if ($affectedRows <= 0) {
        $db->rollback();
        set_flash('danger', 'Booking could not be deleted or was already removed.');
        redirect('bookings.php');
    }
    $db->commit();
    set_flash('success', 'Booking deleted successfully.');
} catch (Throwable $exception) {
    $db->rollback();
    error_log('Booking deletion failed for ID ' . $id . ': ' . $exception->getMessage());
    set_flash('danger', 'Unable to delete the booking. Please try again.');
}
redirect('bookings.php');
