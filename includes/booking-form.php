<?php

declare(strict_types=1);

$booking = $booking ?? [];
$submitLabel = $submitLabel ?? 'Save Booking';
$formTitle = $formTitle ?? 'Booking Form';
$cars = $cars ?? [];
$drivers = $drivers ?? [];
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Assign the correct vehicle and driver for this trip booking.</p>
            </div>
            <a class="btn btn-shell" href="bookings.php">Back to Bookings</a>
        </div>

        <?php if ($cars === [] || $drivers === []): ?>
            <div class="alert alert-warning border-0 m-0">
                <strong>Bookings need master data first.</strong>
                Please add at least one car and one driver before creating a booking.
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-shell" href="cars.php">Manage Cars</a>
                    <a class="btn btn-shell" href="drivers.php">Manage Drivers</a>
                </div>
            </div>
        <?php else: ?>
            <form method="post" class="row g-3">
                <div class="col-12">
                    <label for="guest_company_name" class="form-label">Guest / Company Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="guest_company_name"
                        name="guest_company_name"
                        value="<?= e($booking['guest_company_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="car_id" class="form-label">Assigned Car</label>
                    <select class="form-select" id="car_id" name="car_id" required>
                        <option value="">Select a car</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= e((string) $car['id']) ?>" <?= selected($booking['car_id'] ?? '', $car['id']) ?>>
                                <?= e($car['car_type'] . ' | ' . $car['plate_no'] . ' | ' . $car['model_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="driver_id" class="form-label">Assigned Driver</label>
                    <select class="form-select" id="driver_id" name="driver_id" required>
                        <option value="">Select a driver</option>
                        <?php foreach ($drivers as $driver): ?>
                            <option value="<?= e((string) $driver['id']) ?>" <?= selected($booking['driver_id'] ?? '', $driver['id']) ?>>
                                <?= e($driver['full_name'] . ' | ' . $driver['license_no']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="operator_name" class="form-label">Operator</label>
                    <input
                        type="text"
                        class="form-control"
                        id="operator_name"
                        name="operator_name"
                        value="<?= e($booking['operator_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input
                        type="date"
                        class="form-control"
                        id="start_date"
                        name="start_date"
                        value="<?= e($booking['start_date'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input
                        type="date"
                        class="form-control"
                        id="end_date"
                        name="end_date"
                        value="<?= e($booking['end_date'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <?php foreach (booking_statuses() as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected($booking['status'] ?? 'Pending', $status) ?>>
                                <?= e($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="remark" class="form-label">Remark</label>
                    <textarea
                        class="form-control"
                        id="remark"
                        name="remark"
                        rows="4"
                        placeholder="Optional remark, pickup note, or special request"
                    ><?= e($booking['remark'] ?? '') ?></textarea>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-accent px-4"><?= e($submitLabel) ?></button>
                    <a class="btn btn-shell" href="bookings.php">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
