<?php

namespace App\Domain\Import\Actions;

use App\Domain\Product\Models\ProductCategory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateProductTemplate
{
    private const HEADERS = ['Spesifikasi', 'Kode', 'Nama Produk', 'NIE', 'QTY', 'Kode Golongan'];

    public function stream(string $filename = 'template-produk.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $categories = ProductCategory::pluck('name')->toArray();
        if (empty($categories)) {
            $categories = ['NON LOCKING', 'LOCKING'];
        }

        $sheetIndex = 0;
        foreach ($categories as $categoryName) {
            $sheet = $spreadsheet->createSheet($sheetIndex++);
            $sheet->setTitle($categoryName);

            foreach (self::HEADERS as $col => $header) {
                $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
                $cell->setValue($header);
            }
            $sheet->getStyle('A1:F1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sampleRows = [
                ['Contoh Spesifikasi 1', 'SKU-001', 'Nama Produk Contoh 1', '21302420095', 1, '01'],
                ['Contoh Spesifikasi 2', 'SKU-002', 'Nama Produk Contoh 2', '21302420095', 1, '03'],
            ];

            foreach ($sampleRows as $rowIndex => $row) {
                foreach ($row as $col => $value) {
                    $sheet->getCellByColumnAndRow($col + 1, $rowIndex + 2)->setValue($value);
                }
            }

            foreach (range('A', 'F') as $col) {
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
