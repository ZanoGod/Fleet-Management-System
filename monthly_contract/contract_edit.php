<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/monthly-contracts-data.php';

$activePage = 'monthly-contract-dashboard';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Module';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'MC';
$pageKicker = 'Monthly Contract Module';
$pageTitle = 'Edit Monthly Contract';
$pageSummary = 'Update customer assignment, billing, status, and expiry details.';
$pageActions = '<a class="btn btn-shell" href="monthly-contracts.php">All Contracts</a>';
$pageStyles = ['assets/css/monthly-contracts.css'];
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid monthly contract ID.');
    redirect('monthly-contracts.php');
}

if (!($db instanceof mysqli)) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/messages.php';
    require __DIR__ . '/includes/footer.php';
    return;
}

$cars = fetch_monthly_cars_for_select($db);
$drivers = fetch_monthly_drivers_for_select($db);
$operators = fetch_operators_for_select($db);
$contract = monthly_contract_find($db, $id);

if ($contract === null) {
    set_flash('danger', 'Monthly contract record not found.');
    redirect('monthly-contracts.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract = monthly_contract_payload($_POST, $contract);

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $errors = monthly_contract_validate_payload($db, $contract, $cars, $drivers, $operators, $id);
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'UPDATE monthly_contracts
             SET contract_no = ?,
                 customer_name = ?,
                 customer_phone = ?,
                 customer_email = ?,
                 customer_address = ?,
                 assignment_type = ?,
                 car_id = ?,
                 driver_id = ?,
                 operator_id = ?,
                 operator_name = ?,
                 start_date = ?,
                 end_date = ?,
                 monthly_rate = ?,
                 security_deposit = ?,
                 payment_status = ?,
                 status = ?,
                 note = ?
             WHERE id = ?'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the contract update query.';
        } else {
            bind_statement_params(
                $statement,
                'ssssssiiisssddsssi',
                [
                    $contract['contract_no'],
                    $contract['customer_name'],
                    $contract['customer_phone'],
                    $contract['customer_email'],
                    $contract['customer_address'],
                    $contract['assignment_type'],
                    $contract['car_id'],
                    $contract['driver_id'],
                    $contract['operator_id'],
                    $contract['operator_name'],
                    $contract['start_date'],
                    $contract['end_date'],
                    (float) $contract['monthly_rate'],
                    (float) $contract['security_deposit'],
                    $contract['payment_status'],
                    $contract['status'],
                    $contract['note'],
                    $id,
                ]
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Monthly contract updated successfully.');
                redirect('monthly-contracts.php');
            }

            $errors[] = 'Unable to update the contract. Contract number may already exist.';
            $statement->close();
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
$formTitle = 'Edit Monthly Contract';
$submitLabel = 'Update Contract';
require __DIR__ . '/includes/monthly-contract-form.php';
require __DIR__ . '/includes/footer.php';
?>
