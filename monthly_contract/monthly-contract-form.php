<?php

declare(strict_types=1);

$contract = $contract ?? [];
$submitLabel = $submitLabel ?? 'Save Contract';
$formTitle = $formTitle ?? 'Monthly Contract Form';
$backUrl = $backUrl ?? 'monthly-contracts.php';
$cars = $cars ?? [];
$drivers = $drivers ?? [];
$operators = $operators ?? [];
$customers = $customers ?? [];
$customerSelectionEnabled = $customerSelectionEnabled ?? true;
$customerFieldsLocked = $customerSelectionEnabled
    && trim((string) ($contract['customer_id'] ?? '')) === '';
$internalOperatorOptions = $operators;
$knownInternalOperatorNames = array_map(
    static fn(array $operator): string => (string) ($operator['full_name'] ?? ''),
    $internalOperatorOptions
);

foreach (['prepared_by', 'invoice_sender', 'checked_by'] as $field) {
    $storedName = trim((string) ($contract[$field] ?? ''));

    if ($storedName !== '' && !in_array($storedName, $knownInternalOperatorNames, true)) {
        $internalOperatorOptions[] = ['full_name' => $storedName];
        $knownInternalOperatorNames[] = $storedName;
    }
}
?>

<style>
    .customer-lock {
        background: #e9ecef;
        color: #6c757d;
        cursor: not-allowed;
    }

    .customer-lock:focus {
        background: #e9ecef;
        box-shadow: none;
    }
