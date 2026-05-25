<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Bookings';
$pageSummary = 'Create, update, and monitor fleet trip bookings with flexible car and driver entries.';
$pageActions = '<a class="btn btn-accent" href="create.php">Add Booking</a>';
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
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(b.guest_company_name LIKE ? OR c.car_type LIKE ? OR c.plate_no LIKE ? OR b.custom_car_name LIKE ? OR b.operator_name LIKE ? OR o.full_name LIKE ? OR d.full_name LIKE ? OR b.custom_driver_name LIKE ? OR b.even_odd LIKE ? OR b.remark LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'ssssssssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'b.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    if ($filters['date_from'] !== '') {
        $where[] = 'b.start_date >= ?';
        $types .= 's';
        $params[] = $filters['date_from'];
    }

    if ($filters['date_to'] !== '') {
        $where[] = 'b.end_date <= ?';
        $types .= 's';
        $params[] = $filters['date_to'];
    }

    $sql = "SELECT
                b.id,
                b.guest_company_name,
                b.operator_id,
                b.operator_name,
                b.even_odd,
                b.custom_car_name,
                b.custom_driver_name,
                b.start_date,
                b.end_date,
                b.status,
                b.remark,
                c.car_type,
                c.plate_no,
                d.full_name AS driver_name,
                o.full_name AS operator_full_name
            FROM bookings AS b
            LEFT JOIN cars AS c ON c.id = b.car_id
            LEFT JOIN drivers AS d ON d.id = b.driver_id
            LEFT JOIN operators AS o ON o.id = b.operator_id";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY b.start_date DESC, b.id DESC';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {
        if ($params !== []) {
            bind_statement_params($statement, $types, $params);
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
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'Confirm' THEN 1 ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
     FROM bookings"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Bookings</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All saved trip records</small>
    </div>
    <div class="card-shell overview-card">
        <span>Pending</span>
        <strong><?= e((string) ($stats['pending'] ?? 0)) ?></strong>
        <small>Waiting for confirmation</small>
    </div>
    <div class="card-shell overview-card">
        <span>Confirmed</span>
        <strong><?= e((string) ($stats['confirmed'] ?? 0)) ?></strong>
        <small>Ready to operate</small>
    </div>
    <div class="card-shell overview-card">
        <span>Completed</span>
        <strong><?= e((string) ($stats['completed'] ?? 0)) ?></strong>
        <small>Trips completed</small>
    </div>
    <div class="card-shell overview-card">
        <span>Cancelled</span>
        <strong><?= e((string) (($stats['total'] ?? 0) - ($stats['pending'] ?? 0) - ($stats['confirmed'] ?? 0) - ($stats['completed'] ?? 0))) ?></strong>
        <small>Trips cancelled</small>
</section>

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Search Bookings</h2>
            <p>Filter by guest, car, driver, operator, status, or date.</p>
        </div>
    </div>

    <form method="get" class="filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Guest, car, plate, operator, driver">
        </div>
        <div>
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (booking_statuses() as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e($status) ?></option>
                <?php endforeach; ?>
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
            <h2>Booking List</h2>
            <p>Each booking wraps neatly to fit your screen without horizontal scrolling.</p>
        </div>
        <button type="button" class="btn btn-shell btn-sm" data-fullscreen-target="bookingsTablePanel">Fullscreen Table</button>
    </div>

    <div class="table-panel" id="bookingsTablePanel">
        <div class="table-full-content">
            <table class="table data-table align-middle" style="width: 100%; white-space: normal;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Guest / Company</th>
                        <th style="width: 15%;">Car</th>
                        <th style="width: 10%;">Operator</th>
                        <th style="width: 5%;">E/O</th>
                        <th style="width: 10%;">Dates</th>
                        <th style="width: 15%;">Driver</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 12%;">Remark</th>
                        <th style="width: 8%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings === []): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted-soft">No bookings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $guestName = $booking['guest_company_name'];
                            $operatorDisplay = booking_operator_display($booking);
                            $driverDisplay = booking_driver_display($booking);
                            $remarkText = $booking['remark'] ?: '-';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-wrap" style="line-height: 1.3; font-size: 1.05rem;">
                                        <?= e($guestName) ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="d-flex flex-column align-items-start">
                                        <?php if (trim((string) ($booking['custom_car_name'] ?? '')) !== ''): ?>
                                            <span class="table-pill car-pill text-wrap text-start"><?= e($booking['custom_car_name']) ?></span>
                                        <?php else: ?>
                                            <span class="table-pill car-pill text-wrap text-start"><?= e($booking['car_type'] ?? '-') ?></span>
                                            <?php if (trim((string) ($booking['plate_no'] ?? '')) !== ''): ?>
                                                <span class="soft-note mt-1" style="padding-left: 10px; font-size: 0.85em;">
                                                    <?= e($booking['plate_no']) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="table-pill operator-pill text-wrap text-start" style="font-size: 0.85em;"><?= e($operatorDisplay) ?></span>
                                </td>
                                
                                <td>
                                    <span style="color: var(--muted); font-size: 0.9em;"><?= e($booking['even_odd'] ?: '-') ?></span>
                                </td>
                                
                                <td>
                                    <div style="font-weight: 700; color: var(--cocoa); font-size: 0.95em; line-height: 1.2;">
                                        <?= e(format_display_date($booking['start_date'])) ?>
                                    </div>
                                    <div class="soft-note" style="font-size: 0.85em; margin-top: 2px;">
                                        <?= e(format_display_date($booking['end_date'])) ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="table-pill driver-pill text-wrap text-start" style="font-size: 0.9em; line-height: 1.2;">
                                        <?= e($driverDisplay) ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span>
                                </td>
                                
                                <td>
                                    <div class="text-wrap text-muted" style="font-size: 0.85em; line-height: 1.4;">
                                        <?= e($remarkText) ?>
                                    </div>
                                </td>
                                
                                <td class="text-center">
                                    <div class="d-flex gap-1 flex-wrap justify-content-center">
                                        <a class="btn btn-sm btn-shell" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;" href="edit.php?id=<?= e((string) $booking['id']) ?>">Edit</a>
                                        <form method="post" action="delete.php?id=<?= e((string) $booking['id']) ?>" onsubmit="return confirm('Delete this booking?');" style="margin: 0;">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>