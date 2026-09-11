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

    $sql .= ' ORDER BY b.updated_at DESC';

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

    <form method="get" action="bookings.php#bookingsResultsSection" class="filter-grid"
        data-preserve-scroll="bookingsFiltersSection">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>"
                placeholder="Guest, car, plate, operator, driver">
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
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= e($filters['date_from']) ?>">
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
        <button type="button" class="btn btn-shell btn-sm" data-fullscreen-target="bookingsTablePanel">Fullscreen
            Table</button>
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
                                    <span
                                        class="table-pill operator-pill booking-operator-pill"><?= e($operatorDisplay) ?></span>
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
                                    <span
                                        class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span>
                                </td>

                                <td>
                                    <div class="booking-remark">
                                        <?= e($remarkText) ?>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="table-actions table-actions-compact">
                                        <a class="btn btn-sm btn-shell"
                                            href="edit.php?id=<?= e((string) $booking['id']) ?>">Edit</a>
                                        <form method="post" action="delete" class="delete-booking-form"
                                            data-booking-name="<?= e($guestName) ?>">
                                            <input type="hidden" name="id" value="<?= e((string) $booking['id']) ?>">
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-booking-btn">
                                                <span style="pointer-events: none;">Delete</span>
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
    </div>
</section>


<div class="delete-modal" id="deleteBookingModal" aria-hidden="true" role="dialog" aria-modal="true"
    aria-labelledby="deleteModalTitle">
    <div class="delete-modal-backdrop"></div>
    <div class="delete-modal-dialog">
        <div class="delete-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M3 6h18"></path>
                <path d="M8 6V4h8v2"></path>
                <path d="M19 6l-1 14H6L5 6"></path>
                <path d="M10 11v5"></path>
                <path d="M14 11v5"></path>
            </svg>
        </div>
        <div class="delete-modal-content">
            <h3 id="deleteModalTitle"> Delete booking? </h3>
            <p> Are you sure you want to delete <strong id="deleteBookingName">this booking</strong>? </p>
            <div class="delete-modal-warning">
                <span class="delete-warning-icon">!</span>
                <span> This action cannot be undone. </span>
            </div>
        </div>
        <div class="delete-modal-actions">
            <button type="button" class="delete-modal-cancel" id="deleteModalCancel"> Cancel </button>
            <button type="button" class="delete-modal-confirm" id="deleteModalConfirm">
                <span class="delete-confirm-text"> Delete Booking </span>
                <span class="delete-confirm-loading" aria-hidden="true">
                    <span class="delete-spinner"></span> Deleting...
                </span>
            </button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('deleteBookingModal');
        const modalName = document.getElementById('deleteBookingName');
        const cancelButton = document.getElementById('deleteModalCancel');
        const confirmButton = document.getElementById('deleteModalConfirm');
        const backdrop = modal ? modal.querySelector('.delete-modal-backdrop') : null;
        let activeForm = null;
        let activeRow = null;

        // CRITICAL FIX: Relocate the modal to the body root immediately upon page load
        // This breaks the modal out of any parent CSS transform/overflow contexts
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        function openDeleteModal(form) {
            activeForm = form;
            activeRow = form.closest('tr');
            const bookingName = form.dataset.bookingName || 'this booking';

            if (modalName) modalName.textContent = bookingName;

            if (modal) {
                modal.style.display = 'flex';
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }

            document.body.classList.add('delete-modal-open');
            if (cancelButton) {
                setTimeout(() => cancelButton.focus({
                    preventScroll: true
                }), 50);
            }
        }

        function closeDeleteModal() {
            if (confirmButton && confirmButton.classList.contains('is-loading')) return;

            if (modal) {
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.body.classList.remove('delete-modal-open');

            if (confirmButton) {
                confirmButton.classList.remove('is-loading');
                confirmButton.disabled = false;
            }

            activeForm = null;
            activeRow = null;
        }


        // Attach delegated click listener for delete buttons
        document.addEventListener('click', function(event) {
            const deleteBtn = event.target.closest('.delete-booking-btn');
            if (deleteBtn) {
                event.preventDefault();
                event.stopPropagation();
                const form = deleteBtn.closest('.delete-booking-form');
                if (form) openDeleteModal(form);
            }
        });

        if (cancelButton) cancelButton.addEventListener('click', closeDeleteModal);
        if (backdrop) backdrop.addEventListener('click', closeDeleteModal);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
                closeDeleteModal();
            }
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', function(e) {
                e.preventDefault();
                if (!activeForm) return;

                confirmButton.classList.add('is-loading');
                confirmButton.disabled = true;

                if (activeRow) {
                    activeRow.classList.add('booking-row-deleting');
                }

                // Append a hidden form directly to body to guarantee a clean POST request
                const tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = activeForm.getAttribute('action') || 'delete.php';
                tempForm.style.display = 'none';

                // Copy all input values (including hidden ID field)
                const inputs = activeForm.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    const clone = input.cloneNode(true);
                    tempForm.appendChild(clone);
                });

                document.body.appendChild(tempForm);

                setTimeout(function() {
                    tempForm.submit();
                }, 150);
            });
        }
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>