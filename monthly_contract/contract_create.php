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
$pageTitle = 'Add Monthly Contract';
$pageSummary = 'Create a long-term customer assignment with monthly billing and expiry tracking.';
$pageActions = '<a class="btn btn-shell" href="monthly-contracts.php">All Contracts</a>';
$pageStyles = ['assets/css/monthly-contracts.css'];
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$contract = [
    'contract_no' => '',
    'customer_name' => '',
    'customer_phone' => '',
    'customer_email' => '',
    'customer_address' => '',
    'assignment_type' => 'Vehicle Only',
    'car_id' => '',
    'driver_id' => '',
    'operator_id' => '',
    'operator_name' => '',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 month -1 day')),
    'monthly_rate' => '0.00',
    'security_deposit' => '0.00',
    'payment_status' => 'Pending',
    'status' => 'Active',
    'note' => '',
];

if ($db instanceof mysqli) {
    $cars = fetch_monthly_cars_for_select($db);
    $drivers = fetch_monthly_drivers_for_select($db);
    $operators = fetch_operators_for_select($db);
    $contract['contract_no'] = monthly_contract_next_number($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract = monthly_contract_payload($_POST, $contract);

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (!($db instanceof mysqli)) {
        $errors[] = 'Database is not connected yet. Please check the database settings.';
    } else {
        $errors = monthly_contract_validate_payload($db, $contract, $cars, $drivers, $operators);
    }

    if ($errors === [] && $db instanceof mysqli) {
        $statement = $db->prepare(
            'INSERT INTO monthly_contracts
             (contract_no, customer_name, customer_phone, customer_email, customer_address,
              assignment_type, car_id, driver_id, operator_id, operator_name, start_date, end_date, monthly_rate,
              security_deposit, payment_status, status, renewal_parent_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the contract save query.';
        } else {
            $renewalParentId = null;
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

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Monthly contract created successfully.');
                redirect('monthly-contracts.php');
            }

            $errors[] = 'Unable to save the contract. Contract number may already exist.';
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
$formTitle = 'Add Monthly Contract';
$submitLabel = 'Save Contract';
require __DIR__ . '/includes/monthly-contract-form.php';
require __DIR__ . '/includes/footer.php';
?>
