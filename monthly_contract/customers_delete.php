<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {

    set_flash('error', 'Invalid customer.');

    redirect('customers.php');
}

if (!($db instanceof mysqli)) {

    set_flash('error', 'Database connection failed.');

    redirect('customers.php');
}

/*
|--------------------------------------------------------------------------
| Check customer exists
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare(
    "SELECT id
     FROM customers
     WHERE id=?"
);

$stmt->bind_param('i', $id);

$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {

    $stmt->close();

    set_flash('error', 'Customer not found.');

    redirect('customers.php');
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Check Monthly Contracts
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare(
    "SELECT COUNT(*) AS total
     FROM monthly_contracts
     WHERE customer_id = ?"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$contractCount = (int)($row['total'] ?? 0);

$stmt->close();

if ($contractCount > 0) {

    set_flash(
        'error',
        'This customer is assigned to one or more monthly contracts and cannot be deleted.'
    );

    redirect('customers.php');
}



/*
|--------------------------------------------------------------------------
| Delete Customer
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare(
    "DELETE
     FROM customers
     WHERE id=?"
);

$stmt->bind_param('i', $id);

if ($stmt->execute()) {

    set_flash(
        'success',
        'Customer deleted successfully.'
    );
} else {

    set_flash(
        'error',
        'Unable to delete customer.'
    );
}

$stmt->close();

redirect('customers.php');
