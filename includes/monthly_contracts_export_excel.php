<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/monthly-contracts-data.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!($db instanceof mysqli)) {
    exit('Database connection not available');
}

$filters = monthly_contract_filters($_GET);
$contracts = monthly_contract_fetch($db, $filters);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Monthly Contracts');

$sheet->mergeCells('A1:P1');
$sheet->setCellValue('A1', 'GSS Fleet Management');
$sheet->mergeCells('A2:P2');
$sheet->setCellValue('A2', 'Monthly Contract Report');
$sheet->mergeCells('A3:P3');
$sheet->setCellValue('A3', 'Generated : ' . date('d M Y H:i'));

$headers = [
    'No.',
    'Contract No.',
    'Customer',
    'Phone',
    'Email',
    'Assignment',
    'Vehicle',
    'Driver',
    'Operator',
    'Start Date',
    'End Date',
    'Monthly Rate',
    'Deposit',
    'Payment',
    'Status',
    'Note',
];

$row = 5;
$column = 'A';

foreach ($headers as $header) {
    $sheet->setCellValue($column . $row, $header);
    $column++;
}

$sheet->getStyle('A5:P5')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 12,
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
    $sheet->setCellValue('A' . $row, $number++);
    $sheet->setCellValue('B' . $row, $contract['contract_no']);
    $sheet->setCellValue('C' . $row, $contract['customer_name']);
    $sheet->setCellValue('D' . $row, $contract['customer_phone']);
    $sheet->setCellValue('E' . $row, $contract['customer_email']);
    $sheet->setCellValue('F' . $row, $contract['assignment_type']);
    $sheet->setCellValue('G' . $row, monthly_contract_vehicle_display($contract));
    $sheet->setCellValue('H' . $row, monthly_contract_driver_display($contract));
    $sheet->setCellValue('I' . $row, monthly_contract_operator_display($contract));
    $sheet->setCellValue('J' . $row, format_display_date($contract['start_date']));
    $sheet->setCellValue('K' . $row, format_display_date($contract['end_date']));
    $sheet->setCellValue('L' . $row, (float) $contract['monthly_rate']);
    $sheet->setCellValue('M' . $row, (float) $contract['security_deposit']);
    $sheet->setCellValue('N' . $row, $contract['payment_status']);
    $sheet->setCellValue('O' . $row, $contract['status']);
    $sheet->setCellValue('P' . $row, $contract['note']);
    $row++;
}

$lastRow = max(5, $row - 1);

$sheet->getStyle('A5:P' . $lastRow)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'E8DCCF'],
        ],
    ],
]);

$sheet->getStyle('A5:P' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('A5:P' . $lastRow)->getAlignment()->setWrapText(true);

if ($lastRow >= 6) {
    $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J6:O' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('L6:M' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
}

$sheet->freezePane('A6');
$sheet->setAutoFilter('A5:P' . $lastRow);

$widths = [
    'A' => 8,
    'B' => 18,
    'C' => 30,
    'D' => 18,
    'E' => 28,
    'F' => 20,
    'G' => 34,
    'H' => 24,
    'I' => 24,
    'J' => 16,
    'K' => 16,
    'L' => 16,
    'M' => 16,
    'N' => 14,
    'O' => 14,
    'P' => 45,
];

foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

if ($lastRow >= 6) {
    for ($i = 6; $i <= $lastRow; $i++) {
        if ($i % 2 === 0) {
            $sheet->getStyle("A{$i}:P{$i}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('FFF9F0');
        }
    }
}

$summaryRow = $lastRow + 2;
$sheet->setCellValue('A' . $summaryRow, 'Total Records : ' . count($contracts));
$sheet->mergeCells('A' . $summaryRow . ':P' . $summaryRow);
$sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(15);
$sheet->getStyle('A3')->getFont()->setItalic(true);

$fileName = 'Monthly_Contract_Report_' . date('Ymd_His') . '.xlsx';

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
