<?php

declare(strict_types=1);

$driver = $driver ?? [];
$submitLabel = $submitLabel ?? 'Save Driver';
$formTitle = $formTitle ?? 'Driver Form';
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Keep driver information ready for fast trip assignments.</p>
            </div>
            <a class="btn btn-shell" href="drivers.php">Back to Drivers</a>
        </div>

        <div class="form-assignment-note mb-4">
            <strong>Booking dates stay in Bookings.</strong>
            Guest/company, start date, and end date are linked from booking records automatically, so update those details from <a href="bookings.php">Bookings</a> instead of the driver master record.
        </div>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="full_name" class="form-label">Driver Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($driver['full_name'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= e($driver['phone_number'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label for="driver_status" class="form-label">Status</label>
                <select class="form-select" id="driver_status" name="driver_status" required>
                    <?php foreach (driver_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($driver['driver_status'] ?? 'Available', $status) ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea class="form-control" id="note" name="note" rows="4" placeholder="Optional note about schedule, route, or leave"><?= e($driver['note'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-accent px-4"><?= e($submitLabel) ?></button>
                <a class="btn btn-shell" href="drivers.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
