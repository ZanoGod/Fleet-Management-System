<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$filters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'start_date' => trim((string)($_GET['start_date'] ?? date('Y-m-01'))),
    'end_date' => trim((string)($_GET['end_date'] ?? date('Y-m-t')))
];

$dateFrom = null;
$dateTo = null;

try {

    if ($filters['start_date'] === '' || $filters['end_date'] === '') {

        throw new Exception('Invalid Date');

    }

    $startDate = new DateTimeImmutable($filters['start_date']);
    $endDate   = new DateTimeImmutable($filters['end_date']);

    if ($endDate < $startDate) {

        throw new Exception('Invalid Date');

    }

    $dateFrom = $startDate->format('Y-m-d');
    $dateTo   = $endDate->format('Y-m-d');

} catch(Throwable $e){

    exit('Invalid Date');

}

$reportBookings=[];

$where=[];

$params=[];

$types='';

if (!empty($filters['search'])) {

    $where[]='(

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

    $search='%'.$filters['search'].'%';

    $types.='ssssssssssss';

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

$where[]='(b.start_date<=? AND b.end_date>=?)';

$types.='ss';

$params[]=$dateTo;

$params[]=$dateFrom;

$sql="

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

if($where){

    $sql.=" WHERE ".implode(' AND ',$where);

}

$sql.=" ORDER BY b.start_date DESC,b.id DESC";

$stmt=null;
if (!isset($db) || !($db instanceof mysqli)) {
    exit('Database connection not available');
}

$stmt = $db->prepare($sql);

if($params){

    bind_statement_params($stmt,$types,$params);

}

$stmt->execute();

$result=$stmt->get_result();

while($row=$result->fetch_assoc()){

    $reportBookings[]=$row;

}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Create Excel
|--------------------------------------------------------------------------
*/

$spreadsheet=new Spreadsheet();

$sheet=$spreadsheet->getActiveSheet();

$sheet->setTitle('Booking Report');

$sheet->mergeCells('A1:J1');

$sheet->setCellValue('A1','GSS Fleet Management');

$sheet->mergeCells('A2:J2');

$sheet->setCellValue('A2','Booking Report');

$sheet->mergeCells('A3:J3');

$sheet->setCellValue(

    'A3',

    'Report Period : '

    .format_display_date($dateFrom)

    .' - '

    .format_display_date($dateTo)

);

$row=5;

$headers = [
    'No.',
    'Guest / Company',
    'Car',
    'Operator',
    'Driver',
    'Even/Odd',
    'Start Date',
    'End Date',
    'Status',
    'Remark'
];

$col='A';

foreach($headers as $header){

    $sheet->setCellValue($col.$row,$header);

    $col++;

}

/*
|--------------------------------------------------------------------------
| Header Style
|--------------------------------------------------------------------------
*/

$sheet->getStyle('A5:J5')->applyFromArray([
   
    'font'=>[
    'bold'=>true,
    'size'=>12,
    'color'=>[
        'rgb'=>'FFFFFF'
    ]
],
    'fill'=>[
        'fillType'=>Fill::FILL_SOLID,
        'startColor'=>[
            'rgb'=>'4F200D'
        ]
    ],
    'alignment'=>[
        'horizontal'=>Alignment::HORIZONTAL_CENTER,
        'vertical'=>Alignment::VERTICAL_CENTER
    ]

]);

/*
|--------------------------------------------------------------------------
| Booking Data
|--------------------------------------------------------------------------
*/

$row = 6;
$no = 1;
foreach($reportBookings as $booking){
    $sheet->setCellValue('A'.$row,$no++);
    $sheet->setCellValue('B'.$row,$booking['guest_company_name']);
    $sheet->setCellValue('C'.$row,booking_car_display($booking));
    $sheet->setCellValue('D'.$row,booking_operator_display($booking));
    $sheet->setCellValue('E'.$row,booking_driver_display($booking));
    $sheet->setCellValue('F'.$row,$booking['even_odd']);
    $sheet->setCellValue('G'.$row,format_display_date($booking['start_date']));
    $sheet->setCellValue('H'.$row,format_display_date($booking['end_date']));
    $sheet->setCellValue('I'.$row,$booking['status']);
    $sheet->setCellValue('J'.$row,$booking['remark']);
    $row++;
}

/*
|--------------------------------------------------------------------------
| Borders
|--------------------------------------------------------------------------
*/

$lastRow = $row - 1;

$sheet->getStyle(
    'A5:J'.$lastRow
)->applyFromArray([

    'borders'=>[
    'allBorders'=>[
        'borderStyle'=>Border::BORDER_THIN,
        'color'=>[
            'rgb'=>'E8DCCF'
        ]
    ]
]

]);

/*
|--------------------------------------------------------------------------
| Alignment
|--------------------------------------------------------------------------
*/

$sheet->getStyle(
    'A5:J'.$lastRow
)->getAlignment()->setVertical(
    Alignment::VERTICAL_CENTER
);

$sheet->getStyle("A6:A{$lastRow}")
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle("F6:I{$lastRow}")
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);


