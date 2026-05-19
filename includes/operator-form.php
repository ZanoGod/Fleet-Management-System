<?php

declare(strict_types=1);

$operator = $operator ?? [];
$submitLabel = $submitLabel ?? 'Save Operator';
$formTitle = $formTitle ?? 'Operator Form';
?>

<div class="card-shell form-card">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e($formTitle) ?></h1>
                <p class="text-muted-soft mb-0">Keep the operator directory ready for booking assignments.</p>
            </div>
            <a class="btn btn-shell" href="operators.php">Back to Operators</a>
        </div>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="full_name" class="form-label">Operator Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($operator['full_name'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= e($operator['phone_number'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label for="operator_status" class="form-label">Status</label>
                <select class="form-select" id="operator_status" name="operator_status" required>
                    <?php foreach (operator_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($operator['operator_status'] ?? 'Active', $status) ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label for="note" class="form-label">Note</label>
                <textarea class="form-control" id="note" name="note" rows="4" placeholder="Optional note about operator availability or remarks"><?= e($operator['note'] ?? '') ?></textarea>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-accent px-4"><?= e($submitLabel) ?></button>
                <a class="btn btn-shell" href="operators.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
