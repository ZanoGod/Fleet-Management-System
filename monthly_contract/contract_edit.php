<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';

$activePage = 'monthly-contract-dashboard';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Edit Monthly Contract';
$pageSummary = 'Update customer assignment, billing, status, and expiry details.';
$pageActions = '<a class="btn btn-dashboard" href="monthly-contracts.php">All Contracts</a>';
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
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/messages.php';
    require __DIR__ . '/../includes/footer.php';
    return;
}

$existingContract = monthly_contract_find($db, $id);

if ($existingContract === null) {
    set_flash('danger', 'Monthly contract record not found.');
    redirect('monthly-contracts.php');
}

$currentCarId = (int) ($existingContract['car_id'] ?? 0);
$currentDriverId = (int) ($existingContract['driver_id'] ?? 0);

$cars = fetch_monthly_cars_for_select($db, true, $currentCarId);
$drivers = fetch_monthly_drivers_for_select($db, true, $currentDriverId);
$operators = fetch_operators_for_select($db);
$contract = $existingContract;
$customerLabel = trim((string) ($existingContract['customer_name'] ?? ''));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract = monthly_contract_payload($_POST, $existingContract);
    $contract['customer_id'] = (string) ($existingContract['customer_id'] ?? '');

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
                 guest_name = ?,
                 contact_person = ?,
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
                 off_day = ?,
                 service_hour = ?,
                 even_odd = ?,
                 operation_sheet = ?,
                 monthly_rate = ?,
                 rental_currency = ?,
                 insurance_company = ?,
                 insurance_price = ?,
                 rental_status = ?,
                 status = ?,
                 prepared_by = ?,
                 invoice_sender = ?,
                 checked_by = ?,
                 terms_conditions = ?,
                 note = ?
             WHERE id = ?'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the contract update query.';
        } else {
            bind_statement_params(
                $statement,
                's' . str_repeat('s', 7) . 'iii' . str_repeat('s', 6) . 'dsdsd' . str_repeat('s', 7) . 'i',
                [
                    $contract['contract_no'],
                    $contract['customer_name'],
                    $contract['guest_name'],
                    $contract['contact_person'],
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
                    $contract['off_day'],
                    $contract['service_hour'],
                    $contract['even_odd'],
                    $contract['operation_sheet'],
                    (float) $contract['monthly_rate'],
                    $contract['rental_currency'],
                    $contract['insurance_company'],
                    (float) $contract['insurance_price'],
                    $contract['rental_status'],
                    $contract['status'],
                    $contract['prepared_by'],
                    $contract['invoice_sender'],
                    $contract['checked_by'],
                    $contract['terms_conditions'],
                    $contract['note'],
                    $id,
                ]
            );

            $db->begin_transaction();
            $updated = $statement->execute();
            $customerStatusSynced = $updated && monthly_contract_sync_customer_status(
                $db,
                (int) ($existingContract['customer_id'] ?? 0),
                (string) $contract['status']
            );

            if ($updated && $customerStatusSynced) {
                $db->commit();
                $statement->close();
                set_flash('success', 'Monthly contract updated successfully.');
                redirect('monthly-contracts.php');
            }

            $db->rollback();
            $errors[] = 'Unable to update the contract. Contract number may already exist.';
            $statement->close();
        }
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
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
$formTitle = $customerLabel !== '' ? 'Edit Contract — ' . $customerLabel : 'Edit Monthly Contract';
$submitLabel = 'Update Contract';
$customerSelectionEnabled = false;
require __DIR__ . '/monthly-contract-form.php';
require __DIR__ . '/../includes/footer.php';
?>