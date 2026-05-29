<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('operators.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid operator ID.');
    redirect('operators.php');
}

if ($db === null) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('operators.php');
}

$statement = $db->prepare('DELETE FROM operators WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('operators.php');
}

$statement->bind_param('i', $id);

try {
    $success = $statement->execute();
    $affectedRows = $statement->affected_rows;

    if ($success && $affectedRows > 0) {
        set_flash('success', 'Operator deleted successfully.');
    } else {
        set_flash('danger', 'Operator could not be found.');
    }
} catch (mysqli_sql_exception $e) {
    set_flash(
        'danger',
        'This operator cannot be deleted because the operator is already used in a booking.'
    );
}

$statement->close();

redirect('operators.php');