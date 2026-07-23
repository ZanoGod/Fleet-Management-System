<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';

$activePage = 'monthly-customers';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Customers';
$pageSummary = 'Review customers connected to monthly contract records.';
$pageActions = '<a class="btn btn-accent" href="customers_add.php">Add Customer</a>';
$pageStyles = ['assets/css/customers.css', 'assets/css/monthly-contracts.css'];

$filters = monthly_contract_filters($_GET);
$customers = [];
$stats = [
    'records' => 0,
    'customers' => 0,
    'active' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {

        $where[] = "(
        company_name LIKE ?
        OR guest_name LIKE ?
        OR contact_person LIKE ?
        OR contact_phone LIKE ?
        OR contact_email LIKE ?
    )";

        $search = '%' . $filters['search'] . '%';

        $types .= 'sssss';

        array_push(
            $params,
            $search,
            $search,
            $search,
            $search,
            $search
        );
    }

    if ($filters['status'] !== '') {
        $where[] = 'customer_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    $sql = "
SELECT
    id,
    company_name,
    guest_name,
    contact_person,
    contact_phone,
    contact_email,
    customer_status,
    remark
FROM customers
";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY company_name ASC";
    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {
        if ($params !== []) {
            bind_statement_params($statement, $types, $params);
        }

        $statement->execute();
        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        $statement->close();
    }

    $statsResult = $db->query(
        "SELECT
    COUNT(*) AS records,
    COUNT(*) AS customers,
    SUM(customer_status='Active') AS active
FROM customers"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<section class="overview-grid report-metrics-grid">
    <div class="card-shell overview-card">
        <span>Customer Records</span>
        <strong><?= e((string) $stats['records']) ?></strong>
        <small>Filtered contract records</small>
    </div>
    <div class="card-shell overview-card">
        <span>Customers</span>
        <strong><?= e((string) $stats['customers']) ?></strong>
        <small>Unique customer profiles</small>
    </div>
    <div class="card-shell overview-card">
        <span>Active</span>
        <strong><?= e((string) $stats['active']) ?></strong>
        <small>Currently running</small>
    </div>
</section>

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Search Customers</h2>
            <p>Filter by customer, contract, resource, operator, status, payment, expiry, or date.</p>
        </div>
    </div>

    <form method="get" action="customers.php#monthlyCustomersResultsSection" class="monthly-contract-filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Customer, contract, vehicle, driver, operator">
        </div>
        <div>
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (monthly_contract_statuses() as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="assignment_type" class="form-label">Assignment</label>
            <select class="form-select" id="assignment_type" name="assignment_type">
                <option value="">All Types</option>
                <?php foreach (monthly_contract_assignment_types() as $assignmentType): ?>
                    <option value="<?= e($assignmentType) ?>" <?= selected($filters['assignment_type'], $assignmentType) ?>><?= e($assignmentType) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="payment_status" class="form-label">Payment</label>
            <select class="form-select" id="payment_status" name="payment_status">
                <option value="">All Payments</option>
                <?php foreach (monthly_contract_payment_statuses() as $paymentStatus): ?>
                    <option value="<?= e($paymentStatus) ?>" <?= selected($filters['payment_status'], $paymentStatus) ?>><?= e($paymentStatus) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="expiry" class="form-label">Expiry</label>
            <select class="form-select" id="expiry" name="expiry">
                <option value="">All</option>
                <option value="expiring" <?= selected($filters['expiry'], 'expiring') ?>>Expiring Soon</option>
                <option value="expired" <?= selected($filters['expiry'], 'expired') ?>>Expired</option>
            </select>
        </div>
        <div>
            <label for="date_from" class="form-label">Start From</label>
            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e($filters['date_from']) ?>">
        </div>
        <div>
            <label for="date_to" class="form-label">End To</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="d-grid align-self-end">
            <button type="submit" class="btn btn-shell">Filter</button>
        </div>
    </form>
</section>

<section class="card-shell section-card">
    <div class="section-title">
        <div>
            <h2>Customer Contract List</h2>
            <p><?= e((string) count($customers)) ?> customer contract records found.</p>
        </div>
    </div>

    <div class="table-responsive" id="monthlyCustomersResultsSection">
        <table class="table data-table customers-table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Company</th>
                    <th>Guest</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Remark</th>
                    <th width="120">Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($customers)): ?>

                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            No customers found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($customers as $index => $customer): ?>

                        <tr>

                            <td class="text-center">
                                <?= $index + 1 ?>
                            </td>

                            <td class="customer-company">
                                <?= e($customer['company_name']) ?>
                            </td>

                            <td>
                                <?= e($customer['guest_name'] ?: '-') ?>
                            </td>

                            <td>
                                <?= e($customer['contact_person'] ?: '-') ?>
                            </td>

                            <td>
                                <?= e($customer['contact_phone'] ?: '-') ?>
                            </td>

                            <td class="customer-email">
                                <?= e($customer['contact_email'] ?: '-') ?>
                            </td>

                            <td class="text-center">

                                <span class="customer-status <?= strtolower($customer['customer_status']) ?>">
                                    <?= e($customer['customer_status']) ?>
                                </span>

                            </td>

                            <td class="customer-remark">
                                <?= e($customer['remark'] ?: '-') ?>
                            </td>



                            <td class="text-center">
                                <div class="action-group">

                                    <a href="customers_edit.php?id=<?= $customer['id'] ?>"
                                        class="btn btn-edit btn-sm">
                                        Edit
                                    </a>

                                    <a href="customers_delete.php?id=<?= $customer['id'] ?>"
                                        class="btn btn-delete btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this customer?');">
                                        Delete
                                    </a>

                                </div>
                            </td>



                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>