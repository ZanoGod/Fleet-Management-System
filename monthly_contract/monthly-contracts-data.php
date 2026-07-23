<?php

declare(strict_types=1);

function monthly_contract_default_filters(): array
{
    return [
        'search' => '',
        'status' => '',
        'assignment_type' => '',
        'payment_status' => '',
        'expiry' => '',
        'date_from' => '',
        'date_to' => '',
    ];
}

function monthly_contract_filters(array $source): array
{
    $filters = monthly_contract_default_filters();

    foreach ($filters as $key => $defaultValue) {
        $filters[$key] = trim((string) ($source[$key] ?? $defaultValue));
    }

    if ($filters['status'] !== '' && !in_allowed_values($filters['status'], monthly_contract_statuses())) {
        $filters['status'] = '';
    }

    if (
        $filters['assignment_type'] !== ''
        && !in_allowed_values($filters['assignment_type'], monthly_contract_assignment_types())
    ) {
        $filters['assignment_type'] = '';
    }

    if (
        $filters['payment_status'] !== ''
        && !in_allowed_values($filters['payment_status'], monthly_contract_payment_statuses())
    ) {
        $filters['payment_status'] = '';
    }

    if (!in_array($filters['expiry'], ['', 'expiring', 'expired'], true)) {
        $filters['expiry'] = '';
    }

    foreach (['date_from', 'date_to'] as $dateKey) {
        if ($filters[$dateKey] !== '' && !monthly_contract_valid_date($filters[$dateKey])) {
            $filters[$dateKey] = '';
        }
    }

    if (
        $filters['date_from'] !== ''
        && $filters['date_to'] !== ''
        && $filters['date_to'] < $filters['date_from']
    ) {
        $filters['date_to'] = '';
    }

    return $filters;
}

function monthly_contract_valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

    return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
}

function monthly_contract_select_sql(): string
{
    return "SELECT
                mc.id,
                mc.contract_no,
                mc.customer_name,
                mc.customer_phone,
                mc.customer_email,
                mc.customer_address,
                mc.assignment_type,
                mc.car_id,
                mc.driver_id,
                mc.operator_id,
                mc.operator_name,
                mc.start_date,
                mc.end_date,
                mc.monthly_rate,
                mc.security_deposit,
                mc.payment_status,
                mc.status,
                mc.renewal_parent_id,
                mc.note,
                mc.created_at,
                mc.updated_at,
                c.car_type,
                c.plate_no,
                c.model_name,
                d.full_name AS driver_name,
                o.full_name AS operator_full_name,
                parent.contract_no AS renewal_parent_no
            FROM monthly_contracts AS mc
            LEFT JOIN monthly_cars AS c ON c.id = mc.car_id
            LEFT JOIN monthly_drivers AS d ON d.id = mc.driver_id
            LEFT JOIN operators AS o ON o.id = mc.operator_id
            LEFT JOIN monthly_contracts AS parent ON parent.id = mc.renewal_parent_id";
}

function monthly_contract_where(array $filters): array
{
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(
            mc.contract_no LIKE ?
            OR mc.customer_name LIKE ?
            OR mc.customer_phone LIKE ?
            OR mc.customer_email LIKE ?
            OR c.car_type LIKE ?
            OR c.plate_no LIKE ?
            OR c.model_name LIKE ?
            OR d.full_name LIKE ?
            OR mc.operator_name LIKE ?
            OR o.full_name LIKE ?
            OR mc.note LIKE ?
        )';

        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'sssssssssss';

        for ($i = 0; $i < 11; $i++) {
            $params[] = $searchValue;
        }
    }

    if ($filters['status'] !== '') {
        $where[] = 'mc.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    if ($filters['assignment_type'] !== '') {
        $where[] = 'mc.assignment_type = ?';
        $types .= 's';
        $params[] = $filters['assignment_type'];
    }

    if ($filters['payment_status'] !== '') {
        $where[] = 'mc.payment_status = ?';
        $types .= 's';
        $params[] = $filters['payment_status'];
    }

    if ($filters['date_from'] !== '') {
        $where[] = 'mc.start_date >= ?';
        $types .= 's';
        $params[] = $filters['date_from'];
    }

    if ($filters['date_to'] !== '') {
        $where[] = 'mc.end_date <= ?';
        $types .= 's';
        $params[] = $filters['date_to'];
    }

    if ($filters['expiry'] === 'expiring') {
        $where[] = "mc.status NOT IN ('Renewed', 'Cancelled')
            AND mc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    }

    if ($filters['expiry'] === 'expired') {
        $where[] = "mc.status NOT IN ('Renewed', 'Cancelled')
            AND mc.end_date < CURDATE()";
    }

    return [
        'where' => $where,
        'types' => $types,
        'params' => $params,
    ];
}

