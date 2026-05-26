<?php

declare(strict_types=1);

$car = $car ?? [];
$submitLabel = $submitLabel ?? 'Save Car';
$formTitle = $formTitle ?? 'Car Form';
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Maintain car and fleet master data for booking assignments.</p>
            </div>
            <a class="btn btn-shell" href="cars.php">Back to Cars</a>
        </div>

        <div class="form-assignment-note mb-4">
            <strong>Booking dates stay in Bookings.</strong>
            Guest/company, start date, and end date are linked from booking records automatically, so update those details from <a href="bookings.php">Bookings</a> instead of the car master record.
        </div>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="car_type" class="form-label">Car Type</label>
                <input type="text" class="form-control" id="car_type" name="car_type" value="<?= e($car['car_type'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label for="plate_no" class="form-label">Plate Number</label>
                <input type="text" class="form-control" id="plate_no" name="plate_no" value="<?= e($car['plate_no'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label for="model_name" class="form-label">Model Year</label>
                <input type="text" class="form-control" id="model_name" name="model_name" value="<?= e($car['model_name'] ?? '') ?>" required>
            </div>

            <div class="col-md-3">
                <label for="seat_capacity" class="form-label">Seat Capacity</label>
                <input type="number" min="1" class="form-control" id="seat_capacity" name="seat_capacity" value="<?= e((string) ($car['seat_capacity'] ?? 4)) ?>" required>
            </div>

            <div class="col-md-3">
                <label for="availability_status" class="form-label">Status</label>
                <select class="form-select" id="availability_status" name="availability_status" required>
                    <?php foreach (car_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($car['availability_status'] ?? 'Available', $status) ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea class="form-control" id="note" name="note" rows="4" placeholder="Optional note about maintenance, assignment, or condition"><?= e($car['note'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-accent px-4"><?= e($submitLabel) ?></button>
                <a class="btn btn-shell" href="cars.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
