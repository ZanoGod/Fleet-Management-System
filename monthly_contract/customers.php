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
$pageSummary = 'Manage customer profiles independently from their monthly contract assignments.';
$pageActions = '<a class="btn btn-accent" href="customers_add.php">Add Customer</a>';
$pageStyles = [
    'assets/css/customers.css',
    'assets/css/monthly-contracts.css',
];

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'assignment_type' => trim((string) ($_GET['assignment_type'] ?? '')),
    'rental_status' => trim((string) ($_GET['rental_status'] ?? '')),
    'expiry' => trim((string) ($_GET['expiry'] ?? '')),
];

/*
|--------------------------------------------------------------------------
| SELECTED MONTH
|--------------------------------------------------------------------------
| Controls which monthly contract details are shown in each customer row.
*/
$selectedMonthValue = trim((string) ($_GET['month'] ?? ''));
$selectedMonth = DateTimeImmutable::createFromFormat('!Y-m', $selectedMonthValue);
if (!$selectedMonth || $selectedMonth->format('Y-m') !== $selectedMonthValue) {
    $selectedMonth = new DateTimeImmutable('first day of this month');
}
$selectedMonthValue = $selectedMonth->format('Y-m');
$selectedMonthStart = $selectedMonth->modify('first day of this month')->setTime(0, 0, 0);
$selectedMonthEnd = $selectedMonth->modify('last day of this month')->setTime(23, 59, 59);
$previousMonth = $selectedMonth->modify('-1 month');
$nextMonth = $selectedMonth->modify('+1 month');
$currentMonthValue = (new DateTimeImmutable('first day of this month'))->format('Y-m');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalRecords = 0;
$totalPages = 1;

$buildMonthUrl = static function (string $month) use ($filters): string {
    $query = array_filter([
        'search' => $filters['search'],
        'status' => $filters['status'],
        'assignment_type' => $filters['assignment_type'],
        'rental_status' => $filters['rental_status'],
        'expiry' => $filters['expiry'],
        'month' => $month,
        'page' => '1',
    ], static fn(string $value): bool => $value !== '');
    return 'customers.php?' . http_build_query($query) . '#monthlyCustomersResultsSection';
};

