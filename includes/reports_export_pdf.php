<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$filters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'start_date' => trim((string)($_GET['start_date'] ?? date('Y-m-01'))),
    'end_date' => trim((string)($_GET['end_date'] ?? date('Y-m-t')))
];

$dateFrom = $filters['start_date'];
$dateTo   = $filters['end_date'];

$reportBookings = [];
$where = [];
$params = [];
$types = '';

if ($filters['search'] !== '') {

    $where[] = '(
        b.guest_company_name LIKE ?
        OR b.operator_name LIKE ?
        OR o.full_name LIKE ?
        OR c.car_type LIKE ?
        OR c.plate_no LIKE ?
        OR c2.car_type LIKE ?
        OR c2.plate_no LIKE ?
        OR b.custom_car_name LIKE ?
        OR d.full_name LIKE ?
        OR b.custom_driver_name LIKE ?
        OR b.even_odd LIKE ?
        OR b.remark LIKE ?
    )';

    $search = '%' . $filters['search'] . '%';

    $types .= 'ssssssssssss';

    array_push(
        $params,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search
    );
}

if ($filters['status'] !== '') {

    $where[] = 'b.status = ?';

    $types .= 's';

    $params[] = $filters['status'];

}

$where[] = '(b.start_date<=? AND b.end_date>=?)';

$types .= 'ss';

$params[] = $dateTo;
$params[] = $dateFrom;

$sql = "

SELECT

b.id,
b.guest_company_name,
b.operator_name,
b.custom_car_name,
b.custom_driver_name,
b.secondary_car_id,
b.even_odd,
b.start_date,
b.end_date,
b.status,
b.remark,

c.car_type,
c.plate_no,

c2.car_type AS secondary_car_type,
c2.plate_no AS secondary_plate_no,

d.full_name AS driver_name,

o.full_name AS operator_full_name

FROM bookings b

LEFT JOIN cars c
ON c.id=b.car_id

LEFT JOIN cars c2
ON c2.id=b.secondary_car_id

LEFT JOIN drivers d
ON d.id=b.driver_id

LEFT JOIN operators o
ON o.id=b.operator_id

";

if ($where) {

    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY b.start_date DESC,b.id DESC";

if (!($db instanceof mysqli)) {
    throw new RuntimeException('Database connection is not available.');
}

$stmt = $db->prepare($sql);

if ($params) {

    bind_statement_params($stmt, $types, $params);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $reportBookings[] = $row;
}

$stmt->close();

$html = '
<html>

<head>

<style>

body{
    font-family: DejaVu Sans;
    font-size:10px;
    color:#333;
    background:#FFFDF8;
}

h1{
    text-align:center;
    color:#4F200D;
    margin:0;
    font-size:28px;
}

.subtitle{

    text-align:center;

    color:#8F735F;

    font-size:14px;

    margin-bottom:20px;

}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#4F200D;
    color:white;
padding:10px 8px;  
font-size:10px;
border:1px solid #E5D6C8;
}

tr:nth-child(even){
    background:#FFF8F0;
}

td{
    border:1px solid #E5D6C8;
    padding:6px;
    white-space:nowrap;
    font-size:10px;
}

.guest,
.car,
.operator,
.driver,
.remark{

    white-space:normal;

}
.title{

    text-align:center;

    margin-bottom:20px;

}

.info{

    margin-bottom:15px;

}

.status{

    text-align:center;

}

.no{
    width:5%;
}

.guest{

    width:21%;

}

.car{

    width:20%;

}

.operator{

    width:14%;

}

.driver{

    width:14%;

}

.remark{

    width:18%;

}

.badge{

    display:inline-block;

    padding:4px 10px;

    border-radius:20px;

    font-weight:bold;

}
    .pending{

    background:#FFE7C2;

    color:#9A4B00;

}
    .confirm{

    background:#EAF6EC;

    color:#2E7D32;

}

.cancelled{

    background:#FDECEC;

    color:#B3261E;

}

.nowrap{

    white-space:nowrap;
    text-align:center;

}
    
td{

    border:1px solid #E5D6C8;
    padding:6px;

    vertical-align:top;

    word-break:break-word;
    overflow-wrap:break-word;
    word-wrap:break-word;

}

.center{
    text-align:center;
    vertical-align:middle;
}
    .completed{

    background:#E8F2FF;

    color:#1565C0;

}
</style>

</head>

<body>

<div class="title">

<h1>Booking Report</h1>

<p>

Report Period :

' . format_display_date($dateFrom) . '

-

' . format_display_date($dateTo) . '

</p>

<p>

Generated :

' . date('d M Y H:i') . '

</p>

</div>

<table>



<tr>

<th style="width:4%">No.</th>

<th style="width:21%">Guest / Company</th>

<th style="width:20%">Car</th>

<th style="width:14%">Operator</th>

<th style="width:14%">Driver</th>

<th style="width:5%">E/O</th>

<th style="width:8%">Start</th>

<th style="width:8%">End</th>

<th style="width:8%">Status</th>

<th style="width:18%">Remark</th>

</tr>

';

$no = 1;
foreach ($reportBookings as $booking) {

    $html .= '

<tr>

<td class="center">'.$no++.'</td>

<td class="guest">'.e($booking['guest_company_name']).'</td>

<td class="car">'.booking_car_display($booking).'</td>

<td class="operator">'.booking_operator_display($booking).'</td>

<td class="driver">'.booking_driver_display($booking).'</td>

<td class="nowrap">'.e($booking['even_odd']).'</td>

<td class="nowrap">'.format_display_date($booking['start_date']).'</td>

<td class="nowrap">'.format_display_date($booking['end_date']).'</td>

<td class="nowrap">'.e($booking['status']).'</td>

<td class="remark">'.e($booking['remark']).'</td>

</tr>

';
}

$html .= '

</table>

<br>

<p>

Total Records :

' . count($reportBookings) . '

</p>

</body>

</html>

';


$options = new Options();

$options->set('isRemoteEnabled', true);

$options->set('defaultFont', 'DejaVu Sans');

$pdf = new Dompdf($options);

$pdf->loadHtml($html);

$pdf->setPaper('A4', 'landscape');

$pdf->render();

$pdf->stream(

    'Booking_Report_' . $dateFrom . '_to_' . $dateTo . '.pdf',

    [
        'Attachment' => true
    ]

);

exit;
