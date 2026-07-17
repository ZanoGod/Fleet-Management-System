<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/monthly-contracts-data.php';

$activePage = 'monthly-contract-customers';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Module';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'MC';
$pageKicker = 'Monthly Contract Module';
$pageTitle = 'Monthly Contract Customers';
$pageSummary = 'Review customers connected to monthly contract records.';
$pageActions = '<a class="btn btn-shell" href="monthly-contracts.php">Dashboard</a> <a class="btn btn-accent" href="monthly-contract-create.php">Add Contract</a>';
$pageStyles = ['assets/css/reports.css', 'assets/css/monthly-contracts.css'];

$filters = monthly_contract_filters($_GET);
$contracts = [];
$stats = [
    'records' => 0,
    'customers' => 0,
    'active' => 0,
    'revenue' => 0,
];

if ($db instanceof mysqli) {
    $contracts = monthly_contract_fetch($db, $filters);
    $customerKeys = [];

    foreach ($contracts as $contract) {
        $stats['records']++;

        $customerKey = strtolower(trim(
            (string) $contract['customer_name']
            . '|'
            . (string) $contract['customer_phone']
            . '|'
            . (string) $contract['customer_email']
        ));

        if ($customerKey !== '||') {
            $customerKeys[$customerKey] = true;
        }

        if ((string) $contract['status'] === 'Active') {
            $stats['active']++;
            $stats['revenue'] += (float) $contract['monthly_rate'];
        }
    }

    $stats['customers'] = count($customerKeys);
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
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
    <div class="card-shell overview-card">
        <span>Revenue</span>
        <strong><?= e(monthly_contract_money($stats['revenue'])) ?></strong>
        <small>Active monthly value</small>
    </div>
</section>

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Search Customers</h2>
            <p>Filter by customer, contract, resource, operator, status, payment, expiry, or date.</p>
        </div>
    </div>

    <form method="get" action="monthly-contract-customers.php" class="monthly-contract-filter-grid">
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
            <p><?= e((string) count($contracts)) ?> customer contract records found.</p>
        </div>
    </div>

    <div class="report-table-shell">
        <table class="table data-table report-table monthly-report-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Contract</th>
                    <th>Assignment</th>
                    <th>Dates</th>
                    <th>Rate</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($contracts === []): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted-soft">No customer contracts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td data-label="Customer">
                                <strong><?= e((string) $contract['customer_name']) ?></strong>
                            </td>
                            <td data-label="Contact">
                                <div><?= e((string) ($contract['customer_phone'] ?: '-')) ?></div>
                                <div class="soft-note"><?= e((string) ($contract['customer_email'] ?: '-')) ?></div>
                            </td>
                            <td data-label="Contract">
                                <strong><?= e((string) $contract['contract_no']) ?></strong>
                            </td>
                            <td data-label="Assignment">
                                <span class="resource-pill <?= e(monthly_contract_assignment_badge_class((string) $contract['assignment_type'])) ?>">
                                    <?= e((string) $contract['assignment_type']) ?>
                                </span>
                                <div class="soft-note mt-1"><?= e(monthly_contract_vehicle_display($contract)) ?></div>
                                <div class="soft-note"><?= e(monthly_contract_driver_display($contract)) ?></div>
                                <div class="soft-note">Operator: <?= e(monthly_contract_operator_display($contract)) ?></div>
                            </td>
                            <td data-label="Dates">
                                <div class="report-date-primary"><?= e(format_display_date($contract['start_date'])) ?></div>
                                <div class="report-date-secondary"><?= e(format_display_date($contract['end_date'])) ?></div>
                            </td>
                            <td data-label="Rate"><?= e(monthly_contract_money($contract['monthly_rate'])) ?></td>
                            <td data-label="Payment">
                                <span class="resource-pill <?= e(monthly_contract_payment_badge_class((string) $contract['payment_status'])) ?>">
                                    <?= e((string) $contract['payment_status']) ?>
                                </span>
                            </td>
                            <td data-label="Status">
                                <span class="status-pill <?= e(monthly_contract_status_badge_class((string) $contract['status'])) ?>">
                                    <?= e((string) $contract['status']) ?>
                                </span>
                            </td>
                            <td data-label="Action" class="text-center">
                                <div class="table-actions monthly-actions">
                                    <a class="btn btn-sm btn-shell" href="monthly-contract-edit.php?id=<?= e((string) $contract['id']) ?>">Edit</a>
                                    <a class="btn btn-sm btn-shell" href="monthly-contract-renew.php?id=<?= e((string) $contract['id']) ?>">Renew</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