$customers = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'with_contracts' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    /*
     * Customer search stays at customer level.
     * Contract-related filters use EXISTS so one customer is never
     * duplicated just because it has multiple monthly contracts.
     */
    if ($filters['search'] !== '') {
        $where[] = "(
            c.company_name LIKE ?
            OR c.guest_name LIKE ?
            OR c.contact_person LIKE ?
            OR c.contact_phone LIKE ?
            OR c.contact_email LIKE ?
            OR c.customer_address LIKE ?
            OR c.remark LIKE ?
            OR EXISTS (
                SELECT 1
                FROM monthly_contracts mc_search
                WHERE mc_search.customer_id = c.id
                  AND (
                      mc_search.contract_no LIKE ?
                      OR mc_search.operator_name LIKE ?
                  )
            )
        )";

        $search = '%' . $filters['search'] . '%';
        $types .= 'sssssssss';
        array_push(
            $params,
            $search,
            $search,
            $search,
            $search,
            $search,
            $search,
            $search,
            $search,
            $search
        );
    }

    if ($filters['status'] !== '') {
        $where[] = 'c.customer_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    if ($filters['assignment_type'] !== '') {
        $where[] = "
            EXISTS (
                SELECT 1
                FROM monthly_contracts mc_assignment
                WHERE mc_assignment.customer_id = c.id
                  AND mc_assignment.assignment_type = ?
            )
        ";
        $types .= 's';
        $params[] = $filters['assignment_type'];
    }

    if ($filters['rental_status'] !== '') {
        $where[] = "
            EXISTS (
                SELECT 1
                FROM monthly_contracts mc_rental
                WHERE mc_rental.customer_id = c.id
                  AND mc_rental.rental_status = ?
            )
        ";
        $types .= 's';
        $params[] = $filters['rental_status'];
    }

    if ($filters['expiry'] === 'expiring') {
        $where[] = "
            EXISTS (
                SELECT 1
                FROM monthly_contracts mc_expiring
                WHERE mc_expiring.customer_id = c.id
                  AND mc_expiring.end_date >= CURDATE()
                  AND mc_expiring.end_date < DATE_ADD(CURDATE(), INTERVAL 60 DAY)
            )
        ";
    }

    if ($filters['expiry'] === 'expired') {
        $where[] = "
            EXISTS (
                SELECT 1
                FROM monthly_contracts mc_expired
                WHERE mc_expired.customer_id = c.id
                  AND mc_expired.end_date < CURDATE()
            )
        ";
    }

    $sql = "
        SELECT
            c.id,
            c.company_name,
            c.guest_name,
            c.contact_person,
            c.contact_phone,
            c.contact_email,
            c.customer_address,
            c.off_day,
            c.service_hour,
            c.customer_status,
            c.remark,
            (
                SELECT COUNT(*)
                FROM monthly_contracts mc_count
                WHERE mc_count.customer_id = c.id
            ) AS contract_count,
            (
                SELECT COUNT(*)
                FROM monthly_contracts mc_active
                WHERE mc_active.customer_id = c.id
                  AND mc_active.status = 'Active'
            ) AS active_contract_count,
            (
                SELECT MAX(mc_latest.end_date)
                FROM monthly_contracts mc_latest
                WHERE mc_latest.customer_id = c.id
            ) AS latest_expiry
        FROM customers c
    ";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY c.company_name ASC';

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

    $totalRecords = count($customers);
    $totalPages = max(1, (int) ceil($totalRecords / $perPage));
    $page = min($page, $totalPages);
    $customers = array_slice($customers, ($page - 1) * $perPage, $perPage);

    $statsResult = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(customer_status = 'Active') AS active,
            SUM(
                EXISTS (
                    SELECT 1
                    FROM monthly_contracts mc_stats
                    WHERE mc_stats.customer_id = customers.id
                )
            ) AS with_contracts
         FROM customers"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

/*
 * Load contract details separately for the expandable customer rows.
 * The main customer query remains one-row-per-customer.
 */
$customerContractsById = [];

if ($db instanceof mysqli && $customers !== []) {
    $customerIds = [];

    foreach ($customers as $customer) {
        $customerId = (int) ($customer['id'] ?? 0);
        if ($customerId > 0) {
            $customerIds[] = $customerId;
        }
    }

    $customerIds = array_values(array_unique($customerIds));

    if ($customerIds !== []) {
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        $types = str_repeat('i', count($customerIds));

        $contractSql = "
            SELECT
                mc.id,
                mc.contract_no,
                mc.customer_id,
                mc.guest_name,
                mc.contact_person,
                mc.assignment_type,
                mc.start_date,
                mc.end_date,
                mc.rental_status,
                mc.status,
                c.car_type,
                c.plate_no,
                d.full_name AS driver_name,
                o.full_name AS operator_full_name
            FROM monthly_contracts AS mc
            LEFT JOIN monthly_cars AS c ON c.id = mc.car_id
            LEFT JOIN monthly_drivers AS d ON d.id = mc.driver_id
            LEFT JOIN operators AS o ON o.id = mc.operator_id
            WHERE mc.customer_id IN ($placeholders)
              AND mc.start_date <= ?
              AND mc.end_date >= ?
              AND mc.status NOT IN ('Cancelled', 'Canceled', 'Cancel')
            ORDER BY mc.customer_id ASC, mc.end_date ASC, mc.id DESC
        ";

        $contractStatement = $db->prepare($contractSql);

        if ($contractStatement instanceof mysqli_stmt) {
            $contractParams = $customerIds;
            $contractParams[] = $selectedMonthEnd->format('Y-m-d H:i:s');
            $contractParams[] = $selectedMonthStart->format('Y-m-d H:i:s');
            $types .= 'ss';
            bind_statement_params($contractStatement, $types, $contractParams);
            $contractStatement->execute();

            $contractResult = $contractStatement->get_result();

            while ($contract = $contractResult->fetch_assoc()) {
                $contractCustomerId = (int) ($contract['customer_id'] ?? 0);

                if (!isset($customerContractsById[$contractCustomerId])) {
                    $customerContractsById[$contractCustomerId] = [];
                }

                $customerContractsById[$contractCustomerId][] = $contract;
            }

            $contractStatement->close();
        }
    }
}


