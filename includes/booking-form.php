<?php

declare(strict_types=1);

$booking = $booking ?? [];
$submitLabel = $submitLabel ?? 'Save Booking';
$formTitle = $formTitle ?? 'Booking Form';
$cars = $cars ?? [];
$drivers = $drivers ?? [];
$operators = $operators ?? [];

$selectedCar = find_row_by_id($cars, (int) ($booking['car_id'] ?? 0));
$selectedDriver = find_row_by_id($drivers, (int) ($booking['driver_id'] ?? 0));

$carInputValue = trim((string) ($booking['custom_car_name'] ?? ''));

if ($carInputValue === '' && $selectedCar !== null) {
    $carInputValue = trim(
        (string) ($selectedCar['car_type'] ?? '')
        . ' | '
        . (string) ($selectedCar['plate_no'] ?? '')
        . ' | '
        . (string) ($selectedCar['model_name'] ?? '')
    );
}

$driverInputValue = trim((string) ($booking['custom_driver_name'] ?? ''));

if ($driverInputValue === '' && $selectedDriver !== null) {
    $driverParts = array_values(array_filter([
        trim((string) ($selectedDriver['full_name'] ?? '')),
        trim((string) ($selectedDriver['phone_number'] ?? '')),
    ], static fn (?string $value): bool => $value !== null && $value !== ''));

    $driverInputValue = $driverParts === [] ? '' : implode(' | ', $driverParts);
}
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

        <?php if ($operators === []): ?>
            <div class="alert alert-warning border-0 m-0">
                <strong>Bookings need operators first.</strong>
                Please add at least one operator before creating a booking. Cars and drivers can still be typed manually.
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-shell" href="operators.php">Manage Operators</a>
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
                    <label for="car_value" class="form-label">Car</label>
                    <input
                        type="text"
                        class="form-control"
                        id="car_value"
                        name="car_value"
                        list="carOptions"
                        value="<?= e($carInputValue) ?>"
                        placeholder="Select from the list or type a custom car"
                        data-select-or-type-input
                        data-select-or-type-hidden="car_id"
                    >
                    <input type="hidden" id="car_id" name="car_id" value="<?= e((string) ($booking['car_id'] ?? '')) ?>">
                    <datalist id="carOptions">
                        <?php foreach ($cars as $car): ?>
                            <?php $carLabel = trim($car['car_type'] . ' | ' . $car['plate_no'] . ' | ' . $car['model_name']); ?>
                            <option value="<?= e($carLabel) ?>" data-id="<?= e((string) $car['id']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text">Choose a saved car or type a custom car name.</div>
                </div>

                <div class="col-md-6">
                    <label for="driver_value" class="form-label">Driver</label>
                    <input
                        type="text"
                        class="form-control"
                        id="driver_value"
                        name="driver_value"
                        list="driverOptions"
                        value="<?= e($driverInputValue) ?>"
                        placeholder="Select from the list or type a custom driver"
                        data-select-or-type-input
                        data-select-or-type-hidden="driver_id"
                    >
                    <input type="hidden" id="driver_id" name="driver_id" value="<?= e((string) ($booking['driver_id'] ?? '')) ?>">
                    <datalist id="driverOptions">
                        <?php foreach ($drivers as $driver): ?>
                            <?php
                            $driverParts = array_values(array_filter([
                                trim((string) ($driver['full_name'] ?? '')),
                                trim((string) ($driver['phone_number'] ?? '')),
                            ], static fn (?string $value): bool => $value !== null && $value !== ''));
                            $driverLabel = $driverParts === [] ? '' : implode(' | ', $driverParts);
                            ?>
                            <option value="<?= e($driverLabel) ?>" data-id="<?= e((string) $driver['id']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text">Choose a saved driver or type a custom driver name.</div>
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
                    <div class="form-text">Operators are selected from the operator master list.</div>
                </div>

                <div class="col-md-3">
                    <label for="even_odd" class="form-label">Even / Odd</label>
                    <select class="form-select" id="even_odd" name="even_odd">
                        <option value="">Select if needed</option>
                        <?php foreach (booking_even_odd_options() as $option): ?>
                            <option value="<?= e($option) ?>" <?= selected($booking['even_odd'] ?? '', $option) ?>>
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

                <div class="col-md-3">
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