/*
|--------------------------------------------------------------------------
| Freeze Header
|--------------------------------------------------------------------------
*/

$sheet->freezePane('A6');

/*
|--------------------------------------------------------------------------
| Auto Filter
|--------------------------------------------------------------------------
*/

$sheet->setAutoFilter(
    'A5:J'.$lastRow
);

/*
|--------------------------------------------------------------------------
| Auto Width
|--------------------------------------------------------------------------
*/

foreach(range('A','J') as $column){

    $sheet
        ->getColumnDimension($column);
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(35);
$sheet->getColumnDimension('C')->setWidth(38);
$sheet->getColumnDimension('D')->setWidth(22);
$sheet->getColumnDimension('E')->setWidth(24);
$sheet->getColumnDimension('F')->setWidth(12);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(18);
$sheet->getColumnDimension('I')->setWidth(14);
$sheet->getColumnDimension('J')->setWidth(55);
}

/*
|--------------------------------------------------------------------------
| Wrap Text
|--------------------------------------------------------------------------
*/

$sheet->getStyle(
    'A5:J'.$lastRow
)->getAlignment()->setWrapText(true);

/*
|--------------------------------------------------------------------------
| Report Information
|--------------------------------------------------------------------------
*/

$sheet->setCellValue(
    'A4',
    'Generated : '.date('d M Y H:i')
);

$sheet->mergeCells('A4:J4');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);

$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(15);

$sheet->getStyle('A3')->getFont()->setItalic(true);

$sheet->getStyle('A4')->getFont()->setItalic(true);

/*
|--------------------------------------------------------------------------
| Status Color
|--------------------------------------------------------------------------
*/


for ($i = 6; $i <= $lastRow; $i++) {

    
$status = strtolower(trim((string)$sheet->getCell('I'.$i)->getValue()));
    $color = 'FFFFFF';

    switch($status){

    case 'pending':
        $bg='FFE7C2';
        $font='9A4B00';
        break;

    case 'confirm':
        $bg='EAF6EC';
        $font='2E7D32';
        break;

    case 'completed':
        $bg='E8F2FF';
        $font='1565C0';
        break;

    case 'cancelled':
        $bg='FDECEC';
        $font='B3261E';
        break;
}

    $sheet->getStyle('I' . $i)->applyFromArray([

        'fill' => [

            'fillType' => Fill::FILL_SOLID,

            'startColor' => [

                'rgb' => str_replace('#', '', $color)

            ]

        ],

        'font' => [

            'bold' => true

        ],

        'alignment' => [

            'horizontal' => Alignment::HORIZONTAL_CENTER

        ]

    ]);

}


/*
|--------------------------------------------------------------------------
| Alternate Row Color
|--------------------------------------------------------------------------
*/

for ($i = 6; $i <= $lastRow; $i++) {

    if ($i % 2 == 0) {

        $sheet->getStyle("A{$i}:J{$i}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('FFF9F0');

    }

}


/*
|--------------------------------------------------------------------------
| Total Record
|--------------------------------------------------------------------------
*/

$row = $lastRow + 2;

$sheet->setCellValue(
    'A' . $row,
    'Total Records : ' . count($reportBookings)
);

$sheet->mergeCells(
    'A' . $row . ':J' . $row
);

$sheet->getStyle(
    'A' . $row
)->getFont()->setBold(true);

$row++;

$sheet->setCellValue(
    'A' . $row,
    'Generated by GSS Fleet Management System'
);

$sheet->mergeCells(
    'A' . $row . ':J' . $row
);

$sheet->getStyle(
    'A' . $row
)->getFont()->setItalic(true);



/*
|--------------------------------------------------------------------------
| Filename
|--------------------------------------------------------------------------
*/

$fileName = sprintf(
    'Booking_Report_%s_to_%s.xlsx',
    $dateFrom,
    $dateTo
);


/*
|--------------------------------------------------------------------------
| Download
|--------------------------------------------------------------------------
*/

while (ob_get_level()) {
    ob_end_clean();
}

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $fileName . '"'
);

header(
    'Cache-Control: max-age=0'
);

header(
    'Expires: 0'
);

header(
    'Pragma: public'
);

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;