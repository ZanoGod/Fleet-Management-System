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
$pageTitle = 'Add Monthly Contract';
$pageSummary = 'Create a long-term customer assignment with monthly billing and expiry tracking.';
$pageActions = '<a class="btn btn-dashboard" href="monthly-contracts.php">All Contracts</a>';
$pageStyles = ['assets/css/monthly-contracts.css'];
$errors = [];
$cars = [];
$drivers = [];
$operators = [];
$customers = [];
$contract = [
    'contract_no' => '',
    'customer_id' => '',
    'customer_name' => '',
    'guest_name' => '',
    'contact_person' => '',
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
    'monthly_rate' => '',
    'rental_currency' => 'MMK',
    'off_day' => '',
    'service_hour' => '',
    'even_odd' => '',
    'operation_sheet' => '',
    'insurance_company' => '',
    'insurance_price' => '',
    'prepared_by' => '',
    'invoice_sender' => '',
    'checked_by' => '',
    'terms_conditions' => '',
    'rental_status' => 'Including fuel',
    'status' => 'Active',
    'note' => '',
];

if ($db instanceof mysqli) {

    $cars = fetch_monthly_cars_for_select($db, true);
    $drivers = fetch_monthly_drivers_for_select($db);
    $operators = fetch_operators_for_select($db);
    $customers = monthly_contract_customers_for_select($db);

    $contract['contract_no'] = monthly_contract_next_number($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract = monthly_contract_payload($_POST, $contract);

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (!($db instanceof mysqli)) {
        $errors[] = 'Database is not connected yet. Please check the database settings.';
    } else {
        $errors = monthly_contract_validate_payload($db, $contract, $cars, $drivers, $operators, null, true);
    }

    if ($errors === [] && $db instanceof mysqli) {
        $statement = $db->prepare(
            'INSERT INTO monthly_contracts
    (
        contract_no,
        customer_id,
        customer_name,
        guest_name,
        contact_person,
        customer_phone,
        customer_email,
        customer_address,

        assignment_type,
        car_id,
        driver_id,
        operator_id,
        operator_name,

        start_date,
        end_date,

        off_day,
        service_hour,
        even_odd,
        operation_sheet,

        monthly_rate,
        rental_currency,

        insurance_company,
        insurance_price,

        rental_status,
        status,

        prepared_by,
        invoice_sender,
        checked_by,

        terms_conditions,

        renewal_parent_id,
        note
    )
    VALUES
    (
        ?,?,?,?,?,?,?,?,
        ?,?,?,?,?,
        ?,?,
        ?,?,?,?,
        ?,?,
        ?,
        ?,?,
        ?,
        ?,?,?,
        ?,?,?
    )'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the contract save query.';
        } else {
            $renewalParentId = null;

            bind_statement_params(
                $statement,
                'si' . str_repeat('s', 7) . 'iii' . str_repeat('s', 6) . 'dsdsd' . str_repeat('s', 6) . 'is',
                [
                    $contract['contract_no'],
                    $contract['customer_id'],
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
                    $renewalParentId,
                    $contract['note'],
                ]
            );

            $db->begin_transaction();
            $saved = $statement->execute();
            $customerStatusSynced = $saved && monthly_contract_sync_customer_status(
                $db,
                (int) $contract['customer_id'],
                (string) $contract['status']
            );

            if ($saved && $customerStatusSynced) {
                $db->commit();
                $statement->close();
                set_flash('success', 'Monthly contract created successfully.');
                redirect('monthly-contracts.php');
            }

            $db->rollback();
            $errors[] = 'Unable to save the contract. Contract number may already exist.';
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
$formTitle = 'Create Monthly Contract';
$submitLabel = 'Save Contract';
require __DIR__ . '/monthly-contract-form.php';
require __DIR__ . '/../includes/footer.php';
?>