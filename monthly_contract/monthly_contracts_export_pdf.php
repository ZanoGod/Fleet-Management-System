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

$rows = '';
$number = 1;

foreach ($contracts as $contract) {
    $rows .= '
        <tr>
            <td class="center">' . e((string) $number++) . '</td>
            <td><strong>' . e((string) $contract['contract_no']) . '</strong></td>
            <td class="wide">' . e((string) $contract['customer_name']) . '<br><span>' . e((string) ($contract['customer_phone'] ?: $contract['customer_email'] ?: '-')) . '</span></td>
            <td>' . e((string) $contract['assignment_type']) . '</td>
            <td class="wide">' . e(monthly_contract_vehicle_display($contract)) . '</td>
            <td class="wide">' . e(monthly_contract_driver_display($contract)) . '</td>
            <td class="wide">' . e(monthly_contract_operator_display($contract)) . '</td>
            <td class="center">' . e(format_display_date($contract['start_date'])) . '<br>' . e(format_display_date($contract['end_date'])) . '</td>
            <td class="money">' . e(monthly_contract_money($contract['monthly_rate'])) . '</td>
            <td class="center">' . e((string) $contract['payment_status']) . '</td>
            <td class="center">' . e((string) $contract['status']) . '</td>
        </tr>';
}

if ($rows === '') {
    $rows = '<tr><td colspan="11" class="center empty">No monthly contracts found.</td></tr>';
}

$html = '
<html>
<head>
<style>
body {
    font-family: DejaVu Sans;
    font-size: 10px;
    color: #333;
    background: #FFFDF8;
}

h1 {
    text-align: center;
    color: #4F200D;
    margin: 0;
    font-size: 25px;
}

.title {
    text-align: center;
    margin-bottom: 18px;
}

.title p {
    margin: 4px 0 0;
    color: #8F735F;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #4F200D;
    color: #fff;
    padding: 8px 6px;
    border: 1px solid #E5D6C8;
}

td {
    border: 1px solid #E5D6C8;
    padding: 6px;
    vertical-align: top;
    word-break: break-word;
}

tr:nth-child(even) {
    background: #FFF8F0;
}

span {
    color: #8F735F;
}

.center {
    text-align: center;
}

.money {
    text-align: right;
    white-space: nowrap;
}

.wide {
    white-space: normal;
}

.empty {
    padding: 18px;
    color: #8F735F;
}
</style>
</head>
<body>
    <div class="title">
        <h1>Monthly Contract Report</h1>
        <p>Generated : ' . e(date('d M Y H:i')) . '</p>
        <p>Total Records : ' . e((string) count($contracts)) . '</p>
    </div>

    <table>
        <tr>
            <th style="width:4%">No.</th>
            <th style="width:11%">Contract</th>
            <th style="width:18%">Customer</th>
            <th style="width:12%">Assignment</th>
            <th style="width:17%">Vehicle</th>
            <th style="width:12%">Driver</th>
            <th style="width:12%">Operator</th>
            <th style="width:10%">Dates</th>
            <th style="width:8%">Rate</th>
            <th style="width:8%">Payment</th>
            <th style="width:8%">Status</th>
        </tr>
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
$pdf->stream(
    'Monthly_Contract_Report_' . date('Ymd_His') . '.pdf',
    ['Attachment' => true]
);

exit;
