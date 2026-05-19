<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Fleet Dashboard';
$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$bookings = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(guest_company_name LIKE ? OR car_type LIKE ? OR car_no LIKE ? OR operator_name LIKE ? OR driver_name LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'sssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    if ($filters['date_from'] !== '') {
        $where[] = 'start_date >= ?';
        $types .= 's';
        $params[] = $filters['date_from'];
    }

    if ($filters['date_to'] !== '') {
        $where[] = 'end_date <= ?';
        $types .= 's';
        $params[] = $filters['date_to'];
    }

    $sql = 'SELECT id, guest_company_name, car_type, car_no, operator_name, start_date, end_date, driver_name, status, remark
            FROM bookings';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY start_date DESC, id DESC';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {
        if ($params !== []) {
            $statement->bind_param($types, ...$params);
        }

        $statement->execute();
        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }

        $statement->close();
    }

    $statsResult = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(status = 'Pending') AS pending,
            SUM(status = 'Confirm') AS confirmed,
            SUM(status = 'Completed') AS completed
         FROM bookings"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($dbError !== null): ?>
    <div class="alert alert-warning shadow-sm">
        <h2 class="h5">Database connection not ready</h2>
        <p class="mb-2">Import the SQL file first, then update your MySQL login in <code>config/database.php</code> if needed.</p>
        <div class="small text-secondary"><?= e($dbError) ?></div>
    </div>
<?php endif; ?>

<section class="hero-panel shadow-sm mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <span class="eyebrow">Fleet Booking Control</span>
            <h1 class="display-6 mb-3">Manage guests, vehicles, operators, and drivers in one web-based system.</h1>
            <p class="lead text-secondary mb-0">This layout follows your sheet style, but the <strong>Even / Odd</strong> column has been removed as requested.</p>
        </div>
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <span>Total Bookings</span>
                        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <span>Pending</span>
                        <strong><?= e((string) ($stats['pending'] ?? 0)) ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <span>Confirmed</span>
                        <strong><?= e((string) ($stats['confirmed'] ?? 0)) ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <span>Completed</span>
                        <strong><?= e((string) ($stats['completed'] ?? 0)) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">Trip Booking List</h2>
                <p class="text-secondary mb-0">Search and manage fleet assignments quickly.</p>
            </div>
            <a class="btn btn-success" href="create.php">Add New Booking</a>
        </div>

        <form method="get" class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label for="search" class="form-label">Search</label>
                <input
                    type="text"
                    class="form-control"
                    id="search"
                    name="search"
                    value="<?= e($filters['search']) ?>"
                    placeholder="Guest, car, operator, driver"
                >
            </div>
            <div class="col-lg-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (booking_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label for="date_from" class="form-label">Start From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-lg-2">
                <label for="date_to" class="form-label">End To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-lg-1 d-grid">
                <button type="submit" class="btn btn-outline-success">Go</button>
            </div>
        </form>
    </div>
</section>

<section class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle booking-table mb-0">
            <thead>
                <tr>
                    <th>Guest / Company Name</th>
                    <th>Car Type</th>
                    <th>Car No</th>
                    <th>Operator</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Remark</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings === []): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-secondary">
                            No bookings found yet. Click <strong>Add New Booking</strong> to create your first record.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($booking['guest_company_name']) ?></td>
                            <td><span class="table-pill car-pill"><?= e($booking['car_type']) ?></span></td>
                            <td><span class="table-pill plate-pill"><?= e($booking['car_no']) ?></span></td>
                            <td><span class="table-pill operator-pill"><?= e($booking['operator_name']) ?></span></td>
                            <td><?= e(format_display_date($booking['start_date'])) ?></td>
                            <td><?= e(format_display_date($booking['end_date'])) ?></td>
                            <td><span class="table-pill driver-pill"><?= e($booking['driver_name']) ?></span></td>
                            <td>
                                <span class="status-pill <?= e(status_badge_class($booking['status'])) ?>">
                                    <?= e($booking['status']) ?>
                                </span>
                            </td>
                            <td class="remark-cell"><?= e($booking['remark'] ?: '-') ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-outline-success" href="edit.php?id=<?= e((string) $booking['id']) ?>">Edit</a>
                                    <form method="post" action="delete.php?id=<?= e((string) $booking['id']) ?>" onsubmit="return confirm('Delete this booking?');">
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
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
