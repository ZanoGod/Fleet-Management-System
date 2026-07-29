<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!($db instanceof mysqli)) {
    exit('Database connection not available');
}

$filters = monthly_contract_filters($_GET);
$contracts = monthly_contract_fetch($db, $filters);

$selectedCols = isset($_GET['cols']) ? explode(',', $_GET['cols']) : [];

$columnMap = [
    'contract'          => ['header' => 'Contract No.',    'key' => 'contract_no'],
    'customer'          => ['header' => 'Customer',        'key' => 'customer_name'],
    'assignment'        => ['header' => 'Assignment',      'key' => 'assignment_type'],
    'vehicle'           => ['header' => 'Vehicle',         'callback' => fn($c) => monthly_contract_vehicle_display($c)],
    'driver'            => ['header' => 'Driver',          'callback' => fn($c) => monthly_contract_driver_display($c)],
    'operator'          => ['header' => 'Operator',        'callback' => fn($c) => monthly_contract_operator_display($c)],
    'dates'             => ['header' => 'Dates',           'callback' => fn($c) => format_display_date($c['start_date']) . ' - ' . format_display_date($c['end_date'])],
    'rate'              => ['header' => 'Monthly Rate',    'key' => 'monthly_rate'],
    'payment'           => ['header' => 'Payment',         'key' => 'payment_status'],
    'status'            => ['header' => 'Status',          'key' => 'status'],
    'guest_name'        => ['header' => 'Guest Name',      'key' => 'guest_name'],
    'contact_person'    => ['header' => 'Contact Person',  'key' => 'contact_person'],
    'customer_phone'    => ['header' => 'Phone',           'key' => 'customer_phone'],
    'customer_email'    => ['header' => 'Email',           'key' => 'customer_email'],
    'customer_address'  => ['header' => 'Address',         'key' => 'customer_address'],
    'even_odd'          => ['header' => 'Even/Odd',        'key' => 'even_odd'],
    'operation_sheet'   => ['header' => 'Operation Sheet', 'key' => 'operation_sheet'],
    'rental_currency'   => ['header' => 'Currency',        'key' => 'rental_currency'],
    'security_deposit'  => ['header' => 'Deposit',         'key' => 'security_deposit'],
    'off_day'           => ['header' => 'Off Day',         'key' => 'off_day'],
    'service_hour'      => ['header' => 'Service Hour',    'key' => 'service_hour'],
    'insurance_company' => ['header' => 'Insurance Co.',   'key' => 'insurance_company'],
    'insurance_price'   => ['header' => 'Insurance Fee',   'key' => 'insurance_price'],
    'note'              => ['header' => 'Note',            'key' => 'note'],
];

$defaultCols = ['contract', 'customer', 'assignment', 'vehicle', 'driver', 'operator', 'dates', 'rate', 'payment', 'status'];
$activeCols = empty($selectedCols) ? $defaultCols : array_intersect(array_keys($columnMap), $selectedCols);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Monthly Contracts');

$headers = array_merge(['No.'], array_map(fn($col) => $columnMap[$col]['header'], $activeCols));

$row = 5;
$colIdx = 1;

foreach ($headers as $header) {
    $sheet->setCellValue([$colIdx, $row], $header);
    $colIdx++;
}

$lastColLetter = Coordinate::stringFromColumnIndex(count($headers));

$sheet->mergeCells("A1:{$lastColLetter}1");
$sheet->setCellValue('A1', 'GSS Fleet Management');
$sheet->mergeCells("A2:{$lastColLetter}2");
$sheet->setCellValue('A2', 'Monthly Contract Report');
$sheet->mergeCells("A3:{$lastColLetter}3");
$sheet->setCellValue('A3', 'Generated : ' . date('d M Y H:i'));

$sheet->getStyle("A5:{$lastColLetter}5")->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 11,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4F200D'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

$row = 6;
$number = 1;

foreach ($contracts as $contract) {
    $cIdx = 1;
    $sheet->setCellValue([$cIdx++, $row], $number++);

    foreach ($activeCols as $colKey) {
        $colDef = $columnMap[$colKey];
        $val = '';
        if (isset($colDef['callback'])) {
            $val = ($colDef['callback'])($contract);
        } elseif (isset($colDef['key'])) {
            $val = $contract[$colDef['key']] ?? '';
        }
        $sheet->setCellValue([$cIdx++, $row], $val);
    }
    $row++;
}

$lastRow = max(5, $row - 1);

$sheet->getStyle("A5:{$lastColLetter}{$lastRow}")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'E8DCCF'],
        ],
    ],
]);

$sheet->getStyle("A5:{$lastColLetter}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle("A5:{$lastColLetter}{$lastRow}")->getAlignment()->setWrapText(true);

$sheet->freezePane('A6');

$summaryRow = $lastRow + 2;
$sheet->setCellValue('A' . $summaryRow, 'Total Records : ' . count($contracts));
$sheet->mergeCells("A{$summaryRow}:{$lastColLetter}{$summaryRow}");
$sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A3')->getFont()->setItalic(true);

$fileName = 'Monthly_Contract_Report_' . date('Ymd_His') . '.xlsx';

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;