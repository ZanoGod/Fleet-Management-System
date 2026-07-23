<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'monthly-customers';

$navigationItems = monthly_contract_navigation();

$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';

$pageKicker = 'Monthly Contracts';
$pageTitle = 'Edit Customer';
$pageSummary = 'Update the customer information used for monthly contracts.';

$pageActions = '<a class="btn btn-dashboard" href="customers.php">All Customers</a>';

$pageStyles = [
    'assets/css/customers.css',
    'assets/css/monthly-contracts.css'
];

$errors = [];

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'Invalid customer selected.');
    redirect('customers.php');
}

if (!($db instanceof mysqli)) {
    set_flash('error', 'Database connection failed.');
    redirect('customers.php');
}

$statement = $db->prepare(
    "SELECT
        id,
        company_name,
        guest_name,
        contact_person,
        contact_phone,
        contact_email,
        customer_status,
        remark
     FROM customers
     WHERE id=?"
);

if (!$statement instanceof mysqli_stmt) {
    set_flash('error', 'Unable to load customer.');
    redirect('customers.php');
}

$statement->bind_param('i', $id);
$statement->execute();

$result = $statement->get_result();

$customer = $result->fetch_assoc();

$statement->close();

if (!$customer) {
    set_flash('error', 'Customer not found.');
    redirect('customers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer = [
        'id'               => $id,
        'company_name'     => trim($_POST['company_name'] ?? ''),
        'guest_name'       => trim($_POST['guest_name'] ?? ''),
        'contact_person'   => trim($_POST['contact_person'] ?? ''),
        'contact_phone'    => trim($_POST['contact_phone'] ?? ''),
        'contact_email'    => trim($_POST['contact_email'] ?? ''),
        'customer_status'  => trim($_POST['customer_status'] ?? 'Active'),
        'remark'           => trim($_POST['remark'] ?? ''),
    ];

    if ($customer['company_name'] === '') {
        $errors[] = 'Company Name is required.';
    }

    if (
        $customer['contact_email'] !== '' &&
        !filter_var($customer['contact_email'], FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (
        !in_array(
            $customer['customer_status'],
            ['Active', 'Inactive'],
            true
        )
    ) {
        $errors[] = 'Please choose a valid status.';
    }

    if ($errors === []) {

        $duplicate = $db->prepare(
            "SELECT id
             FROM customers
             WHERE company_name=?
             AND id<>?"
        );

        $duplicate->bind_param(
            'si',
            $customer['company_name'],
            $id
        );

        $duplicate->execute();

        if ($duplicate->get_result()->num_rows > 0) {
            $errors[] = 'Company already exists.';
        }

        $duplicate->close();
    }

    if ($errors === []) {

        $update = $db->prepare(
            "UPDATE customers
            SET
                company_name=?,
                guest_name=?,
                contact_person=?,
                contact_phone=?,
                contact_email=?,
                customer_status=?,
                remark=?
            WHERE id=?"
        );

        if (!$update instanceof mysqli_stmt) {

            $errors[] = 'Failed to prepare update statement.';

        } else {

            $update->bind_param(
                'sssssssi',
                $customer['company_name'],
                $customer['guest_name'],
                $customer['contact_person'],
                $customer['contact_phone'],
                $customer['contact_email'],
                $customer['customer_status'],
                $customer['remark'],
                $id
            );

            if ($update->execute()) {

                $update->close();

                set_flash(
                    'success',
                    'Customer updated successfully.'
                );

                redirect('customers.php');
            }

            $errors[] = 'Unable to update customer.';

            $update->close();
        }
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<?php if ($errors): ?>

<div class="alert alert-danger border-0 shadow-sm mb-4">

    <ul class="mb-0 ps-3">

        <?php foreach ($errors as $error): ?>

            <li><?= e($error) ?></li>

        <?php endforeach; ?>

    </ul>

</div>

<?php endif; ?>

<?php

$formTitle = 'Edit Customer';

$submitLabel = 'Update Customer';

$backUrl = 'customers.php';

$backLabel = 'Back to Customers';

$formLead = 'Update customer information for monthly contract assignments.';

$assignmentNoteTitle = 'Contract information stays in Monthly Contracts.';

$assignmentNoteBody = 'Vehicle, driver, operator, contract period, and billing information should be maintained from';

$assignmentNoteLinkUrl = 'monthly-contracts.php';

$assignmentNoteLinkText = 'Monthly Contracts';

require __DIR__ . '/../includes/customer-form.php';

require __DIR__ . '/../includes/footer.php';