function monthly_contract_fetch(mysqli $db, array $filters, ?int $limit = null, int $offset = 0): array
{
    $contracts = [];
    $whereData = monthly_contract_where($filters);
    $sql = monthly_contract_select_sql();

    if ($whereData['where'] !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $whereData['where']);
    }

    $sql .= ' ORDER BY mc.end_date ASC, mc.id DESC';

    $types = (string) $whereData['types'];
    $params = $whereData['params'];

    if ($limit !== null) {
        $safeLimit = max(1, $limit);
        $safeOffset = max(0, $offset);
        $sql .= ' LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $safeLimit;
        $params[] = $safeOffset;
    }

    $statement = $db->prepare($sql);

    if (!$statement instanceof mysqli_stmt) {
        return $contracts;
    }

    if ($params !== []) {
        bind_statement_params($statement, $types, $params);
    }

    $statement->execute();
    $result = $statement->get_result();

    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }

    $statement->close();

    return $contracts;
}

function monthly_contract_count(mysqli $db, array $filters): int
{
    $whereData = monthly_contract_where($filters);
    $sql = 'SELECT COUNT(*) AS total
            FROM monthly_contracts AS mc
            LEFT JOIN monthly_cars AS c ON c.id = mc.car_id
            LEFT JOIN monthly_drivers AS d ON d.id = mc.driver_id
            LEFT JOIN operators AS o ON o.id = mc.operator_id';

    if ($whereData['where'] !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $whereData['where']);
    }

    $statement = $db->prepare($sql);

    if (!$statement instanceof mysqli_stmt) {
        return 0;
    }

    if ($whereData['params'] !== []) {
        bind_statement_params($statement, (string) $whereData['types'], $whereData['params']);
    }

    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc() ?: [];
    $statement->close();

    return (int) ($row['total'] ?? 0);
}

function monthly_contract_stats(mysqli $db): array
{
    $stats = [
        'total' => 0,
        'active' => 0,
        'expiring' => 0,
        'expired' => 0,
        'monthly_revenue' => 0,
    ];

    $result = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active,
            SUM(CASE
                WHEN status NOT IN ('Renewed', 'Cancelled')
                 AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                THEN 1 ELSE 0 END
            ) AS expiring,
            SUM(CASE
                WHEN status NOT IN ('Renewed', 'Cancelled')
                 AND end_date < CURDATE()
                THEN 1 ELSE 0 END
            ) AS expired,
            SUM(CASE WHEN status = 'Active' THEN monthly_rate ELSE 0 END) AS monthly_revenue
         FROM monthly_contracts"
    );

    if ($result instanceof mysqli_result) {
        $stats = array_merge($stats, $result->fetch_assoc() ?: []);
    }

    return $stats;
}

function monthly_contract_expiry_notifications(mysqli $db, int $limit = 6): array
{
    $filters = monthly_contract_default_filters();
    $filters['expiry'] = 'expiring';

    return monthly_contract_fetch($db, $filters, $limit, 0);
}

function monthly_contract_payload(array $source, array $defaults = []): array
{
    return [
        'contract_no' => old($source, 'contract_no', (string) ($defaults['contract_no'] ?? '')),
        'customer_name' => old($source, 'customer_name', (string) ($defaults['customer_name'] ?? '')),
        'customer_phone' => old($source, 'customer_phone', (string) ($defaults['customer_phone'] ?? '')),
        'customer_email' => old($source, 'customer_email', (string) ($defaults['customer_email'] ?? '')),
        'customer_address' => old($source, 'customer_address', (string) ($defaults['customer_address'] ?? '')),
        'assignment_type' => old(
            $source,
            'assignment_type',
            (string) ($defaults['assignment_type'] ?? 'Vehicle Only')
        ),
        'car_id' => old($source, 'car_id', (string) ($defaults['car_id'] ?? '')),
        'driver_id' => old($source, 'driver_id', (string) ($defaults['driver_id'] ?? '')),
        'operator_id' => old($source, 'operator_id', (string) ($defaults['operator_id'] ?? '')),
        'operator_name' => (string) ($defaults['operator_name'] ?? ''),
        'start_date' => old($source, 'start_date', (string) ($defaults['start_date'] ?? '')),
        'end_date' => old($source, 'end_date', (string) ($defaults['end_date'] ?? '')),
        'monthly_rate' => old($source, 'monthly_rate', (string) ($defaults['monthly_rate'] ?? '0.00')),
        'security_deposit' => old(
            $source,
            'security_deposit',
            (string) ($defaults['security_deposit'] ?? '0.00')
        ),
        'payment_status' => old(
            $source,
            'payment_status',
            (string) ($defaults['payment_status'] ?? 'Pending')
        ),
        'status' => old($source, 'status', (string) ($defaults['status'] ?? 'Active')),
        'note' => old($source, 'note', (string) ($defaults['note'] ?? '')),
    ];
}