require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<section class="overview-grid report-metrics-grid">
    <div class="card-shell overview-card">
        <span>Total Customers</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All registered customer profiles</small>
    </div>

    <div class="card-shell overview-card">
        <span>Active Customers</span>
        <strong><?= e((string) ($stats['active'] ?? 0)) ?></strong>
        <small>Currently active customers</small>
    </div>

    <div class="card-shell overview-card">
        <span>With Contracts</span>
        <strong><?= e((string) ($stats['with_contracts'] ?? 0)) ?></strong>
        <small>Customers linked to contracts</small>
    </div>
</section>

<section class="card-shell section-card mb-4" id="customersFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Customers</h2>
            <p>Filter by customer details or linked monthly contract conditions.</p>
        </div>
    </div>

    <form method="get" action="customers.php#monthlyCustomersResultsSection" class="filter-grid customer-filter-form">
        <div class="customer-search-field">
            <label for="search" class="form-label">Search</label>
            <div class="customer-search-input">
                <span aria-hidden="true">⌕</span>
                <input
                    type="text"
                    class="form-control"
                    id="search"
                    name="search"
                    value="<?= e($filters['search']) ?>"
                    placeholder="Company, contact, phone, email, contract no..."
                    autocomplete="off">
            </div>
        </div>

        <div>
            <label for="status" class="form-label">Customer Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (['Active', 'Inactive'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>>
                        <?= e($status) ?>
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
                <option value="">All Rental Statuses</option>
                <?php foreach (monthly_contract_rental_statuses() as $rentalStatus): ?>
                    <option value="<?= e($rentalStatus) ?>" <?= selected($filters['rental_status'], $rentalStatus) ?>>
                        <?= e($rentalStatus) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="expiry" class="form-label">Contract Expiry</label>
            <select class="form-select" id="expiry" name="expiry">
                <option value="">All Contracts</option>
                <option value="expiring" <?= selected($filters['expiry'], 'expiring') ?>>Within 60 Days</option>
                <option value="expired" <?= selected($filters['expiry'], 'expired') ?>>Expired</option>
            </select>
        </div>

        <div class="d-grid align-self-end">
            <button type="submit" class="btn btn-shell">Filter</button>
        </div>
        <input type="hidden" name="month" value="<?= e($selectedMonthValue) ?>">
        <div class="fleet-month-filter" aria-label="Contract month selector">
            <span class="fleet-month-label">Month</span>
            <div class="fleet-month-nav">
                <a class="fleet-month-arrow" href="<?= e($buildMonthUrl($previousMonth->format('Y-m'))) ?>" aria-label="Previous month" title="Previous month">‹</a>
                <span class="fleet-month-value"><?= e($selectedMonth->format('F Y')) ?></span>
                <a class="fleet-month-arrow" href="<?= e($buildMonthUrl($nextMonth->format('Y-m'))) ?>" aria-label="Next month" title="Next month">›</a>
                <?php if ($selectedMonthValue !== $currentMonthValue): ?>
                    <a class="fleet-month-today" href="<?= e($buildMonthUrl($currentMonthValue)) ?>">Today</a>
                <?php else: ?>
                    <span class="fleet-month-today is-current">Today</span>
                <?php endif; ?>
            </div>
        </div>
    </form>
</section>

<section class="card-shell section-card customer-results-section" id="monthlyCustomersResultsSection">
    <div class="section-title">
        <div>
            <h2>Customer List</h2>
            <p>
                <?= e((string) count($customers)) ?>
                customer<?= count($customers) === 1 ? '' : 's' ?> found.
                Each customer appears once; monthly contracts for <?= e($selectedMonth->format('F Y')) ?> are shown inside the expandable row.
            </p>
        </div>

        <button
            type="button"
            class="btn btn-shell btn-sm customer-fullscreen-toggle"
            id="customerFullscreenToggle"
            aria-controls="monthlyCustomersResultsSection"
            aria-pressed="false">
            Fullscreen Table
        </button>
    </div>

    <div class="customer-directory-table-wrap">
        <table class="table data-table customers-table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Contracts</th>
                    <th>Latest Expiry</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($customers === []): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted-soft">
                            No customers found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $index => $customer): ?>
                        <?php
                        $customerId = (int) ($customer['id'] ?? 0);
                        $companyName = trim((string) ($customer['company_name'] ?? ''));
                        $guestName = trim((string) ($customer['guest_name'] ?? ''));
                        $contactPerson = trim((string) ($customer['contact_person'] ?? ''));
                        $phone = trim((string) ($customer['contact_phone'] ?? ''));
                        $email = trim((string) ($customer['contact_email'] ?? ''));
                        $address = trim((string) ($customer['customer_address'] ?? ''));
                        $offDay = trim((string) ($customer['off_day'] ?? ''));
                        $serviceHour = trim((string) ($customer['service_hour'] ?? ''));
                        $customerStatus = trim((string) ($customer['customer_status'] ?? 'Inactive'));
                        $remark = trim((string) ($customer['remark'] ?? ''));
                        $contractCount = (int) ($customer['contract_count'] ?? 0);
                        $activeContractCount = (int) ($customer['active_contract_count'] ?? 0);
                        $latestExpiry = trim((string) ($customer['latest_expiry'] ?? ''));
                        $customerContracts = $customerContractsById[$customerId] ?? [];
                        $drawerId = 'customerDrawerContent' . $customerId;
                        ?>

                        <tr
                            class="customer-main-row customer-drawer-trigger"
                            data-customer-drawer="<?= e($drawerId) ?>"
                            tabindex="0"
                            role="button"
                            aria-label="View details for <?= e($companyName !== '' ? $companyName : 'customer') ?>">
                            <td class="text-center customer-index-cell">
                                <?= e((string) (($page - 1) * $perPage + $index + 1)) ?>
                            </td>

                            <td class="customer-company">
                                <div class="customer-company-cell">
                                    <div class="customer-company-avatar" aria-hidden="true">
                                        <?= e(strtoupper(substr($companyName !== '' ? $companyName : 'C', 0, 1))) ?>
                                    </div>
                                    <div class="customer-company-copy">
                                        <strong><?= e($companyName !== '' ? $companyName : '—') ?></strong>
                                        <small>
                                            <?= $guestName !== '' ? e($guestName) : 'No guest name' ?>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="customer-table-contact">
                                    <strong><?= e($contactPerson !== '' ? $contactPerson : '—') ?></strong>
                                    <?php if ($email !== ''): ?>
                                        <small><?= e($email) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="customer-phone">
                                <?= e($phone !== '' ? $phone : '—') ?>
                            </td>

                            <td>
                                <?php if ($contractCount > 0): ?>
                                    <span class="customer-contract-count-badge">
                                        <strong><?= e((string) $contractCount) ?></strong>
                                        <span><?= $contractCount === 1 ? 'contract' : 'contracts' ?></span>
                                    </span>
                                    <small class="customer-active-contracts">
                                        <?= e((string) $activeContractCount) ?> active
                                    </small>
                                <?php else: ?>
                                    <span class="customer-no-contract">No contracts</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($latestExpiry !== ''): ?>
                                    <span class="customer-expiry"><?= e(format_display_date($latestExpiry)) ?></span>
                                <?php else: ?>
                                    <span class="customer-no-contract">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <span class="customer-status <?= strtolower($customerStatus) ?>">
                                    <span class="customer-status-dot"></span>
                                    <?= e($customerStatus) ?>
                                </span>
                            </td>

                            <td class="text-end">
                                <div class="customer-row-actions">
                                    <button
                                        type="button"
                                        class="btn btn-view-customer btn-sm"
                                        data-customer-drawer="<?= e($drawerId) ?>">
                                        View
                                    </button>
                                    <a
                                        href="customers_edit.php?id=<?= e((string) $customerId) ?>"
                                        class="btn btn-edit btn-sm"
                                        onclick="event.stopPropagation();">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>


                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <div class="customer-drawer-sources" aria-hidden="true">
        <?php if ($customers !== []): ?>
            <?php foreach ($customers as $customer): ?>
                <?php
                $customerId = (int) ($customer['id'] ?? 0);
                $companyName = trim((string) ($customer['company_name'] ?? ''));
                $guestName = trim((string) ($customer['guest_name'] ?? ''));
                $contactPerson = trim((string) ($customer['contact_person'] ?? ''));
                $phone = trim((string) ($customer['contact_phone'] ?? ''));
                $email = trim((string) ($customer['contact_email'] ?? ''));
                $address = trim((string) ($customer['customer_address'] ?? ''));
                $offDay = trim((string) ($customer['off_day'] ?? ''));
                $serviceHour = trim((string) ($customer['service_hour'] ?? ''));
                $customerStatus = trim((string) ($customer['customer_status'] ?? 'Inactive'));
                $remark = trim((string) ($customer['remark'] ?? ''));
                $contractCount = (int) ($customer['contract_count'] ?? 0);
                $activeContractCount = (int) ($customer['active_contract_count'] ?? 0);
                $latestExpiry = trim((string) ($customer['latest_expiry'] ?? ''));
                $customerContracts = $customerContractsById[$customerId] ?? [];
                $drawerId = 'customerDrawerContent' . $customerId;
                ?>
                <div class="customer-drawer-source" id="<?= e($drawerId) ?>">
                    <div class="customer-drawer-company-header">
                        <div class="customer-company-avatar customer-company-avatar-large" aria-hidden="true">
                            <?= e(strtoupper(substr($companyName !== '' ? $companyName : 'C', 0, 1))) ?>
                        </div>
                        <div>
                            <span class="customer-drawer-kicker">Customer Profile</span>
                            <h3><?= e($companyName !== '' ? $companyName : '—') ?></h3>
                            <div class="customer-drawer-status-row">
                                <span class="customer-status <?= strtolower($customerStatus) ?>">
                                    <span class="customer-status-dot"></span>
                                    <?= e($customerStatus) ?>
                                </span>
                                <span class="customer-drawer-contract-summary">
                                    <?= e((string) $contractCount) ?> contract<?= $contractCount === 1 ? '' : 's' ?>
                                    · <?= e((string) $activeContractCount) ?> active
                                </span>
                            </div>
                        </div>
                    </div>

                    <section class="customer-drawer-section">
                        <div class="customer-drawer-section-heading">
                            <div>
                                <span class="customer-drawer-kicker">Customer Information</span>
                                <h4>Profile & Contact</h4>
                            </div>
                        </div>

                        <div class="customer-detail-grid">
                            <div class="customer-detail-item"><span>Guest</span><strong><?= e($guestName !== '' ? $guestName : '—') ?></strong></div>
                            <div class="customer-detail-item"><span>Contact Person</span><strong><?= e($contactPerson !== '' ? $contactPerson : '—') ?></strong></div>
                            <div class="customer-detail-item"><span>Phone</span><strong><?= e($phone !== '' ? $phone : '—') ?></strong></div>
                            <div class="customer-detail-item"><span>Email</span><strong><?= e($email !== '' ? $email : '—') ?></strong></div>
                            <div class="customer-detail-item customer-detail-item-wide"><span>Address</span><strong><?= e($address !== '' ? $address : '—') ?></strong></div>
                            <div class="customer-detail-item"><span>Off Day</span><strong><?= e($offDay !== '' ? $offDay : '—') ?></strong></div>
                            <div class="customer-detail-item"><span>Service Hour</span><strong><?= e($serviceHour !== '' ? $serviceHour : '—') ?></strong></div>
                            <div class="customer-detail-item customer-detail-item-wide"><span>Remark</span><strong><?= e($remark !== '' ? $remark : '—') ?></strong></div>
                        </div>
                    </section>

                    <section class="customer-drawer-section customer-contracts-section">
                        <div class="customer-drawer-section-heading">
                            <div>
                                <span class="customer-drawer-kicker">Contracts & Terms</span>
                                <h4>Monthly Contracts</h4>
                            </div>
                            <div class="customer-drawer-contract-count"><?= e((string) $contractCount) ?> total</div>
                        </div>

                        <?php if ($customerContracts !== []): ?>
                            <div class="customer-contract-cards">
                                <?php foreach ($customerContracts as $contract): ?>
                                    <?php
                                    $contractStatus = trim((string) ($contract['status'] ?? ''));
                                    $assignmentType = trim((string) ($contract['assignment_type'] ?? ''));
                                    $carType = trim((string) ($contract['car_type'] ?? ''));
                                    $plateNo = trim((string) ($contract['plate_no'] ?? ''));
                                    $driverName = trim((string) ($contract['driver_name'] ?? ''));
                                    $operatorName = trim((string) ($contract['operator_full_name'] ?? ''));
                                    $contractGuest = trim((string) ($contract['guest_name'] ?? ''));
                                    $contractContact = trim((string) ($contract['contact_person'] ?? ''));
                                    $rentalStatus = trim((string) ($contract['rental_status'] ?? ''));
                                    $startDate = trim((string) ($contract['start_date'] ?? ''));
                                    $endDate = trim((string) ($contract['end_date'] ?? ''));
                                    $contractNo = trim((string) ($contract['contract_no'] ?? ''));
                                    ?>
                                    <article class="customer-contract-card">
                                        <div class="customer-contract-card-header">
                                            <div>
                                                <span class="customer-drawer-kicker">Contract</span>
                                                <a href="monthly-contracts.php?search=<?= rawurlencode($contractNo) ?>#monthlyContractsResultsSection" class="customer-contract-number">
                                                    <?= e($contractNo !== '' ? $contractNo : '—') ?>
                                                </a>
                                            </div>
                                            <span class="customer-contract-status <?= strtolower($contractStatus) ?>">
                                                <?= e($contractStatus !== '' ? $contractStatus : '—') ?>
                                            </span>
                                        </div>
                                        <div class="customer-contract-card-grid">
                                            <div><span>Guest / Contact</span><strong><?= e($contractGuest !== '' ? $contractGuest : '—') ?><?= $contractContact !== '' ? ' / ' . e($contractContact) : '' ?></strong></div>
                                            <div><span>Assignment</span><strong><?= e($assignmentType !== '' ? $assignmentType : '—') ?></strong></div>
                                            <div><span>Vehicle</span><strong><?= e($carType !== '' ? $carType : '—') ?><?= $plateNo !== '' ? ' · ' . e($plateNo) : '' ?></strong></div>
                                            <div><span>Driver</span><strong><?= e($driverName !== '' ? $driverName : '—') ?></strong></div>
                                            <div><span>Operator</span><strong><?= e($operatorName !== '' ? $operatorName : '—') ?></strong></div>
                                            <div><span>Rental</span><strong><?= e($rentalStatus !== '' ? $rentalStatus : '—') ?></strong></div>
                                            <div><span>Start</span><strong><?= e($startDate !== '' ? format_display_date($startDate) : '—') ?></strong></div>
                                            <div><span>End</span><strong><?= e($endDate !== '' ? format_display_date($endDate) : '—') ?></strong></div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="customer-contract-empty">This customer has no monthly contracts.</div>
                        <?php endif; ?>
                    </section>

                    <div class="customer-drawer-footer">
                        <a href="customers_edit.php?id=<?= e((string) $customerId) ?>" class="btn btn-edit">Edit Customer</a>
                        <a href="customers_delete.php?id=<?= e((string) $customerId) ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this customer?');">Delete Customer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    </div>

    <div class="customer-drawer-backdrop" data-customer-drawer-close></div>
    <aside class="customer-detail-drawer" aria-hidden="true" aria-label="Customer details">
        <div class="customer-drawer-header">
            <div>
                <span class="customer-drawer-kicker">Customer Details</span>
                <strong>Profile & Contracts</strong>
            </div>
            <button type="button" class="customer-drawer-close" data-customer-drawer-close aria-label="Close customer details">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="customer-drawer-body" id="customerDrawerBody"></div>
    </aside>


    <?php if ($totalPages > 1): ?>
        <?php
        $paginationQuery = array_filter([
            'search' => $filters['search'],
            'status' => $filters['status'],
            'assignment_type' => $filters['assignment_type'],
            'rental_status' => $filters['rental_status'],
            'expiry' => $filters['expiry'],
            'month' => $selectedMonthValue,
        ], static fn(string $value): bool => $value !== '');
        ?>
        <nav class="monthly-pagination fleet-pagination mt-3" aria-label="Customer pagination">
            <ul class="pagination justify-content-end mb-0 flex-wrap">
                <?php if ($page > 1): ?>
                    <?php $paginationQuery['page'] = (string) ($page - 1); ?>
                    <li class="page-item">
                        <a class="page-link" href="customers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyCustomersResultsSection">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $paginationStart = max(1, $page - 2);
                $paginationEnd = min($totalPages, $page + 2);
                ?>

                <?php if ($paginationStart > 1): ?>
                    <?php $paginationQuery['page'] = '1'; ?>
                    <li class="page-item"><a class="page-link" href="customers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyCustomersResultsSection">1</a></li>
                    <?php if ($paginationStart > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $paginationStart; $i <= $paginationEnd; $i++): ?>
                    <?php $paginationQuery['page'] = (string) $i; ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="customers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyCustomersResultsSection">
                            <?= e((string) $i) ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <?php $paginationQuery['page'] = (string) $totalPages; ?>
                    <li class="page-item"><a class="page-link" href="customers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyCustomersResultsSection"><?= e((string) $totalPages) ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<script>
    (function () {
        const drawer = document.querySelector('.customer-detail-drawer');
        const backdrop = document.querySelector('.customer-drawer-backdrop');
        const drawerBody = document.getElementById('customerDrawerBody');

        if (!drawer || !backdrop || !drawerBody) {
            return;
        }

        function openDrawer(trigger) {
            const sourceId = trigger.getAttribute('data-customer-drawer');
            const source = sourceId ? document.getElementById(sourceId) : null;

            if (!source) {
                return;
            }

            drawerBody.innerHTML = source.innerHTML;
            drawer.classList.add('is-open');
            backdrop.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('customer-drawer-open');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('customer-drawer-open');
            drawerBody.innerHTML = '';
        }

        /*
         * Single delegated listener so a click on the View button
         * inside a trigger row fires exactly once.
         */
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-customer-drawer]');

            if (trigger) {
                event.preventDefault();
                openDrawer(trigger);
                return;
            }

            if (event.target.closest('[data-customer-drawer-close]')) {
                event.preventDefault();
                closeDrawer();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
                return;
            }

            if (
                (event.key === 'Enter' || event.key === ' ')
                && document.activeElement
                && document.activeElement.classList.contains('customer-drawer-trigger')
            ) {
                event.preventDefault();
                openDrawer(document.activeElement);
            }
        });
    })();
</script>

<?php
require __DIR__ . '/../includes/footer.php';
?>
