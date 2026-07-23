<?php

declare(strict_types=1);

$customer = $customer ?? [];

$submitLabel = $submitLabel ?? 'Save Customer';
$formTitle = $formTitle ?? 'Customer Form';

$backUrl = $backUrl ?? 'customers.php';
$backLabel = $backLabel ?? 'Back to Customers';
$cancelLabel = $cancelLabel ?? 'Cancel';

$formLead = $formLead ?? 'Maintain customer master information for monthly contract assignments.';

$showAssignmentNote = $showAssignmentNote ?? true;

$assignmentNoteTitle = $assignmentNoteTitle ?? 'Contract information stays in Monthly Contracts.';
$assignmentNoteBody = $assignmentNoteBody ?? 'Contract number, vehicle, driver, operator, rental period, and payment details are managed from';
$assignmentNoteLinkUrl = $assignmentNoteLinkUrl ?? 'monthly-contracts.php';
$assignmentNoteLinkText = $assignmentNoteLinkText ?? 'Monthly Contracts';

$customerStatuses = [
    'Active',
    'Inactive',
];
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0"><?= e($formLead) ?></p>
            </div>

            <a class="btn btn-shell" href="<?= e($backUrl) ?>">
                <?= e($backLabel) ?>
            </a>
        </div>

        <?php if ($showAssignmentNote): ?>
            <div class="form-assignment-note mb-4">
                <strong><?= e($assignmentNoteTitle) ?></strong>
                <?= e($assignmentNoteBody) ?>
                <a href="<?= e($assignmentNoteLinkUrl) ?>">
                    <?= e($assignmentNoteLinkText) ?>
                </a>
                instead of the customer master record.
            </div>
        <?php endif; ?>

        <form method="post" class="row g-3">

            <div class="col-md-6">
                <label for="company_name" class="form-label">
                    Company Name <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="company_name"
                    name="company_name"
                    value="<?= e($customer['company_name'] ?? '') ?>"
                    required>
            </div>

            <div class="col-md-6">
                <label for="guest_name" class="form-label">
                    Guest Name
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="guest_name"
                    name="guest_name"
                    value="<?= e($customer['guest_name'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="contact_person" class="form-label">
                    Contact Person
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="contact_person"
                    name="contact_person"
                    value="<?= e($customer['contact_person'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="contact_phone" class="form-label">
                    Contact Phone
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="contact_phone"
                    name="contact_phone"
                    value="<?= e($customer['contact_phone'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="contact_email" class="form-label">
                    Contact Email
                </label>

                <input
                    type="email"
                    class="form-control"
                    id="contact_email"
                    name="contact_email"
                    value="<?= e($customer['contact_email'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="customer_status" class="form-label">
                    Status
                </label>

                <select
                    class="form-select"
                    id="customer_status"
                    name="customer_status">

                    <?php foreach ($customerStatuses as $status): ?>

                        <option
                            value="<?= e($status) ?>"
                            <?= selected($customer['customer_status'] ?? 'Active', $status) ?>>

                            <?= e($status) ?>

                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="col-12">
                <label for="remark" class="form-label">
                    Remark
                </label>

                <textarea
                    class="form-control"
                    id="remark"
                    name="remark"
                    rows="4"
                    placeholder="Optional customer notes"><?= e($customer['remark'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">

                <button
                    type="submit"
                    class="btn btn-accent px-4">

                    <?= e($submitLabel) ?>

                </button>

                <a
                    class="btn btn-shell"
                    href="<?= e($backUrl) ?>">

                    <?= e($cancelLabel) ?>

                </a>

            </div>

        </form>

    </div>
</div>