</style>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Manage customer, vehicle, driver, billing, and renewal dates in one
                    record.</p>
            </div>
            <a class="btn btn-shell" href="<?= e($backUrl) ?>">Back to Contracts</a>
        </div>

        <form method="post" class="row g-3" data-contract-form>
            <?= csrf_field() ?>

            <div class="col-md-4">
                <label for="contract_no" class="form-label">Contract No.</label>
                <input type="text" class="form-control" id="contract_no" name="contract_no"
                    value="<?= e($contract['contract_no'] ?? '') ?>" placeholder="Auto generated if blank">
            </div>

            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date"
                    value="<?= e($contract['start_date'] ?? '') ?>" required>
            </div>

            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date"
                    value="<?= e($contract['end_date'] ?? '') ?>" required>
            </div>

            <div class="col-12">
                <div class="p-3 border rounded bg-light-subtle">
                    <label class="form-label fw-bold mb-3 text-accent">Customer Information</label>

                    <div class="row g-3">
                        <?php if ($customerSelectionEnabled): ?>
                            <div class="col-md-6">
                                <label for="customer_select" class="form-label small">Select Customer or Company</label>
                                <select class="form-select" id="customer_select" name="customer_id" required>
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <?php
                                        $companyName = trim((string) ($customer['company_name'] ?? ''));
                                        $guestName = trim((string) ($customer['guest_name'] ?? ''));
                                        $contactPerson = trim((string) ($customer['contact_person'] ?? ''));
                                        $customerOptionLabel = $companyName;
                                        if ((string) ($customer['customer_status'] ?? '') === 'Active') {
                                            $customerOptionLabel .= ' (Active Contract)';
                                        }

                                        // if ((string) ($customer['customer_status'] ?? '') === 'Active') {
                                        //     if ($guestName !== '') {
                                        //         $customerOptionLabel .= ' - ' . $guestName;
                                        //     } elseif ($contactPerson !== '') {
                                        //         $customerOptionLabel .= ' - ' . $contactPerson;
                                        //     }
                                        //     if ($contactPerson !== '') {
                                        //         $customerOptionLabel .= ' - ' . $contactPerson;
                                        //     }elseif ($contactPerson !== '') {
                                        //         $customerOptionLabel .= ' - ' . $contactPerson;
                                        //     }
                                        // }
                                        // if ((string) ($customer['customer_status'] ?? '') === 'Inactive') {
                                        //     $customerOptionLabel .= ' (Inactive)';
                                        // }
                                        ?>
                                        <option value="<?= (int)$customer['id'] ?>"
                                            <?= selected($contract['customer_id'] ?? '', $customer['id']) ?>
                                            data-company="<?= e($customer['company_name']) ?>"
                                            data-guest="<?= e($customer['guest_name']) ?>"
                                            data-contact="<?= e($customer['contact_person']) ?>"
                                            data-phone="<?= e($customer['contact_phone']) ?>"
                                            data-email="<?= e($customer['contact_email']) ?>"
                                            data-address="<?= e($customer['customer_address']) ?>"
                                            data-offday="<?= e($customer['off_day']) ?>"
                                            data-service="<?= e($customer['service_hour']) ?>">
                                            <?= e($customerOptionLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="customer_id"
                                value="<?= e((string) ($contract['customer_id'] ?? '')) ?>">
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label for="customer_name" class="form-label small">Customer Name</label>
                            <input type="text" class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                id="customer_name" name="customer_name"
                                value="<?= e($contract['customer_name'] ?? '') ?>" required
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="guest_name" class="form-label small">Guest Name</label>
                            <input type="text" class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                id="guest_name" name="guest_name" value="<?= e($contract['guest_name'] ?? '') ?>"
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_person" class="form-label small">Contact Person</label>
                            <input type="text" class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                id="contact_person" name="contact_person"
                                value="<?= e($contract['contact_person'] ?? '') ?>"
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-md-3">
                            <label for="customer_phone" class="form-label small">Phone</label>
                            <input type="text" class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                id="customer_phone" name="customer_phone"
                                value="<?= e($contract['customer_phone'] ?? '') ?>"
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-md-3">
                            <label for="customer_email" class="form-label small">Email</label>
                            <input type="email" class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                id="customer_email" name="customer_email"
                                value="<?= e($contract['customer_email'] ?? '') ?>"
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>>
                        </div>

                        <div class="col-12">
                            <label for="customer_address" class="form-label small">Address</label>
                            <textarea id="customer_address" name="customer_address"
                                class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>" rows="2"
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>><?= e($contract['customer_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-12">
                <div class="p-3 border rounded bg-light-subtle">

                    <label class="form-label fw-bold mb-3 text-accent">
                        Service Information
                    </label>

                    <div class="row g-3">
                        <?php
                        $offDayOptions = [
                            'Public Holidays',
                            'Sunday & Public Holidays',
                            'Sat, Sun & Public Holidays',
                            'Sat or Sun & Public Holidays',
                            'Once a week',
                        ];
                        ?>
                        <div class="col-md-6">
                            <label for="off_day" class="form-label small">
                                Off Day
                            </label>

                            <select
                                class="form-select<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                id="off_day"
                                name="off_day"
                                <?= $customerFieldsLocked ? 'disabled' : '' ?>>

                                <option value="">- - Select Off Day - -</option>

                                <?php foreach ($offDayOptions as $option): ?>
                                    <option
                                        value="<?= e($option) ?>"
                                        <?= selected($contract['off_day'] ?? '', $option) ?>>
                                        <?= e($option) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">
                                Service Hour
                            </label>
                            <input type="text" id="service_hour" name="service_hour"
                                class="form-control<?= $customerFieldsLocked ? ' customer-lock' : '' ?>"
                                value="<?= e($contract['service_hour'] ?? '') ?>"
                                <?= $customerFieldsLocked ? 'readonly' : '' ?>>
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
                            <select class="form-select" id="assignment_type" name="assignment_type"
                                data-contract-assignment required>
                                <?php foreach (monthly_contract_assignment_types() as $assignmentType): ?>
                                    <option value="<?= e($assignmentType) ?>"
                                        <?= selected($contract['assignment_type'] ?? 'Vehicle Only', $assignmentType) ?>>
                                        <?= e($assignmentType) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" data-contract-car-group>
                            <label for="car_id" class="form-label small">Available Vehicle</label>
                            <select class="form-select" id="car_id" name="car_id" data-contract-car>
                                <option value="">Select available vehicle</option>
                                <?php foreach ($cars as $car): ?>
                                    <?php $carLabel = trim($car['car_type'] . ' | ' . $car['plate_no'] . ' | ' . $car['model_name']); ?>
                                    <option value="<?= e((string) $car['id']) ?>"
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
                                    ], static fn(?string $value): bool => $value !== null && $value !== '')));
                                    ?>
                                    <option value="<?= e((string) $driver['id']) ?>"
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
                                    <option value="<?= e((string) $operator['id']) ?>"
                                        <?= selected($contract['operator_id'] ?? '', $operator['id']) ?>>
                                        <?= e((string) $operator['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="even_odd" class="form-label small">Even / Odd</label>
                            <select class="form-select" id="even_odd" name="even_odd">
                                <option value="">Select</option>
                                <option value="Even" <?= selected($contract['even_odd'] ?? '', 'Even') ?>>Even</option>
                                <option value="Odd" <?= selected($contract['even_odd'] ?? '', 'Odd') ?>>Odd</option>
                            </select>
                        </div>

                        <div class="col-md-9">
                            <label for="operation_sheet" class="form-label small">Operation Sheet</label>
                            <input type="text" class="form-control" id="operation_sheet" name="operation_sheet"
                                value="<?= e($contract['operation_sheet'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <div class="p-3 border rounded bg-light-subtle">

                                <label class="form-label fw-bold mb-3 text-accent">
                                    Insurance
                                </label>

                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label small">
                                            Insurance Company
                                        </label>

                                        <input type="text" class="form-control" name="insurance_company"
                                            value="<?= e($contract['insurance_company'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small">
                                            Insurance Price
                                        </label>
                                        <input type="text"
                                            class="form-control"
                                            id="insurance_price"
                                            name="insurance_price"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            pattern="[0-9,]*"
                                            placeholder="0"
                                            value="<?= e(monthly_contract_money($contract['insurance_price'] ?? '')) ?>">
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Rental Price</label>

                <div class="input-group">

                    <input type="text" class="form-control" id="monthly_rate" name="monthly_rate" inputmode="numeric" placeholder="0"
                        autocomplete="off" pattern="[0-9,]*"
                        value="<?= e(monthly_contract_money($contract['monthly_rate'] ?? "")) ?>"
                        aria-describedby="monthly_rate_help" required>

                    <select class="form-select" name="rental_currency" style="max-width:120px">

                        <option value="MMK" <?= selected($contract['rental_currency'] ?? 'MMK', 'MMK') ?>>
                            MMK
                        </option>

                        <option value="USD" <?= selected($contract['rental_currency'] ?? 'MMK', 'USD') ?>>
                            USD
                        </option>

                    </select>

                </div>
                <div id="monthly_rate_help" class="form-text">Whole numbers only. Example: 100,000 MMK</div>
            </div>

            <div class="col-md-3">
                <label for="rental_status" class="form-label">
                    Status
                </label>

                <select class="form-select"
                    id="rental_status"
                    name="rental_status"
                    required>
                    <?php foreach (monthly_contract_rental_statuses() as $rentalStatus): ?>
                        <option value="<?= e($rentalStatus) ?>"
                            <?= selected($contract['rental_status'] ?? 'Including fuel', $rentalStatus) ?>>
                            <?= e($rentalStatus) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label">Contract Status</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach (monthly_contract_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($contract['status'] ?? 'Active', $status) ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea class="form-control" id="note" name="note" rows="4"
                    placeholder="Optional contract remark, pickup condition, or billing note"><?= e($contract['note'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <div class="p-3 border rounded bg-light-subtle">

                    <label class="form-label fw-bold mb-3 text-accent">
                        Internal Information
                    </label>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label for="prepared_by" class="form-label small">Prepared By</label>
                            <select class="form-select" id="prepared_by" name="prepared_by">
                                <option value="">Select operator</option>
                                <?php foreach ($internalOperatorOptions as $operator): ?>
                                    <option value="<?= e((string) $operator['full_name']) ?>"
                                        <?= selected($contract['prepared_by'] ?? '', $operator['full_name']) ?>>
                                        <?= e((string) $operator['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="invoice_sender" class="form-label small">Invoice Sender</label>
                            <select class="form-select" id="invoice_sender" name="invoice_sender">
                                <option value="">Select operator</option>
                                <?php foreach ($internalOperatorOptions as $operator): ?>
                                    <option value="<?= e((string) $operator['full_name']) ?>"
                                        <?= selected($contract['invoice_sender'] ?? '', $operator['full_name']) ?>>
                                        <?= e((string) $operator['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="checked_by" class="form-label small">Checked By</label>
                            <select class="form-select" id="checked_by" name="checked_by">
                                <option value="">Select operator</option>
                                <?php foreach ($internalOperatorOptions as $operator): ?>
                                    <option value="<?= e((string) $operator['full_name']) ?>"
                                        <?= selected($contract['checked_by'] ?? '', $operator['full_name']) ?>>
                                        <?= e((string) $operator['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">The same operator can be selected in more than one field.</div>
                        </div>

                    </div>

                </div>
            </div>
            <div class="col-12">

                <label class="form-label">
                    Terms & Conditions
                </label>

                <textarea class="form-control" rows="5"
                    name="terms_conditions"><?= e($contract['terms_conditions'] ?? '') ?></textarea>

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
            const needsDriver = value === 'Driver Only' || value === 'Vehicle and Driver';

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

        const customerSelect = document.getElementById('customer_select');

        if (customerSelect) {

            customerSelect.addEventListener('change', function() {

                const option = this.options[this.selectedIndex];

                if (!option.value) {

                    // No customer selected
                    return;
                }

                document.getElementById('customer_name').value =
                    option.dataset.company || '';

                document.getElementById('guest_name').value =
                    option.dataset.guest || '';

                document.getElementById('contact_person').value =
                    option.dataset.contact || '';

                document.getElementById('customer_phone').value =
                    option.dataset.phone || '';

                document.getElementById('customer_email').value =
                    option.dataset.email || '';
                document.getElementById('customer_address').value =
                    option.dataset.address || '';

                document.getElementById('off_day').value = option.dataset.offday || '';
                document.getElementById('service_hour').value = option.dataset.service || '';

                [
                    'customer_name',
                    'guest_name',
                    'contact_person',
                    'customer_phone',
                    'customer_email',
                    'customer_address',
                    'off_day',
                    'service_hour'
                ].forEach(function(id) {
                    const field = document.getElementById(id);

                    if (!field) {
                        return;
                    }

                    if (field.tagName === 'SELECT') {
                        field.disabled = false;
                    } else {
                        field.readOnly = false;
                    }

                    field.classList.remove('customer-lock');
                });

            });

        }

        function formatWholeAmount(value) {
            const digits = value.replace(/,/g, '');

            if (!/^\d*$/.test(digits)) {
                return value;
            }

            return digits === '' ? '' : Number(digits).toLocaleString('en-US');
        }

        function attachMoneyFormatter(input) {
            if (!input) {
                return;
            }

            input.value = formatWholeAmount(input.value);

            input.addEventListener('beforeinput', function(event) {
                if (event.data !== null && !/^\d+$/.test(event.data)) {
                    event.preventDefault();
                }
            });

            input.addEventListener('paste', function(event) {
                const pasted = event.clipboardData.getData('text').replace(/,/g, '');

                if (!/^\d*$/.test(pasted)) {
                    event.preventDefault();
                }
            });

            input.addEventListener('input', function() {
                const formatted = formatWholeAmount(input.value);

                if (formatted !== input.value) {
                    input.value = formatted;
                }
            });
        }

        attachMoneyFormatter(document.getElementById('monthly_rate'));
        attachMoneyFormatter(document.getElementById('insurance_price'));

        assignment.addEventListener('change', syncAssignmentFields);
        syncAssignmentFields();
    });
</script>