function monthly_contract_validate_payload(
    mysqli $db,
    array &$contract,
    array $cars,
    array $drivers,
    array $operators,
    ?int $excludeId = null
): array {
    $errors = [];

    foreach (['customer_name', 'assignment_type', 'operator_id', 'start_date', 'end_date', 'monthly_rate', 'payment_status', 'status'] as $field) {
        if ((string) ($contract[$field] ?? '') === '') {
            $errors[] = 'Please fill in all required fields.';
            break;
        }
    }

    if ($contract['contract_no'] === '') {
        $contract['contract_no'] = monthly_contract_next_number($db, $contract['start_date']);
    }

    if (strlen((string) $contract['contract_no']) > 50) {
        $errors[] = 'Contract number must be 50 characters or fewer.';
    }

    if (!monthly_contract_valid_date((string) $contract['start_date'])) {
        $errors[] = 'Please choose a valid start date.';
    }

    if (!monthly_contract_valid_date((string) $contract['end_date'])) {
        $errors[] = 'Please choose a valid end date.';
    }

    if (
        monthly_contract_valid_date((string) $contract['start_date'])
        && monthly_contract_valid_date((string) $contract['end_date'])
        && $contract['end_date'] < $contract['start_date']
    ) {
        $errors[] = 'End date cannot be earlier than start date.';
    }

    if (
        $contract['customer_email'] !== ''
        && filter_var($contract['customer_email'], FILTER_VALIDATE_EMAIL) === false
    ) {
        $errors[] = 'Please enter a valid customer email.';
    }

    if (!in_allowed_values((string) $contract['assignment_type'], monthly_contract_assignment_types())) {
        $errors[] = 'Please choose a valid assignment type.';
    }

    if (!in_allowed_values((string) $contract['payment_status'], monthly_contract_payment_statuses())) {
        $errors[] = 'Please choose a valid payment status.';
    }

    if (!in_allowed_values((string) $contract['status'], monthly_contract_statuses())) {
        $errors[] = 'Please choose a valid contract status.';
    }

    foreach (['monthly_rate' => 'Monthly rate', 'security_deposit' => 'Security deposit'] as $field => $label) {
        if (!is_numeric($contract[$field]) || (float) $contract[$field] < 0) {
            $errors[] = $label . ' must be zero or greater.';
        }
    }

    $finalCarId = null;
    $finalDriverId = null;
    $finalOperatorId = null;
    $assignmentType = (string) $contract['assignment_type'];

    if (in_array($assignmentType, ['Vehicle Only', 'Vehicle and Driver'], true)) {
        if ($contract['car_id'] === '') {
            $errors[] = 'Please choose a vehicle for this assignment type.';
        } else {
            $carId = (int) $contract['car_id'];
            $matchedCar = find_row_by_id($cars, $carId);

            if ($matchedCar === null) {
                $errors[] = 'Please choose a valid vehicle.';
            } elseif (in_array(strtolower((string) ($matchedCar['availability_status'] ?? '')), ['maintenance', 'inactive'], true)) {
                $errors[] = 'The selected vehicle is not available for a monthly contract.';
            } else {
                $finalCarId = $carId;
            }
        }
    }

    if ($assignmentType === 'Vehicle and Driver') {
        if ($contract['driver_id'] === '') {
            $errors[] = 'Please choose a driver for this assignment type.';
        } else {
            $driverId = (int) $contract['driver_id'];
            $matchedDriver = find_row_by_id($drivers, $driverId);

            if ($matchedDriver === null) {
                $errors[] = 'Please choose a valid driver.';
            } elseif (in_array(strtolower((string) ($matchedDriver['driver_status'] ?? '')), ['leave', 'inactive'], true)) {
                $errors[] = 'The selected driver is currently on leave or inactive.';
            } else {
                $finalDriverId = $driverId;
            }
        }
    }

    if ($contract['operator_id'] !== '') {
        $operatorId = (int) $contract['operator_id'];
        $matchedOperator = find_row_by_id($operators, $operatorId);

        if ($matchedOperator === null) {
            $errors[] = 'Please choose a valid operator.';
        } else {
            $finalOperatorId = $operatorId;
            $contract['operator_name'] = trim((string) ($matchedOperator['full_name'] ?? ''));
        }
    }

    if (
        $errors === []
        && in_array((string) $contract['status'], ['Draft', 'Active'], true)
        && monthly_contract_valid_date((string) $contract['start_date'])
        && monthly_contract_valid_date((string) $contract['end_date'])
    ) {
        if ($finalCarId !== null) {
            $conflict = monthly_contract_resource_conflict(
                $db,
                'car_id',
                $finalCarId,
                (string) $contract['start_date'],
                (string) $contract['end_date'],
                $excludeId
            );

            if ($conflict !== null) {
                $errors[] = 'This vehicle is already assigned to contract '
                    . $conflict['contract_no']
                    . ' from '
                    . format_display_date($conflict['start_date'])
                    . ' to '
                    . format_display_date($conflict['end_date'])
                    . '.';
            }
        }

        if ($finalDriverId !== null) {
            $conflict = monthly_contract_resource_conflict(
                $db,
                'driver_id',
                $finalDriverId,
                (string) $contract['start_date'],
                (string) $contract['end_date'],
                $excludeId
            );

            if ($conflict !== null) {
                $errors[] = 'This driver is already assigned to contract '
                    . $conflict['contract_no']
                    . ' from '
                    . format_display_date($conflict['start_date'])
                    . ' to '
                    . format_display_date($conflict['end_date'])
                    . '.';
            }
        }
    }

    $contract['car_id'] = $finalCarId;
    $contract['driver_id'] = $finalDriverId;
    $contract['operator_id'] = $finalOperatorId;
    $contract['monthly_rate'] = number_format((float) $contract['monthly_rate'], 2, '.', '');
    $contract['security_deposit'] = number_format((float) $contract['security_deposit'], 2, '.', '');

    return $errors;
}

