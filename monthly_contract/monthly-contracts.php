<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';

$activePage = 'monthly-contract-dashboard';
$navigationType = 'monthly_contract';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Dashboard';
$pageSummary = 'Manage monthly customer assignments, renewals, expiry alerts, and contract reporting.';
$pageActions = '<a class="btn btn-dashboard" href="../index.php">Daily Booking</a> <a class="btn btn-accent" href="contract_create.php">Add Contract</a>';
$pageStyles = [
    'assets/css/monthly-contracts.css',
    'assets/css/reports.css',
];
$flash = get_flash();
$filters = monthly_contract_filters($_GET);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$contracts = [];
$expiringContracts = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'expiring' => 0,
    'expired' => 0,
    'monthly_revenue' => 0,
];
$totalRecords = 0;
$totalPages = 1;

if ($db instanceof mysqli) {
    $stats = monthly_contract_stats($db);
    $totalRecords = monthly_contract_count($db, $filters);
    $totalPages = max(1, (int) ceil($totalRecords / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $contracts = monthly_contract_fetch($db, $filters, $perPage, $offset);
    $expiringContracts = monthly_contract_expiry_notifications($db);
}

$filterQuery = array_filter(
    $filters,
    static fn(string $value): bool => $value !== ''
);

$exportQuery = http_build_query($filterQuery);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Contracts</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All monthly agreements</small>
    </div>

    <div class="card-shell overview-card">
        <span>Active</span>
        <strong><?= e((string) ($stats['active'] ?? 0)) ?></strong>
        <small>Currently running</small>
    </div>

    <div class="card-shell overview-card">
        <span>Expiring Soon</span>
        <strong><?= e((string) ($stats['expiring'] ?? 0)) ?></strong>
        <small>Within the next 30 days</small>
    </div>

    <div class="card-shell overview-card">
        <span>Expired</span>
        <strong><?= e((string) ($stats['expired'] ?? 0)) ?></strong>
        <small>Need review or renewal</small>
    </div>
</section>

<?php if ($expiringContracts !== []): ?>
    <section class="card-shell section-card mb-4">
        <div class="section-title">
            <div>
                <h2>Expiry Notifications</h2>
                <p>Contracts ending within the next 30 days.</p>
            </div>
        </div>

        <div class="monthly-expiry-list">
            <?php foreach ($expiringContracts as $contract): ?>
                <div class="monthly-expiry-item">
                    <div>
                        <strong><?= e((string) $contract['contract_no']) ?></strong>
                        <span><?= e((string) $contract['customer_name']) ?></span>
                    </div>
                    <a class="btn btn-sm btn-shell" href="monthly-contract-renew.php?id=<?= e((string) $contract['id']) ?>">
                        Renew
                    </a>
                    <span class="status-pill status-pending">
                        Ends <?= e(format_display_date($contract['end_date'])) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="card-shell section-card mb-4" id="monthlyContractsFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Contracts</h2>
            <p>Filter by customer, contract number, resource, status, payment, expiry, or date.</p>
        </div>

    </div>

    <form method="get" action="monthly-contracts.php#monthlyContractsResultsSection" class="monthly-contract-filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input
                type="text"
                class="form-control"
                id="search"
                name="search"
                value="<?= e($filters['search']) ?>"
                placeholder="Contract, customer, vehicle, driver">
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
            <button type="submit" class="btn btn-shell">Filter</button>
        </div>
    </form>
</section>

<section class="card-shell section-card" id="monthlyContractsResultsSection">
    <div class="section-title">
        <div>
            <h2>Contract List</h2>
            <p><?= e((string) $totalRecords) ?> contract records found.</p>
        </div>
        <button type="button" class="btn btn-shell btn-sm" data-fullscreen-target="monthlyTable">Fullscreen Table</button>

        <div class="report-actions">
            <a href="monthly_contracts_export_excel.php<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>"
                class="btn btn-accent-report btn-excel">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <span>Excel</span>
            </a>
            <a href="monthly_contracts_export_pdf.php<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>"
                class="btn btn-accent-report btn-pdf">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>PDF</span>
            </a>
        </div>
    </div>

    <div class="table-responsive" id="monthlyTable">
        <table class="table data-table monthly-contract-table align-middle">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Customer</th>
                    <th>Assignment</th>
                    <th>Dates</th>
                    <th>Rate</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($contracts === []): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted-soft">No monthly contracts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $contract): ?>
                        <?php
                        $expiryState = monthly_contract_expiry_state($contract);
                        $expiryClass = $expiryState === 'expired'
                            ? 'status-pending'
                            : ($expiryState === 'expiring' ? 'resource-assigned' : 'resource-available');
                        $expiryLabel = match ($expiryState) {
                            'expired' => 'Expired',
                            'expiring' => 'Ending Soon',
                            default => 'On Track',
                        };
                        ?>
                        <tr>
                            <td>
                                <div class="monthly-contract-no"><?= e((string) $contract['contract_no']) ?></div>
                                <?php if (!empty($contract['renewal_parent_no'])): ?>
                                    <div class="soft-note">Renewed from <?= e((string) $contract['renewal_parent_no']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="monthly-customer"><?= e((string) $contract['customer_name']) ?></div>
                                <div class="soft-note"><?= e((string) ($contract['customer_phone'] ?: $contract['customer_email'] ?: '-')) ?></div>
                                <div class="soft-note">Operator: <?= e(monthly_contract_operator_display($contract)) ?></div>
                            </td>

                            <td>
                                <div class="monthly-assignment-stack">
                                    <span class="resource-pill <?= e(monthly_contract_assignment_badge_class((string) $contract['assignment_type'])) ?>">
                                        <?= e((string) $contract['assignment_type']) ?>
                                    </span>
                                    <span class="table-pill car-pill monthly-resource-pill">
                                        <?= e(monthly_contract_vehicle_display($contract)) ?>
                                    </span>
                                    <span class="table-pill driver-pill monthly-resource-pill">
                                        <?= e(monthly_contract_driver_display($contract)) ?>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="monthly-date-primary"><?= e(format_display_date($contract['start_date'])) ?></div>
                                <div class="soft-note"><?= e(format_display_date($contract['end_date'])) ?></div>
                            </td>

                            <td>
                                <div class="monthly-money"><?= e(monthly_contract_money($contract['monthly_rate'])) ?></div>
                                <div class="soft-note">Deposit <?= e(monthly_contract_money($contract['security_deposit'])) ?></div>
                            </td>

                            <td>
                                <span class="resource-pill <?= e(monthly_contract_payment_badge_class((string) $contract['payment_status'])) ?>">
                                    <?= e((string) $contract['payment_status']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-pill <?= e(monthly_contract_status_badge_class((string) $contract['status'])) ?>">
                                    <?= e((string) $contract['status']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="resource-pill <?= e($expiryClass) ?>"><?= e($expiryLabel) ?></span>
                                <div class="soft-note"><?= e(format_display_date($contract['end_date'])) ?></div>
                            </td>

                            <td class="text-center">
                                <div class="table-actions monthly-actions">
                                    <a class="btn btn-sm btn-shell" href="contract_edit.php?id=<?= e((string) $contract['id']) ?>">Edit</a>
                                    <a class="btn btn-sm btn-shell" href="contract_renew.php?id=<?= e((string) $contract['id']) ?>">Renew</a>
                                    <form
                                        method="post"
                                        action="monthly-contract-delete.php?id=<?= e((string) $contract['id']) ?>"
                                        onsubmit="return confirm('Delete this monthly contract?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="monthly-pagination" aria-label="Monthly contract pagination">
            <ul class="pagination justify-content-end mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $pageQuery = $filterQuery;
                    $pageQuery['page'] = (string) $i;
                    ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="monthly-contracts.php?<?= e(http_build_query($pageQuery)) ?>#monthlyContractsResultsSection">
                            <?= e((string) $i) ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>