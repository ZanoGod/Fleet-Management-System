<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'operators';
$pageTitle = 'Add Operator';
$pageSummary = 'Create a new operator master record.';
$pageActions = '<a class="btn btn-shell" href="operators.php">All Operators</a>';
$errors = [];
$operator = [
    'full_name' => '',
    'phone_number' => '',
    'operator_status' => 'Active',
    'note' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator = [
        'full_name' => old($_POST, 'full_name'),
        'phone_number' => old($_POST, 'phone_number'),
        'operator_status' => old($_POST, 'operator_status', 'Active'),
        'note' => old($_POST, 'note'),
    ];

    foreach (['full_name', 'operator_status'] as $field) {
        if ($operator[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if (!in_allowed_values($operator['operator_status'], operator_statuses())) {
        $errors[] = 'Please choose a valid operator status.';
    }

    if ($db === null) {
        $errors[] = 'Database is not connected yet. Please import the SQL file and check config/database.php.';
    }

    if ($errors === []) {
        $statement = $db->prepare(
            'INSERT INTO operators
            (full_name, phone_number, operator_status, note)
            VALUES (?, ?, ?, ?)'
        );

        if (!$statement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the database query.';
        } else {
            $statement->bind_param(
                'ssss',
                $operator['full_name'],
                $operator['phone_number'],
                $operator['operator_status'],
                $operator['note']
            );

            if ($statement->execute()) {
                $statement->close();
                set_flash('success', 'Operator added successfully.');
                redirect('operators.php');
            }

            $errors[] = 'Unable to save the operator. The operator name may already exist.';
            $statement->close();
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php
$formTitle = 'Add Operator';
$submitLabel = 'Save Operator';
$formAction = 'operator-create.php';
require __DIR__ . '/includes/operator-form.php';
require __DIR__ . '/includes/footer.php';
?>
