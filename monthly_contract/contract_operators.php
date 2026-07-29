<?php

declare(strict_types=1);


require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'monthly-operators';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Operators';
$pageSummary = 'View the operator directory while staying inside the monthly contract module.';
$pageActions = '<a class="btn btn-accent" href="contract_op_create.php">Add Operator</a>';
$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

$operators = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(full_name LIKE ? OR phone_number LIKE ? OR note LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'sss';
        array_push($params, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'operator_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    $sql = 'SELECT id, full_name, phone_number, operator_status, note FROM operators';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY updated_at DESC, created_at DESC';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {
        if ($params !== []) {
            bind_statement_params($statement, $types, $params);
        }

        $statement->execute();
        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $operators[] = $row;
        }

        $statement->close();
    }

    $statsResult = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(operator_status = 'Active') AS active,
            SUM(operator_status = 'Inactive') AS inactive
         FROM operators"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>

<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Operators</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All operators in the directory</small>
    </div>
    <div class="card-shell overview-card">
        <span>Active</span>
        <strong><?= e((string) ($stats['active'] ?? 0)) ?></strong>
        <small>Available for contract support</small>
    </div>
    <div class="card-shell overview-card">
        <span>Inactive</span>
        <strong><?= e((string) ($stats['inactive'] ?? 0)) ?></strong>
        <small>Not active right now</small>
    </div>
</section>

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Search Operators</h2>
            <p>Filter by operator name, phone number, note, or status.</p>
        </div>
    </div>

    <form method="get" action="contract_operators.php" class="filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Operator name, phone, note">
        </div>
        <div>
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (operator_statuses() as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-grid align-self-end">
            <button type="submit" class="btn btn-shell">Filter</button>
        </div>
    </form>
</section>

<section class="card-shell section-card">
    <div class="section-title">
        <div>
            <h2>Operator List</h2>
            <p><?= e((string) count($operators)) ?> operator records found.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>Operator Name</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($operators === []): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted-soft">No operators found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($operators as $operator): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($operator['full_name']) ?></td>
                            <td><?= e($operator['phone_number'] ?: '-') ?></td>
                            <td>
                                <span class="resource-pill <?= e(operator_status_class($operator['operator_status'])) ?>">
                                    <?= e($operator['operator_status']) ?>
                                </span>
                            </td>
                            <td class="note-cell"><?= e($operator['note'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
