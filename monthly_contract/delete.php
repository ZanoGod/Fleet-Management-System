<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('monthly-contracts.php');
}

if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
    set_flash('danger', 'Your form session expired. Please try again.');
    redirect('monthly-contracts.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid monthly contract ID.');
    redirect('monthly-contracts.php');
}

if ($db === null) {
    set_flash('danger', 'Database is not connected yet.');
    redirect('monthly-contracts.php');
}

$statement = $db->prepare('DELETE FROM monthly_contracts WHERE id = ?');

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to prepare the delete query.');
    redirect('monthly-contracts.php');
}

$statement->bind_param('i', $id);
$success = $statement->execute();
$affectedRows = $statement->affected_rows;
$statement->close();

if ($success && $affectedRows > 0) {
    set_flash('success', 'Monthly contract deleted successfully.');
} else {
    set_flash('danger', 'Monthly contract could not be deleted or was already removed.');
}

redirect('monthly-contracts.php');
