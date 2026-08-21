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
                <p>Contracts ending within the next 60 days.</p>
            </div>
        </div>

        <div class="monthly-expiry-list">
            <?php foreach ($expiringContracts as $contract): ?>
                <div class="monthly-expiry-item">
                    <div>
                        <?php
                        $contactPerson = trim((string) ($contract['contact_person'] ?? ''));
                        $guestName = trim((string) ($contract['guest_name'] ?? ''));
                        $customerName = trim((string) ($contract['customer_name'] ?? ''));

                        $displayName = $contactPerson !== ''
                            ? $contactPerson
                            : ($guestName !== '' ? $guestName : $customerName);
                        ?>

                        <strong><?= e($displayName) ?></strong>

                        <?php if ($customerName !== '' && $displayName !== $customerName): ?>
                            <span><?= e($customerName) ?></span>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn-sm btn-shell" href="contract_renew.php?id=<?= e((string) $contract['id']) ?>">
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

    <form method="get" action="monthly-contracts.php#monthlyContractsResultsSection"
        class="monthly-contract-filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>"
                placeholder="Contract, customer, vehicle, driver">
        </div>

        <div>
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (monthly_contract_statuses() as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e($status) ?>
                    </option>
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
            <label for="rental_status" class="form-label">Rental</label>
            <select class="form-select" id="rental_status" name="rental_status">
                <option value="">Rental Status</option>
                <?php foreach (monthly_contract_rental_statuses() as $rentalStatus): ?>
                    <option value="<?= e($rentalStatus) ?>" <?= selected($filters['rental_status'], $rentalStatus) ?>>
                        <?= e($rentalStatus) ?>
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

        <div class="d-flex gap-2 align-self-end">
            <button type="submit" class="btn btn-shell">
                Filter
            </button>

            <button type="button" class="btn btn-outline-dark" id="resetFilters">
                Reset
            </button>
        </div>
    </form>
</section>

<section class="card-shell section-card" id="monthlyContractsResultsSection">
    <div class="section-title">
        <div>
            <h2>Contract List</h2>
            <p><?= e((string) $totalRecords) ?> contract records found.</p>
        </div>
        <button type="button" class="btn btn-shell btn-sm" id="monthlyTableFullscreenBtn"
            data-fullscreen-target="monthlyTable" aria-label="Open contract table in fullscreen">
            <span class="fullscreen-label">Fullscreen Table</span>
            <span class="fullscreen-exit-label" hidden>Exit Fullscreen</span>
        </button>

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
        <button type="button" class="monthly-fullscreen-exit" id="monthlyTableInlineExit" hidden
            aria-label="Exit fullscreen table">
            <span aria-hidden="true">×</span> Exit Fullscreen
        </button>
        <table class="table data-table monthly-contract-table align-middle">
            <thead>
                <tr>
                    <th class="text-center">No.</th>
                    <th>Company / Contract</th>
                    <th>Car Type</th>
                    <th>Car No</th>
                    <th>Driver</th>
                    <th>Driver Phone</th>
                    <th>Operation Sheet</th>
                    <th>Contract Date</th>
                    <th>Off Day</th>
                    <th>Service Hour</th>
                    <th>Rental Price</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($contracts === []): ?>
                    <tr>
                        <td colspan="13" class="text-center py-5 text-muted-soft">No monthly contracts found.</td>
                    </tr>
                <?php else: ?>

                    <?php $rowNo = $offset + 1;
                    foreach ($contracts as $contract): ?>
                        <tr>
                            <td class="text-center fw-bold">
                                <?= $rowNo++ ?>
                            </td>
                            <td>
                                <div class="monthly-customer"><?= e($contract['customer_name']) ?></div>
                                <div class="soft-note">
                                    <?= e(trim((string)($contract['contact_person'] ?? '')) !== '' ? $contract['contact_person'] : '') ?>

                                    <!-- <?= e(trim((string)($contract['guest_name'] ?? '')) !== '' ? $contract['guest_name'] : '') ?> -->
                                </div>
                            </td>

                            <td>
                                <?= e($contract['car_type']) ?>
                            </td>

                            <td>
                                <?= e($contract['plate_no']) ?>
                            </td>

                            <td>
                                <?= e($contract['driver_name']) ?>
                            </td>

                            <td>
                                <?= e($contract['driver_phone']) ?>
                            </td>

                            <td>
                                <?= e($contract['operation_sheet']) ?>
                            </td>

                            <td>
                                <?= e(date('d-M-Y', strtotime($contract['start_date']))) ?>
                                <div class="soft-note">
                                    <?= e(date('d-M-Y', strtotime($contract['end_date']))) ?>
                                </div>
                            </td>

                            <td>
                                <?= e($contract['off_day']) ?>
                            </td>

                            <td>
                                <?= e($contract['service_hour']) ?>
                            </td>

                            <td>
                                <?= e(monthly_contract_money($contract['monthly_rate'])) ?>
                                <?= e($contract['rental_currency']) ?>
                            </td>

                            <td>

                                <span class="status-pill <?= e(monthly_contract_status_badge_class($contract['status'])) ?>">
                                    <?= e($contract['status']) ?>
                                </span>

                            </td>

                            <td class="text-center">

                                <div class="table-actions monthly-actions">

                                    <a href="monthly-contract-view.php?id=<?= (int)$contract['id'] ?>"
                                        class="btn btn-sm btn-shell">
                                        View
                                    </a>

                                    <a href="contract_edit.php?id=<?= (int)$contract['id'] ?>" class="btn btn-sm btn-shell">
                                        Edit
                                    </a>

                                    <a href="contract_renew.php?id=<?= (int)$contract['id'] ?>" class="btn btn-sm btn-shell">
                                        Renew
                                    </a>

                                    <form method="post" action="contract_delete.php?id=<?= (int)$contract['id'] ?>"
                                        onsubmit="return confirm('Delete this monthly contract?');">

                                        <?= csrf_field() ?>

                                        <button type="submit" class="btn btn-sm btn-delete">
                                            Delete
                                        </button>

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
        <nav class="monthly-pagination fleet-pagination" aria-label="Monthly contract pagination">
            <ul class="pagination justify-content-end mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $pageQuery = $filterQuery;
                    $pageQuery['page'] = (string) $i;
                    ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link"
                            href="monthly-contracts.php?<?= e(http_build_query($pageQuery)) ?>#monthlyContractsResultsSection">
                            <?= e((string) $i) ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    
</section>

<script>
(() => {
    const table = document.getElementById('monthlyTable');
    const toggle = document.getElementById('monthlyTableFullscreenBtn');
    const inlineExit = document.getElementById('monthlyTableInlineExit');

    if (!table || !toggle) return;

    const label = toggle.querySelector('.fullscreen-label');
    const exitLabel = toggle.querySelector('.fullscreen-exit-label');

    const isFullscreen = () => document.fullscreenElement === table;

    const syncFullscreenUI = () => {
        const active = isFullscreen();
        if (label) label.hidden = active;
        if (exitLabel) exitLabel.hidden = !active;
        toggle.setAttribute('aria-label', active ? 'Exit fullscreen table' : 'Open contract table in fullscreen');
        if (inlineExit) inlineExit.hidden = !active;
    };

    toggle.addEventListener('click', async () => {
        try {
            if (isFullscreen()) {
                await document.exitFullscreen();
            } else {
                await table.requestFullscreen();
            }
        } catch (error) {
            console.error('Unable to change fullscreen state:', error);
        }
    });

    if (inlineExit) {
        inlineExit.addEventListener('click', async () => {
            if (isFullscreen()) {
                try {
                    await document.exitFullscreen();
                } catch (error) {
                    console.error('Unable to exit fullscreen:', error);
                }
            }
        });
    }

    document.addEventListener('fullscreenchange', syncFullscreenUI);
    syncFullscreenUI();
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
