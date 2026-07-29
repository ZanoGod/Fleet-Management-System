<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';

$activePage = 'monthly-reports';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Reports';
$pageSummary = 'Review contract revenue, assignment mix, expiry risk, and export filtered records.';
$pageActions = '<a class="btn btn-accent" href="contract_create.php">Add Contract</a>';
$pageStyles = ['assets/css/reports.css', 'assets/css/monthly-contracts.css', 'assets/css/monthly-contract-report.css'];
$filters = monthly_contract_filters($_GET);
$flash = get_flash();
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
    static fn(string $value): bool => $value !== ''
);
$exportQuery = http_build_query($filterQuery);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<section class="overview-grid">
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
</section>

<section class="card-shell section-card mb-4" id="monthlyContractReportFilters">
    <div class="section-title">
        <div>
            <h2>Report Filters</h2>
            <p>Use filters before exporting the monthly contract report.</p>
        </div>
    </div>

    <form method="get" action="contract-reports.php#monthlyContractReportResults" class="monthly-contract-filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>"
                placeholder="Contract, customer, vehicle, driver, operator">
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

        <div>
            <label for="date_from" class="form-label">Start From</label>
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= e($filters['date_from']) ?>">
        </div>

        <div>
            <label for="date_to" class="form-label">End To</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e($filters['date_to']) ?>">
        </div>

        <div class="d-grid align-self-end">
            <button type="submit" class="btn btn-shell">Search</button>
        </div>
    </form>
</section>

