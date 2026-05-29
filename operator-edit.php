<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'operators';
$pageTitle = 'Edit Operator';
$pageSummary = 'Update operator details and availability.';
$pageActions = '<a class="btn btn-shell" href="operators.php">All Operators</a>';

$errors = [];
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid operator ID.');
    redirect('operators.php');
}

if ($db === null) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/messages.php';
    require __DIR__ . '/includes/footer.php';
    return;
}

$statement = $db->prepare(
    'SELECT * FROM operators WHERE id = ? LIMIT 1'
);

if (!$statement instanceof mysqli_stmt) {
    set_flash('danger', 'Failed to load the operator record.');
    redirect('operators.php');
}

$statement->bind_param('i', $id);
$statement->execute();

$result = $statement->get_result();
$operator = $result->fetch_assoc();

$statement->close();

if ($operator === null) {
    set_flash('danger', 'Operator record not found.');
    redirect('operators.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $operator = [
        'id' => $id,
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'phone_number' => trim((string) ($_POST['phone_number'] ?? '')),
        'operator_status' => trim((string) ($_POST['operator_status'] ?? 'Active')),
        'note' => trim((string) ($_POST['note'] ?? '')),
    ];

    foreach (['full_name', 'operator_status'] as $field) {
        if ($operator[$field] === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if (!in_allowed_values(
        $operator['operator_status'],
        operator_statuses()
    )) {
        $errors[] = 'Please choose a valid operator status.';
    }

    if ($errors === []) {
        $updateStatement = $db->prepare(
            'UPDATE operators
             SET full_name = ?,
                 phone_number = ?,
                 operator_status = ?,
                 note = ?
             WHERE id = ?'
        );

        if (!$updateStatement instanceof mysqli_stmt) {
            $errors[] = 'Failed to prepare the update query.';
        } else {
            $updateStatement->bind_param(
                'ssssi',
                $operator['full_name'],
                $operator['phone_number'],
                $operator['operator_status'],
                $operator['note'],
                $id
            );

            try {
                $db->begin_transaction();

                if (!$updateStatement->execute()) {
                    $errors[] = 'Unable to update the operator.';
                } else {
                    $syncStatement = $db->prepare(
                        'UPDATE bookings
                         SET operator_name = ?
                         WHERE operator_id = ?'
                    );

                    if (!$syncStatement instanceof mysqli_stmt) {
                        $errors[] = 'Failed to sync linked booking operators.';
                    } else {
                        $syncStatement->bind_param(
                            'si',
                            $operator['full_name'],
                            $id
                        );

                        if (!$syncStatement->execute()) {
                            $errors[] = 'Failed to sync linked booking operators.';
                        }

                        $syncStatement->close();
                    }
                }

                if ($errors === []) {
                    $db->commit();
                    $updateStatement->close();

                    set_flash(
                        'success',
                        'Operator updated successfully.'
                    );

                    redirect('operators.php');
                }

                $db->rollback();
            } catch (mysqli_sql_exception $e) {
                $db->rollback();

                if ($e->getCode() === 1062) {
                    $errors[] = 'The operator name already exists.';
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }

            $updateStatement->close();
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
$formTitle = 'Edit Operator';
$submitLabel = 'Update Operator';
$formAction = 'operator-edit.php?id=' . $id;

require __DIR__ . '/includes/operator-form.php';
require __DIR__ . '/includes/footer.php';
?>