function monthly_contract_find(mysqli $db, int $id): ?array
{
    $sql = monthly_contract_select_sql() . ' WHERE mc.id = ? LIMIT 1';
    $statement = $db->prepare($sql);

    if (!$statement instanceof mysqli_stmt) {
        return null;
    }

    $statement->bind_param('i', $id);
    $statement->execute();
    $contract = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();

    return is_array($contract) ? $contract : null;
}

function monthly_contract_resource_conflict(
    mysqli $db,
    string $resourceColumn,
    int $resourceId,
    string $startDate,
    string $endDate,
    ?int $excludeId = null
): ?array {
    if (!in_array($resourceColumn, ['car_id', 'driver_id'], true) || $resourceId <= 0) {
        return null;
    }

    $sql = "SELECT contract_no, customer_name, start_date, end_date
            FROM monthly_contracts
            WHERE {$resourceColumn} = ?
              AND status IN ('Draft', 'Active')
              AND start_date <= ?
              AND end_date >= ?";

    $types = 'iss';
    $params = [$resourceId, $endDate, $startDate];

    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $types .= 'i';
        $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $statement = $db->prepare($sql);

    if (!$statement instanceof mysqli_stmt) {
        return null;
    }

    bind_statement_params($statement, $types, $params);
    $statement->execute();
    $conflict = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();

    return is_array($conflict) ? $conflict : null;
}

function monthly_contract_next_number(mysqli $db, ?string $date = null): string
{
    $contractDate = $date !== null && monthly_contract_valid_date($date)
        ? new DateTimeImmutable($date)
        : new DateTimeImmutable('today');

    $prefix = 'MC-' . $contractDate->format('Ym') . '-';
    $like = $prefix . '%';
    $statement = $db->prepare(
        'SELECT contract_no
         FROM monthly_contracts
         WHERE contract_no LIKE ?
         ORDER BY contract_no DESC
         LIMIT 1'
    );

    if (!$statement instanceof mysqli_stmt) {
        return $prefix . '0001';
    }

    $statement->bind_param('s', $like);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc() ?: [];
    $statement->close();

    $lastNumber = 0;
    $lastContractNo = (string) ($row['contract_no'] ?? '');

    if (str_starts_with($lastContractNo, $prefix)) {
        $lastNumber = (int) substr($lastContractNo, strlen($prefix));
    }

    return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
}
