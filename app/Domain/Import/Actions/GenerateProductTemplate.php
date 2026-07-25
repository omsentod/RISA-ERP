<?php

namespace App\Domain\Import\Actions;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateProductTemplate
{
    private const SHEETS = [
        'NON LOCKING' => [
            ['DHS 3.5mm', 'DHS-350-01', 'Dynamic Hip Screw 3.5mm', 'AKD 21302420095'],
            ['Recon Plate 3.5mm', 'RP-350-05H', 'Reconstruction Plate 5 hole', 'AKD 21302420095'],
        ],
        'LOCKING' => [
            ['Prox Humerus Plate', 'LPHP-L-03', 'Locking Proximal Humerus Plate Left', 'AKD 21302420151'],
            ['Distal Radius Plate', 'LDRP-R-05', 'Locking Distal Radius Plate Right', 'AKD 21302420151'],
        ],
    ];

    private const HEADERS = ['Spesifikasi', 'Kode', 'Nama Produk', 'NIE'];

    public function stream(string $filename = 'template-produk.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $sheetIndex = 0;
        foreach (self::SHEETS as $sheetName => $sampleRows) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle($sheetName);

            foreach (self::HEADERS as $col => $header) {
                $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
                $cell->setValue($header);
            }
            $sheet->getStyle('A1:D1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            foreach ($sampleRows as $rowIndex => $row) {
                foreach ($row as $col => $value) {
                    $sheet->getCellByColumnAndRow($col + 1, $rowIndex + 2)->setValue($value);
                }
            }

            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
