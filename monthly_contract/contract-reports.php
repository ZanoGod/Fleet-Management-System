<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/monthly-contracts-data.php';

$activePage = 'monthly-contract-reports';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Module';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'MC';
$pageKicker = 'Monthly Contract Module';
$pageTitle = 'Monthly Contract Reports';
$pageSummary = 'Review contract revenue, assignment mix, expiry risk, and export filtered records.';
$pageActions = '<a class="btn btn-shell" href="index.php">Daily Booking</a> <a class="btn btn-accent" href="monthly-contract-create.php">Add Contract</a>';
$pageStyles = ['assets/css/reports.css', 'assets/css/monthly-contracts.css'];
$filters = monthly_contract_filters($_GET);
$contracts = [];
$reportStats = [
    'records' => 0,
    'active' => 0,
    'expiring' => 0,
    'expired' => 0,
    'revenue' => 0,
];

if ($db instanceof mysqli) {
    $contracts = monthly_contract_fetch($db, $filters);

    foreach ($contracts as $contract) {
        $reportStats['records']++;

        if ((string) $contract['status'] === 'Active') {
            $reportStats['active']++;
            $reportStats['revenue'] += (float) $contract['monthly_rate'];
        }

        $expiryState = monthly_contract_expiry_state($contract);

        if ($expiryState === 'expiring') {
            $reportStats['expiring']++;
        }

        if ($expiryState === 'expired') {
            $reportStats['expired']++;
        }
    }
}

$filterQuery = array_filter(
    $filters,
    static fn (string $value): bool => $value !== ''
);
$exportQuery = http_build_query($filterQuery);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<section class="overview-grid report-metrics-grid">
    <div class="card-shell overview-card">
        <span>Records</span>
        <strong><?= e((string) $reportStats['records']) ?></strong>
        <small>Filtered contracts</small>
    </div>

    <div class="card-shell overview-card">
        <span>Active</span>
        <strong><?= e((string) $reportStats['active']) ?></strong>
        <small>Currently running</small>
    </div>

    <div class="card-shell overview-card">
        <span>Expiring</span>
        <strong><?= e((string) $reportStats['expiring']) ?></strong>
        <small>Within 30 days</small>
    </div>

    <div class="card-shell overview-card">
        <span>Expired</span>
        <strong><?= e((string) $reportStats['expired']) ?></strong>
        <small>Past end date</small>
    </div>

    <div class="card-shell overview-card">
        <span>Revenue</span>
        <strong><?= e(monthly_contract_money($reportStats['revenue'])) ?></strong>
        <small>Active monthly value</small>
    </div>
</section>

<section class="card-shell section-card mb-4" id="monthlyContractReportFilters">
    <div class="section-title">
        <div>
            <h2>Report Filters</h2>
            <p>Use filters before exporting the monthly contract report.</p>
        </div>
    </div>

    <form method="get" action="monthly-contract-reports.php#monthlyContractReportResults" class="monthly-contract-filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input
                type="text"
                class="form-control"
                id="search"
                name="search"
                value="<?= e($filters['search']) ?>"
                placeholder="Contract, customer, vehicle, driver, operator">
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
                    <option value="<?= e($assignmentType) ?>" <?= selected($filters['assignment_type'], $assignmentType) ?>>
                        <?= e($assignmentType) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="payment_status" class="form-label">Payment</label>
            <select class="form-select" id="payment_status" name="payment_status">
                <option value="">All Payments</option>
                <?php foreach (monthly_contract_payment_statuses() as $paymentStatus): ?>
                    <option value="<?= e($paymentStatus) ?>" <?= selected($filters['payment_status'], $paymentStatus) ?>>
                        <?= e($paymentStatus) ?>
                    </option>
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
            <button type="submit" class="btn btn-shell">Run</button>
        </div>
    </form>
</section>

<section class="card-shell section-card" id="monthlyContractReportResults">
    <div class="section-title">
        <div>
            <h2>Report Results</h2>
            <p>Filtered monthly contract records.</p>
        </div>
        <div class="report-actions">
            <a href="includes/monthly_contracts_export_excel.php<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>"
                class="btn btn-accent-report btn-excel">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <span>Excel</span>
            </a>
            <a href="includes/monthly_contracts_export_pdf.php<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>"
                class="btn btn-accent-report btn-pdf">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>PDF</span>
            </a>
        </div>
    </div>

    <div class="report-table-shell">
        <table class="table data-table report-table monthly-report-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Customer</th>
                    <th>Assignment</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Operator</th>
                    <th>Dates</th>
                    <th>Rate</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($contracts === []): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted-soft">No report records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td data-label="Contract">
                                <strong><?= e((string) $contract['contract_no']) ?></strong>
                            </td>
                            <td data-label="Customer">
                                <div class="report-guest"><?= e((string) $contract['customer_name']) ?></div>
                                <div class="soft-note"><?= e((string) ($contract['customer_phone'] ?: $contract['customer_email'] ?: '-')) ?></div>
                            </td>
                            <td data-label="Assignment">
                                <span class="resource-pill <?= e(monthly_contract_assignment_badge_class((string) $contract['assignment_type'])) ?>">
                                    <?= e((string) $contract['assignment_type']) ?>
                                </span>
                            </td>
                            <td data-label="Vehicle"><?= e(monthly_contract_vehicle_display($contract)) ?></td>
                            <td data-label="Driver"><?= e(monthly_contract_driver_display($contract)) ?></td>
                            <td data-label="Operator"><?= e(monthly_contract_operator_display($contract)) ?></td>
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
