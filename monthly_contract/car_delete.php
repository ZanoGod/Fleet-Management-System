<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('monthly-contract-cars.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid monthly vehicle ID.');
    redirect('monthly-contract-cars.php');
}

if (!($db instanceof mysqli)) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('monthly-contract-cars.php');
}

$statement = $db->prepare('DELETE FROM monthly_cars WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('monthly-contract-cars.php');
}

$statement->bind_param('i', $id);

try {
    $success = $statement->execute();
    $affectedRows = $statement->affected_rows;

    if ($success && $affectedRows > 0) {
        set_flash('success', 'Monthly vehicle deleted successfully.');
    } else {
        set_flash('danger', 'Monthly vehicle could not be found.');
    }
} catch (mysqli_sql_exception $exception) {
    set_flash('danger', 'This monthly vehicle cannot be deleted because it is already used in a monthly contract.');
}

$statement->close();

redirect('contract_cars.php');
