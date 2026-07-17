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
$pageTitle = 'Renew Monthly Contract';
$pageSummary = 'Create a new contract period from an existing monthly agreement.';
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
$parentContract = monthly_contract_find($db, $id);

if ($parentContract === null) {
    set_flash('danger', 'Monthly contract record not found.');
    redirect('monthly-contracts.php');
}

$renewalStart = date('Y-m-d');

if (monthly_contract_valid_date((string) $parentContract['end_date'])) {
    $renewalStart = (new DateTimeImmutable((string) $parentContract['end_date']))
        ->modify('+1 day')
        ->format('Y-m-d');
}

$renewalEnd = (new DateTimeImmutable($renewalStart))
    ->modify('+1 month -1 day')
    ->format('Y-m-d');

$contract = [
    'contract_no' => monthly_contract_next_number($db, $renewalStart),
    'customer_name' => (string) $parentContract['customer_name'],
    'customer_phone' => (string) ($parentContract['customer_phone'] ?? ''),
    'customer_email' => (string) ($parentContract['customer_email'] ?? ''),
    'customer_address' => (string) ($parentContract['customer_address'] ?? ''),
    'assignment_type' => (string) $parentContract['assignment_type'],
    'car_id' => (string) ($parentContract['car_id'] ?? ''),
    'driver_id' => (string) ($parentContract['driver_id'] ?? ''),
    'operator_id' => (string) ($parentContract['operator_id'] ?? ''),
    'operator_name' => (string) ($parentContract['operator_name'] ?? ''),
    'start_date' => $renewalStart,
    'end_date' => $renewalEnd,
    'monthly_rate' => (string) $parentContract['monthly_rate'],
    'security_deposit' => (string) $parentContract['security_deposit'],
    'payment_status' => 'Pending',
    'status' => 'Active',
    'note' => trim('Renewed from ' . $parentContract['contract_no'] . '. ' . (string) ($parentContract['note'] ?? '')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract = monthly_contract_payload($_POST, $contract);

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $errors = monthly_contract_validate_payload($db, $contract, $cars, $drivers, $operators);
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'INSERT INTO monthly_contracts
             (contract_no, customer_name, customer_phone, customer_email, customer_address,
              assignment_type, car_id, driver_id, operator_id, operator_name, start_date, end_date, monthly_rate,
              security_deposit, payment_status, status, renewal_parent_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $updateParentStatement = $db->prepare(
            "UPDATE monthly_contracts
             SET status = 'Renewed'
             WHERE id = ?"
        );

        if (!$statement instanceof mysqli_stmt || !$updateParentStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the contract renewal query.';
        } else {
            $renewalParentId = $id;
            bind_statement_params(
                $statement,
                'ssssssiiisssddssis',
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
                    $renewalParentId,
                    $contract['note'],
                ]
            );
            $updateParentStatement->bind_param('i', $id);

            $db->begin_transaction();
            $created = $statement->execute();
            $updated = $created && $updateParentStatement->execute();

            if ($created && $updated) {
                $db->commit();
                $statement->close();
                $updateParentStatement->close();
                set_flash('success', 'Monthly contract renewed successfully.');
                redirect('monthly-contracts.php');
            }

            $db->rollback();
            $statement->close();
            $updateParentStatement->close();
            $errors[] = 'Unable to renew the contract. Contract number may already exist.';
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

<div class="alert alert-warning border-0 shadow-sm mb-4">
    Renewing <strong><?= e((string) $parentContract['contract_no']) ?></strong>
    for <?= e((string) $parentContract['customer_name']) ?>.
</div>

<?php
$formTitle = 'Renew Monthly Contract';
$submitLabel = 'Create Renewal';
require __DIR__ . '/includes/monthly-contract-form.php';
require __DIR__ . '/includes/footer.php';
?>
