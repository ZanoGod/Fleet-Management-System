<?php

declare(strict_types=1);

$contract = $contract ?? [];
$submitLabel = $submitLabel ?? 'Save Contract';
$formTitle = $formTitle ?? 'Monthly Contract Form';
$backUrl = $backUrl ?? 'monthly-contracts.php';
$cars = $cars ?? [];
$drivers = $drivers ?? [];
$operators = $operators ?? [];
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Manage customer, vehicle, driver, billing, and renewal dates in one record.</p>
            </div>
            <a class="btn btn-shell" href="<?= e($backUrl) ?>">Back to Contracts</a>
        </div>

        <form method="post" class="row g-3" data-contract-form>
            <?= csrf_field() ?>

            <div class="col-md-4">
                <label for="contract_no" class="form-label">Contract No.</label>
                <input
                    type="text"
                    class="form-control"
                    id="contract_no"
                    name="contract_no"
                    value="<?= e($contract['contract_no'] ?? '') ?>"
                    placeholder="Auto generated if blank">
            </div>

            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input
                    type="date"
                    class="form-control"
                    id="start_date"
                    name="start_date"
                    value="<?= e($contract['start_date'] ?? '') ?>"
                    required>
            </div>

            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input
                    type="date"
                    class="form-control"
                    id="end_date"
                    name="end_date"
                    value="<?= e($contract['end_date'] ?? '') ?>"
                    required>
            </div>

            <div class="col-12">
                <div class="p-3 border rounded bg-light-subtle">
                    <label class="form-label fw-bold mb-3 text-accent">Customer Information</label>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label small">Customer Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="customer_name"
                                name="customer_name"
                                value="<?= e($contract['customer_name'] ?? '') ?>"
                                required>
                        </div>

                        <div class="col-md-3">
                            <label for="customer_phone" class="form-label small">Phone</label>
                            <input
                                type="text"
                                class="form-control"
                                id="customer_phone"
                                name="customer_phone"
                                value="<?= e($contract['customer_phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-3">
                            <label for="customer_email" class="form-label small">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="customer_email"
                                name="customer_email"
                                value="<?= e($contract['customer_email'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label for="customer_address" class="form-label small">Address</label>
                            <textarea
                                class="form-control"
                                id="customer_address"
                                name="customer_address"
                                rows="2"><?= e($contract['customer_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="p-3 border rounded bg-light-subtle">
                    <label class="form-label fw-bold mb-3 text-accent">Assignment</label>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="assignment_type" class="form-label small">Assignment Type</label>
                            <select
                                class="form-select"
                                id="assignment_type"
                                name="assignment_type"
                                data-contract-assignment
                                required>
                                <?php foreach (monthly_contract_assignment_types() as $assignmentType): ?>
                                    <option
                                        value="<?= e($assignmentType) ?>"
                                        <?= selected($contract['assignment_type'] ?? 'Vehicle Only', $assignmentType) ?>>
                                        <?= e($assignmentType) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3" data-contract-car-group>
                            <label for="car_id" class="form-label small">Vehicle</label>
                            <select class="form-select" id="car_id" name="car_id" data-contract-car>
                                <option value="">Select vehicle</option>
                                <?php foreach ($cars as $car): ?>
                                    <?php $carLabel = trim($car['car_type'] . ' | ' . $car['plate_no'] . ' | ' . $car['model_name']); ?>
                                    <option
                                        value="<?= e((string) $car['id']) ?>"
                                        <?= selected($contract['car_id'] ?? '', $car['id']) ?>>
                                        <?= e($carLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3" data-contract-driver-group>
                            <label for="driver_id" class="form-label small">Driver</label>
                            <select class="form-select" id="driver_id" name="driver_id" data-contract-driver>
                                <option value="">Select driver</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <?php
                                    $driverLabel = implode(' | ', array_values(array_filter([
                                        trim((string) ($driver['full_name'] ?? '')),
                                        trim((string) ($driver['phone_number'] ?? '')),
                                    ], static fn (?string $value): bool => $value !== null && $value !== '')));
                                    ?>
                                    <option
                                        value="<?= e((string) $driver['id']) ?>"
                                        <?= selected($contract['driver_id'] ?? '', $driver['id']) ?>>
                                        <?= e($driverLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="operator_id" class="form-label small">Operator</label>
                            <select class="form-select" id="operator_id" name="operator_id" required>
                                <option value="">Select operator</option>
                                <?php foreach ($operators as $operator): ?>
                                    <option
                                        value="<?= e((string) $operator['id']) ?>"
                                        <?= selected($contract['operator_id'] ?? '', $operator['id']) ?>>
                                        <?= e((string) $operator['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Shared with daily bookings.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <label for="monthly_rate" class="form-label">Monthly Rate</label>
                <input
                    type="number"
                    class="form-control"
                    id="monthly_rate"
                    name="monthly_rate"
                    value="<?= e((string) ($contract['monthly_rate'] ?? '0.00')) ?>"
                    min="0"
                    step="0.01"
                    required>
            </div>

            <div class="col-md-3">
                <label for="security_deposit" class="form-label">Security Deposit</label>
                <input
                    type="number"
                    class="form-control"
                    id="security_deposit"
                    name="security_deposit"
                    value="<?= e((string) ($contract['security_deposit'] ?? '0.00')) ?>"
                    min="0"
                    step="0.01">
            </div>

            <div class="col-md-3">
                <label for="payment_status" class="form-label">Payment Status</label>
                <select class="form-select" id="payment_status" name="payment_status" required>
                    <?php foreach (monthly_contract_payment_statuses() as $paymentStatus): ?>
                        <option
                            value="<?= e($paymentStatus) ?>"
                            <?= selected($contract['payment_status'] ?? 'Pending', $paymentStatus) ?>>
                            <?= e($paymentStatus) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label">Contract Status</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach (monthly_contract_statuses() as $status): ?>
                        <option
                            value="<?= e($status) ?>"
                            <?= selected($contract['status'] ?? 'Active', $status) ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea
                    class="form-control"
                    id="note"
                    name="note"
                    rows="4"
                    placeholder="Optional contract remark, pickup condition, or billing note"><?= e($contract['note'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-accent px-4"><?= e($submitLabel) ?></button>
                <a class="btn btn-shell" href="<?= e($backUrl) ?>">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('[data-contract-form]');

        if (!form) {
            return;
        }

        const assignment = form.querySelector('[data-contract-assignment]');
        const car = form.querySelector('[data-contract-car]');
        const driver = form.querySelector('[data-contract-driver]');
        const carGroup = form.querySelector('[data-contract-car-group]');
        const driverGroup = form.querySelector('[data-contract-driver-group]');

        function syncAssignmentFields() {
            const value = assignment.value;
            const needsCar = value === 'Vehicle Only' || value === 'Vehicle and Driver';
            const needsDriver = value === 'Vehicle and Driver';

            car.required = needsCar;
            driver.required = needsDriver;
            car.disabled = !needsCar;
            driver.disabled = !needsDriver;
            carGroup.classList.toggle('contract-field-muted', !needsCar);
            driverGroup.classList.toggle('contract-field-muted', !needsDriver);

            if (!needsCar) {
                car.value = '';
            }

            if (!needsDriver) {
                driver.value = '';
            }
        }

        assignment.addEventListener('change', syncAssignmentFields);
        syncAssignmentFields();
    });
</script>
