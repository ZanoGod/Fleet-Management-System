<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'bookings';
$pageTitle = 'Bookings';
$pageSummary = 'Create, update, and monitor fleet trip bookings with flexible car and driver entries.';
$pageActions = '<a class="btn btn-accent" href="create.php">Add Booking</a>';
$pageStyles = ['assets/css/booking.css'];
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
    'cancelled' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(b.guest_company_name LIKE ? OR c.car_type LIKE ? OR c.plate_no LIKE ? OR c2.car_type LIKE ? OR c2.plate_no LIKE ? OR b.custom_car_name LIKE ? OR b.operator_name LIKE ? OR o.full_name LIKE ? OR d.full_name LIKE ? OR b.custom_driver_name LIKE ? OR b.even_odd LIKE ? OR b.remark LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'ssssssssssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue);
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
                b.secondary_car_id,
                b.start_date,
                b.end_date,
                b.status,
                b.remark,
                c.car_type,
                c.plate_no,
                c2.car_type AS secondary_car_type,
                c2.plate_no AS secondary_plate_no,
                d.full_name AS driver_name,
                o.full_name AS operator_full_name
            FROM bookings AS b
            LEFT JOIN cars AS c ON c.id = b.car_id
            LEFT JOIN cars AS c2 ON c2.id = b.secondary_car_id
            LEFT JOIN drivers AS d ON d.id = b.driver_id
            LEFT JOIN operators AS o ON o.id = b.operator_id";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

<<<<<<< HEAD
    $sql .= ' ORDER BY b.updated_at DESC';
=======
   $sql .= ' ORDER BY b.updated_at DESC';
>>>>>>> d6f08dc5b311da7778b9a9a444510acd76ebbbf0

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
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
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
        <strong><?= e((string) ($stats['cancelled'] ?? 0)) ?></strong>
        <small>Trips cancelled</small>
    </div>
</section>

<section class="card-shell section-card mb-4" id="bookingsFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Bookings</h2>
            <p>Filter by guest, car, driver, operator, status, or date.</p>
        </div>
    </div>

    <form method="get" action="bookings.php#bookingsResultsSection" class="filter-grid" data-preserve-scroll="bookingsFiltersSection">
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

<section class="card-shell section-card" id="bookingsResultsSection">
    <div class="section-title">
        <div>
            <h2>Booking List</h2>
            <p>Each booking wraps neatly to fit your screen without horizontal scrolling.</p>
        </div>
        <button type="button" class="btn btn-shell btn-sm" data-fullscreen-target="bookingsTablePanel">Fullscreen Table</button>
    </div>

    <div class="table-panel" id="bookingsTablePanel">
        <div class="table-full-content">
            <table class="table data-table booking-table align-middle">
                <thead>
                    <tr>
                        <th>Guest / Company</th>
                        <th>Car</th>
                        <th>Operator</th>
                        <th>E/O</th>
                        <th>Dates</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th class="text-center">Action</th>
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
                                    <div class="booking-guest">
                                        <?= e($guestName) ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="booking-car">
                                        <?php foreach (booking_car_entries($booking) as $carLabel): ?>
                                            <span class="table-pill car-pill booking-car-pill"><?= e($carLabel) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="table-pill operator-pill booking-operator-pill"><?= e($operatorDisplay) ?></span>
                                </td>

                                <td>
                                    <span class="booking-even-odd"><?= e($booking['even_odd'] ?: '-') ?></span>
                                </td>

                                <td>
                                    <div class="booking-date-primary">
                                        <?= e(format_display_date($booking['start_date'])) ?>
                                    </div>
                                    <div class="soft-note booking-date-secondary">
                                        <?= e(format_display_date($booking['end_date'])) ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="table-pill driver-pill booking-driver-pill">
                                        <?= e($driverDisplay) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span>
                                </td>

                                <td>
                                    <div class="booking-remark">
                                        <?= e($remarkText) ?>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="table-actions table-actions-compact">
                                        <a class="btn btn-sm btn-shell" href="edit.php?id=<?= e((string) $booking['id']) ?>">Edit</a>
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
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>