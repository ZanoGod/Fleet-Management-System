<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'monthly-customers';
$navigationItems = monthly_contract_navigation();

$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';

$pageKicker = 'Monthly Contracts';
$pageTitle = 'Customer';
$pageSummary = 'Create a customer record for monthly contracts.';

$pageActions = '<a class="btn btn-dashboard" href="customers.php">All Customers</a>';

$pageStyles = [
    'assets/css/customers.css',
    'assets/css/monthly-contracts.css'
];

$errors = [];

$customer = [
    'company_name' => '',
    'guest_name' => '',
    'contact_person' => '',
    'contact_phone' => '',
    'contact_email' => '',
    'customer_address' => '',
    'off_day' => '',
    'service_hour' => '',
    'customer_status' => old($_POST, 'customer_status', 'Inactive'),
    'remark' => '',
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer = [
        'company_name' => old($_POST, 'company_name'),
        'guest_name' => old($_POST, 'guest_name'),
        'contact_person' => old($_POST, 'contact_person'),
        'contact_phone' => old($_POST, 'contact_phone'),
        'contact_email' => old($_POST, 'contact_email'),
        'customer_address' => old($_POST, 'customer_address'),
        'off_day' => old($_POST, 'off_day'),
        'service_hour' => old($_POST, 'service_hour'),
        'customer_status' => 'Inactive',
        'remark' => old($_POST, 'remark'),
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

    if ($customer['service_hour'] === '') {
        $errors[] = 'Service Hour is required.';
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

    if (!($db instanceof mysqli)) {
        $errors[] = 'Database is not connected yet. Please check the database settings.';
    }

    if ($errors === [] && $db instanceof mysqli) {

        $check = $db->prepare(
            "SELECT id
             FROM customers
             WHERE company_name = ?"
        );

        if ($check instanceof mysqli_stmt) {

            $check->bind_param(
                's',
                $customer['company_name']
            );

            $check->execute();

            if ($check->get_result()->num_rows > 0) {

                $errors[] = 'Company already exists.';
            }

            $check->close();
        }
    }

    if ($errors === [] && $db instanceof mysqli) {

        $statement = $db->prepare(
            "INSERT INTO customers
            (   
            company_name,
            guest_name,
            contact_person,
            contact_phone,
            contact_email,
            customer_address,
            off_day,
            service_hour,
            customer_status,
            remark
            )
            VALUES
            (?,?,?,?,?,?,?,?,?,?)"
        );

        if (!$statement instanceof mysqli_stmt) {

            $errors[] = 'Failed to prepare the database query.';
        } else {

            $statement->bind_param(
                'ssssssssss',
                $customer['company_name'],
                $customer['guest_name'],
                $customer['contact_person'],
                $customer['contact_phone'],
                $customer['contact_email'],
                $customer['customer_address'],
                $customer['off_day'],
                $customer['service_hour'],
                $customer['customer_status'],
                $customer['remark']
            );

            if ($statement->execute()) {

                $statement->close();

                set_flash(
                    'success',
                    'Customer added successfully.'
                );

                redirect('customers.php');
            }

            $errors[] = 'Unable to save the customer. Please try again.';

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

$formTitle = 'Add Customer';
$submitLabel = 'Save Customer';
$backUrl = 'customers.php';
$backLabel = 'Back to Customers';
$formLead = 'Maintain customer master data for monthly contract assignments.';

require __DIR__ . '/../includes/customer-form.php';
require __DIR__ . '/../includes/footer.php';