<section class="card-shell section-card" id="monthlyContractReportResults">
    <div class="report-header-layout mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2>Report Results</h2>
                <p class="text-muted m-0">
                    <?= number_format(count($contracts)) ?>
                    <?= count($contracts) === 1 ? 'record' : 'records' ?> found.
                </p>
            </div>
            <button type="button" class="btn btn-shell btn-sm" data-fullscreen-target="reportTable">Fullscreen
                Table</button>
            <div class="report-actions d-flex gap-2">
                <a id="exportExcelBtn" href="monthly_contracts_export_excel.php<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>"
                    class="btn btn-accent-report btn-excel">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    <span>Excel</span>
                </a>
                <a id="exportPdfBtn" href="monthly_contracts_export_pdf.php<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>"
                    class="btn btn-accent-report btn-pdf">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    <span>PDF</span>
                </a>
            </div>
        </div>

        <!-- Horizontal Column Selector Shell -->
        <div class="column-selector-card p-3 rounded shadow-sm border">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <h6 class="fw-bold m-0 text-dark">Select Columns to Display</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="selectAllCols">Select All</button>
                    <span class="text-muted">|</span>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="deselectAllCols">Reset Default</button>
                </div>
            </div>

            <div class="horizontal-column-grid">
                <!-- Default Checked Columns -->
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="customer" checked> Customer</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="assignment" checked> Assignment</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="vehicle" checked> Vehicle</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="driver" checked> Driver</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="operator" checked> Operator</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="dates" checked> Dates</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="rate" checked> Rate</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="rental_status" checked> Rental Status  </label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="status" checked> Status</label>

                <!-- Extended Database Columns -->
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="contract"> Contract No.</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="guest_name"> Guest Name</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="contact_person"> Contact Person</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="customer_phone"> Phone</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="customer_email"> Email</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="customer_address"> Address</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="even_odd"> Even/Odd</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="operation_sheet"> Op Sheet</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="rental_currency"> Currency</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="off_day"> Off Day</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="service_hour"> Service Hours</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="insurance_company"> Insurance Co.</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="insurance_price"> Insurance Fee</label>
                <label class="col-item"><input type="checkbox" class="column-toggle" data-column="note"> Note</label>
            </div>
        </div>
    </div>

    <div class="report-table-wrapper">
        <table class="table data-table report-table align-middle" id="reportTable">
            <thead>
                <tr>
                    <th class="col-contract">Contract</th>
                    <th class="col-customer">Customer</th>
                    <th class="col-assignment">Assignment</th>
                    <th class="col-vehicle">Vehicle</th>
                    <th class="col-driver">Driver</th>
                    <th class="col-operator">Operator</th>
                    <th class="col-dates">Dates</th>
                    <th class="col-rate">Rate</th>
                    <th class="col-rental_status">Rental Status</th>
                    <th class="col-status">Status</th>

                    <!-- Dynamic Header Columns -->
                    <th class="col-guest_name">Guest Name</th>
                    <th class="col-contact_person">Contact Person</th>
                    <th class="col-customer_phone">Phone</th>
                    <th class="col-customer_email">Email</th>
                    <th class="col-customer_address">Address</th>
                    <th class="col-even_odd">Even/Odd</th>
                    <th class="col-operation_sheet">Op Sheet</th>
                    <th class="col-rental_currency">Currency</th>
                    <th class="col-off_day">Off Day</th>
                    <th class="col-service_hour">Service Hours</th>
                    <th class="col-insurance_company">Insurance Co.</th>
                    <th class="col-insurance_price">Insurance Fee</th>
                    <th class="col-note">Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($contracts === []): ?>
                    <tr>
                        <td colspan="24" class="text-center py-5 text-muted-soft">No report records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td class="col-contract">
                                <div class="report-guest"><?= e((string) $contract['contract_no']) ?></div>
                            </td>
                            <td class="col-customer">
                                <div class="report-guest"><?= e((string) $contract['customer_name']) ?></div>
                            </td>
                            <td class="col-assignment">
                                <span class="resource-pill <?= e(monthly_contract_assignment_badge_class((string) $contract['assignment_type'])) ?>">
                                    <?= e((string) $contract['assignment_type']) ?>
                                </span>
                            </td>
                            <td class="col-vehicle">
                                <span class="resource-pill"><?= e(monthly_contract_vehicle_display($contract)) ?></span>
                            </td>
                            <td class="col-driver"><?= e(monthly_contract_driver_display($contract)) ?></td>
                            <td class="col-operator"><?= e(monthly_contract_operator_display($contract)) ?></td>
                            <td class="col-dates">
                                <div class="report-date-primary"><?= e(format_display_date($contract['start_date'])) ?></div>
                                <div class="report-date-secondary"><?= e(format_display_date($contract['end_date'])) ?></div>
                            </td>
                            <td class="col-rate">
                                <div class="report-rate"><?= e(monthly_contract_money($contract['monthly_rate'])) ?></div>
                            </td>
                            <td class="col-rental_status">
                                <span class="resource-pill <?= e(monthly_contract_rental_badge_class((string)$contract['rental_status'])) ?>">
                                    <?= e((string)$contract['rental_status']) ?>
                                </span>
                            </td>
                            <td class="col-status">
                                <span class="status-pill <?= e(monthly_contract_status_badge_class((string)$contract['status'])) ?>">
                                    <?= e((string)$contract['status']) ?>
                                </span>
                            </td>

                            <!-- Dynamic Data Cells -->
                            <td class="col-guest_name"><?= e((string) ($contract['guest_name'] ?? '-')) ?></td>
                            <td class="col-contact_person"><?= e((string) ($contract['contact_person'] ?? '-')) ?></td>
                            <td class="col-customer_phone"><?= e((string) ($contract['customer_phone'] ?? '-')) ?></td>
                            <td class="col-customer_email"><?= e((string) ($contract['customer_email'] ?? '-')) ?></td>
                            <td class="col-customer_address"><?= e((string) ($contract['customer_address'] ?? '-')) ?></td>
                            <td class="col-even_odd"><?= e((string) ($contract['even_odd'] ?? '-')) ?></td>
                            <td class="col-operation_sheet"><?= e((string) ($contract['operation_sheet'] ?? '-')) ?></td>
                            <td class="col-rental_currency"><?= e((string) ($contract['rental_currency'] ?? '-')) ?></td>
                            <td class="col-off_day"><?= e((string) ($contract['off_day'] ?? '-')) ?></td>
                            <td class="col-service_hour"><?= e((string) ($contract['service_hour'] ?? '-')) ?></td>
                            <td class="col-insurance_company"><?= e((string) ($contract['insurance_company'] ?? '-')) ?></td>
                            <td class="col-insurance_price"><?= e(monthly_contract_money($contract['insurance_price'] ?? 0)) ?></td>
                            <td class="col-note"><?= e((string) ($contract['note'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


<script>
    function updateColumnsVisibility() {
        const visibleCols = [];
        document.querySelectorAll(".column-toggle").forEach(box => {
            const column = box.dataset.column;
            const isChecked = box.checked;

            document.querySelectorAll(".col-" + column).forEach(cell => {
                cell.style.display = isChecked ? "" : "none";
            });

            if (isChecked) {
                visibleCols.push(column);
            }
        });

        // Sync visibility to export links
        const excelBtn = document.getElementById('exportExcelBtn');
        const pdfBtn = document.getElementById('exportPdfBtn');

        [excelBtn, pdfBtn].forEach(btn => {
            if (!btn) return;
            const url = new URL(btn.href, window.location.origin);
            url.searchParams.set('cols', visibleCols.join(','));
            btn.href = url.toString();
        });

        saveColumns();
    }

    function saveColumns() {
        const state = {};
        document.querySelectorAll(".column-toggle").forEach(box => {
            state[box.dataset.column] = box.checked;
        });
        localStorage.setItem("monthlyReportColumns", JSON.stringify(state));
    }

    document.querySelectorAll(".column-toggle").forEach(box => {
        box.addEventListener("change", updateColumnsVisibility);
    });

    document.getElementById("selectAllCols")?.addEventListener("click", () => {
        document.querySelectorAll(".column-toggle").forEach(box => box.checked = true);
        updateColumnsVisibility();
    });

    document.getElementById("deselectAllCols")?.addEventListener("click", () => {
        // Updated default columns list (without 'contract')
        const defaultCols = [
            'customer',
            'assignment',
            'vehicle',
            'driver',
            'operator',
            'dates',
            'rate',
            'rental_status',
            'status'
        ];

        document.querySelectorAll(".column-toggle").forEach(box => {
            box.checked = defaultCols.includes(box.dataset.column);
        });

        updateColumnsVisibility();
    });

    window.addEventListener("load", () => {
        let saved = localStorage.getItem("monthlyReportColumns");
        if (saved) {
            try {
                saved = JSON.parse(saved);
                document.querySelectorAll(".column-toggle").forEach(box => {
                    if (saved[box.dataset.column] !== undefined) {
                        box.checked = saved[box.dataset.column];
                    }
                });
            } catch (e) {
                console.error(e);
            }
        }
        updateColumnsVisibility();
    });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>