<?php

declare(strict_types=1);

$booking = $booking ?? [];
$submitLabel = $submitLabel ?? 'Save Booking';
$formTitle = $formTitle ?? 'Booking Form';
?>

<div class="card border-0 shadow-sm form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-secondary mb-0">Fill in the trip and assignment details below.</p>
            </div>
            <a class="btn btn-outline-success" href="index.php">Back to Dashboard</a>
        </div>

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
                <label for="car_type" class="form-label">Car Type</label>
                <input
                    type="text"
                    class="form-control"
                    id="car_type"
                    name="car_type"
                    list="car-types"
                    value="<?= e($booking['car_type'] ?? '') ?>"
                    required
                >
                <datalist id="car-types">
                    <option value="Alphard">
                    <option value="Allion">
                    <option value="Ertiga Type II">
                    <option value="Ertiga Type III">
                    <option value="Hiace">
                </datalist>
            </div>

            <div class="col-md-6">
                <label for="car_no" class="form-label">Car No</label>
                <input
                    type="text"
                    class="form-control"
                    id="car_no"
                    name="car_no"
                    value="<?= e($booking['car_no'] ?? '') ?>"
                    required
                >
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

            <div class="col-md-6">
                <label for="driver_name" class="form-label">Driver</label>
                <input
                    type="text"
                    class="form-control"
                    id="driver_name"
                    name="driver_name"
                    value="<?= e($booking['driver_name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4">
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

            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach (booking_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($booking['status'] ?? '') === $status ? 'selected' : '' ?>>
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
                    placeholder="Optional remark or special request"
                ><?= e($booking['remark'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-success px-4"><?= e($submitLabel) ?></button>
                <a class="btn btn-light border" href="index.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
