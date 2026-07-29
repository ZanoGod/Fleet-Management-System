<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!($db instanceof mysqli)) {
    exit('Database connection not available');
}

$filters = monthly_contract_filters($_GET);
$contracts = monthly_contract_fetch($db, $filters);

$selectedCols = isset($_GET['cols']) ? explode(',', $_GET['cols']) : [];

$columnMap = [
    'contract'          => ['header' => 'Contract',        'key' => 'contract_no'],
    'customer'          => ['header' => 'Customer',        'key' => 'customer_name'],
    'assignment'        => ['header' => 'Assignment',      'key' => 'assignment_type'],
    'vehicle'           => ['header' => 'Vehicle',         'callback' => fn($c) => e(monthly_contract_vehicle_display($c))],
    'driver'            => ['header' => 'Driver',          'callback' => fn($c) => e(monthly_contract_driver_display($c))],
    'operator'          => ['header' => 'Operator',        'callback' => fn($c) => e(monthly_contract_operator_display($c))],
    'dates'             => ['header' => 'Dates',           'callback' => fn($c) => e(format_display_date($c['start_date'])) . '<br>' . e(format_display_date($c['end_date']))],
    'rate'              => ['header' => 'Rate',            'callback' => fn($c) => e(monthly_contract_money($c['monthly_rate']))],
    'payment'           => ['header' => 'Payment',         'key' => 'payment_status'],
    'status'            => ['header' => 'Status',          'key' => 'status'],
    'guest_name'        => ['header' => 'Guest Name',      'key' => 'guest_name'],
    'contact_person'    => ['header' => 'Contact Person',  'key' => 'contact_person'],
    'customer_phone'    => ['header' => 'Phone',           'key' => 'customer_phone'],
    'customer_email'    => ['header' => 'Email',           'key' => 'customer_email'],
    'customer_address'  => ['header' => 'Address',         'key' => 'customer_address'],
    'even_odd'          => ['header' => 'Even/Odd',        'key' => 'even_odd'],
    'operation_sheet'   => ['header' => 'Op Sheet',        'key' => 'operation_sheet'],
    'rental_currency'   => ['header' => 'Currency',        'key' => 'rental_currency'],
    'security_deposit'  => ['header' => 'Deposit',         'callback' => fn($c) => e(monthly_contract_money($c['security_deposit'] ?? 0))],
    'off_day'           => ['header' => 'Off Day',         'key' => 'off_day'],
    'service_hour'      => ['header' => 'Service Hour',    'key' => 'service_hour'],
    'insurance_company' => ['header' => 'Insurance Co.',   'key' => 'insurance_company'],
    'insurance_price'   => ['header' => 'Insurance Fee',   'callback' => fn($c) => e(monthly_contract_money($c['insurance_price'] ?? 0))],
    'note'              => ['header' => 'Note',            'key' => 'note'],
];

$defaultCols = ['contract', 'customer', 'assignment', 'vehicle', 'driver', 'operator', 'dates', 'rate', 'payment', 'status'];
$activeCols = empty($selectedCols) ? $defaultCols : array_intersect(array_keys($columnMap), $selectedCols);

$thHtml = '<th>No.</th>';
foreach ($activeCols as $colKey) {
    $thHtml .= '<th>' . e($columnMap[$colKey]['header']) . '</th>';
}

$rows = '';
$number = 1;

foreach ($contracts as $contract) {
    $rows .= '<tr><td class="center">' . e((string) $number++) . '</td>';
    foreach ($activeCols as $colKey) {
        $colDef = $columnMap[$colKey];
        $val = '';
        if (isset($colDef['callback'])) {
            $val = ($colDef['callback'])($contract);
        } elseif (isset($colDef['key'])) {
            $val = e((string)($contract[$colDef['key']] ?? '-'));
        }
        $rows .= '<td>' . $val . '</td>';
    }
    $rows .= '</tr>';
}

$colSpan = count($activeCols) + 1;
if ($rows === '') {
    $rows = '<tr><td colspan="' . $colSpan . '" class="center empty">No monthly contracts found.</td></tr>';
}

$html = '
<html>
<head>
<style>
body { font-family: DejaVu Sans; font-size: 8px; color: #333; background: #FFFDF8; }
h1 { text-align: center; color: #4F200D; margin: 0; font-size: 20px; }
.title { text-align: center; margin-bottom: 12px; }
.title p { margin: 2px 0 0; color: #8F735F; }
table { width: 100%; border-collapse: collapse; }
th { background: #4F200D; color: #fff; padding: 5px 4px; border: 1px solid #E5D6C8; font-size: 8px; }
td { border: 1px solid #E5D6C8; padding: 4px; vertical-align: top; word-break: break-word; }
tr:nth-child(even) { background: #FFF8F0; }
.center { text-align: center; }
.empty { padding: 12px; color: #8F735F; }
</style>
</head>
<body>
    <div class="title">
        <h1>Monthly Contract Report</h1>
        <p>Generated : ' . e(date('d M Y H:i')) . ' | Total Records : ' . e((string) count($contracts)) . '</p>
    </div>

    <table>
        <tr>' . $thHtml . '</tr>
        ' . $rows . '
    </table>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$pdf->stream('Monthly_Contract_Report_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);

exit;