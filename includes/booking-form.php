<?php
declare(strict_types=1);

$booking = $booking ?? [];
$submitLabel = $submitLabel ?? 'Save Booking';
$formTitle = $formTitle ?? 'Booking Form';
$cars = $cars ?? [];
$drivers = $drivers ?? [];
$operators = $operators ?? [];
$activeBookings = $activeBookings ?? []; // Passed from create.php

// Show all cars EXCEPT ones broken down in maintenance
$availableCars = array_filter($cars, function($c) {
    return strtolower($c['availability_status'] ?? '') !== 'maintenance';
});

// Show all drivers EXCEPT ones on leave or inactive
$availableDrivers = array_filter($drivers, function($d) {
    return !in_array(strtolower($d['driver_status'] ?? ''), ['leave', 'inactive']);
});
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Select dates first to see available cars and drivers.</p>
            </div>
            <a class="btn btn-shell" href="bookings.php">Back to Bookings</a>
        </div>

        <?php if ($operators === [] || $cars === [] || $drivers === []): ?>
            <div class="alert alert-warning border-0 m-0">
                <strong>Missing Master Data.</strong>
                Please make sure you have added at least one Car, one Driver, and one Operator before creating a booking.
            </div>
        <?php else: ?>
            <form method="post" class="row g-3">
                <div class="col-12">
                    <label for="guest_company_name" class="form-label">Guest / Company Name</label>
                    <input type="text" class="form-control" id="guest_company_name" name="guest_company_name" value="<?= e($booking['guest_company_name'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <div class="p-3 border rounded bg-light-subtle h-100">
                        <label class="form-label fw-bold mb-3 text-accent">1. Select Trip Dates</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="start_date" class="form-label small">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($booking['start_date'] ?? '') ?>" required>
                            </div>
                            <div class="col-6">
                                <label for="end_date" class="form-label small">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($booking['end_date'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 border rounded bg-light-subtle h-100">
                        <label class="form-label fw-bold mb-3 text-accent">2. Assign Resources</label>
                        <div class="mb-2">
                            <label for="car_id" class="form-label small">Fleet Car</label>
                            <select class="form-select" id="car_id" name="car_id" required>
                                <option value="">Select an available car</option>
                                <?php foreach ($availableCars as $car): ?>
                                    <?php $carLabel = trim($car['car_type'] . ' | ' . $car['plate_no'] . ' | ' . $car['model_name']); ?>
                                    <option value="<?= e((string)$car['id']) ?>" data-original-text="<?= e($carLabel) ?>" <?= selected($booking['car_id'] ?? '', $car['id']) ?>>
                                        <?= e($carLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="driver_id" class="form-label small">Fleet Driver</label>
                            <select class="form-select" id="driver_id" name="driver_id" required>
                                <option value="">Select an available driver</option>
                                <?php foreach ($availableDrivers as $driver): ?>
                                    <?php
                                    $driverParts = array_values(array_filter([
                                        trim((string) ($driver['full_name'] ?? '')),
                                        trim((string) ($driver['phone_number'] ?? '')),
                                    ], static fn (?string $v): bool => $v !== null && $v !== ''));
                                    $driverLabel = implode(' | ', $driverParts);
                                    ?>
                                    <option value="<?= e((string)$driver['id']) ?>" data-original-text="<?= e($driverLabel) ?>" <?= selected($booking['driver_id'] ?? '', $driver['id']) ?>>
                                        <?= e($driverLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="operator_id" class="form-label">Operator</label>
                    <select class="form-select" id="operator_id" name="operator_id" required>
                        <option value="">Select an operator</option>
                        <?php foreach ($operators as $operator): ?>
                            <option value="<?= e((string) $operator['id']) ?>" <?= selected($booking['operator_id'] ?? '', $operator['id']) ?>>
                                <?= e($operator['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="even_odd" class="form-label">Even / Odd</label>
                    <select class="form-select" id="even_odd" name="even_odd">
                        <option value="">Select if needed</option>
                        <?php foreach (booking_even_odd_options() as $option): ?>
                            <option value="<?= e($option) ?>" <?= selected($booking['even_odd'] ?? '', $option) ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <?php foreach (booking_statuses() as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected($booking['status'] ?? 'Confirm', $status) ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="remark" class="form-label">Remark</label>
                    <textarea class="form-control" id="remark" name="remark" rows="4" placeholder="Optional remark, pickup note, or special request"><?= e($booking['remark'] ?? '') ?></textarea>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-accent px-4"><?= e($submitLabel) ?></button>
                    <a class="btn btn-shell" href="bookings.php">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const activeBookings = <?= json_encode($activeBookings) ?>;
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const carSelect = document.getElementById('car_id');
    const driverSelect = document.getElementById('driver_id');

    // Function to check if two date ranges overlap
    function checkOverlap(start1, end1, start2, end2) {
        if (!start1 || !end1 || !start2 || !end2) return false;
        return (start1 <= end2) && (end1 >= start2);
    }

    function filterResources() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Reset all options to available first
        [...carSelect.options, ...driverSelect.options].forEach(opt => {
            if (opt.value !== '') {
                opt.disabled = false;
                opt.text = opt.getAttribute('data-original-text');
            }
        });

        // If dates are empty, stop here
        if (!startDate || !endDate) return;

        // Loop through all active bookings from the database
        activeBookings.forEach(booking => {
            if (checkOverlap(startDate, endDate, booking.start_date, booking.end_date)) {
                
                // Disable overlapping Car
                if (booking.car_id) {
                    const carOpt = carSelect.querySelector(`option[value="${booking.car_id}"]`);
                    if (carOpt) {
                        carOpt.disabled = true;
                        carOpt.text = carOpt.getAttribute('data-original-text') + ' (Booked)';
                    }
                }

                // Disable overlapping Driver
                if (booking.driver_id) {
                    const drvOpt = driverSelect.querySelector(`option[value="${booking.driver_id}"]`);
                    if (drvOpt) {
                        drvOpt.disabled = true;
                        drvOpt.text = drvOpt.getAttribute('data-original-text') + ' (Booked)';
                    }
                }
            }
        });

        // If the user's currently selected item just became disabled, reset the dropdown
        if (carSelect.options[carSelect.selectedIndex]?.disabled) carSelect.value = '';
        if (driverSelect.options[driverSelect.selectedIndex]?.disabled) driverSelect.value = '';
    }

    // Listen for date changes
    startDateInput.addEventListener('change', filterResources);
    endDateInput.addEventListener('change', filterResources);

    // Run once on load in case dates are already filled
    filterResources();
});
</script>