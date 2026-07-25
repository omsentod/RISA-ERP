<?php

namespace App\Domain\Import\Parsers;

use App\Domain\Import\Data\ProductImportRow;
use App\Domain\Product\Models\Product;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductImportParser
{
    /**
     * @return array<int, ProductImportRow>
     */
    public function parse(string $absolutePath): array
    {
        $sheets = Excel::toArray(new class implements WithHeadingRow
        {
            public function headingRow(): int
            {
                return 1;
            }
        }, $absolutePath);

        $sheetNames = IOFactory::load($absolutePath)->getSheetNames();

        $rows = [];
        $existingCodes = $this->loadExistingCodes();

        foreach ($sheets as $sheetIndex => $sheetRows) {
            $categoryName = $sheetNames[$sheetIndex] ?? ('Sheet ' . ($sheetIndex + 1));

            foreach ($sheetRows as $index => $row) {
                $rowNumber = $index + 2;
                $code = $this->clean($row['kode'] ?? null);
                $name = $this->clean($row['nama_produk'] ?? null);
                $spec = $this->clean($row['spesifikasi'] ?? null);
                $nie = $this->clean($row['nie'] ?? null);

                if ($code === null && $name === null && $spec === null && $nie === null) {
                    continue;
                }

                if ($code === null || $name === null) {
                    $rows[] = new ProductImportRow(
                        sheetIndex: $sheetIndex,
                        rowNumber: $rowNumber,
                        categoryName: $categoryName,
                        code: $code,
                        name: $name,
                        specification: $spec,
                        nieNumber: $nie,
                        status: ProductImportRow::STATUS_INVALID,
                        errorReason: $code === null ? 'Kolom Kode kosong' : 'Kolom Nama Produk kosong',
                    );

                    continue;
                }

                $existing = $existingCodes[$code] ?? null;

                if ($existing !== null) {
                    $rows[] = new ProductImportRow(
                        sheetIndex: $sheetIndex,
                        rowNumber: $rowNumber,
                        categoryName: $categoryName,
                        code: $code,
                        name: $name,
                        specification: $spec,
                        nieNumber: $nie,
                        status: ProductImportRow::STATUS_DUPLICATE,
                        existingData: $existing,
                    );

                    continue;
                }

                $rows[] = new ProductImportRow(
                    sheetIndex: $sheetIndex,
                    rowNumber: $rowNumber,
                    categoryName: $categoryName,
                    code: $code,
                    name: $name,
                    specification: $spec,
                    nieNumber: $nie,
                    status: ProductImportRow::STATUS_NEW,
                );
            }
        }

        return $rows;
    }

    private function loadExistingCodes(): array
    {
        return Product::query()
            ->with(['category:id,name', 'registration:id,nie_number'])
            ->get(['id', 'code', 'name', 'specification', 'product_category_id', 'registration_id'])
            ->keyBy('code')
            ->map(fn (Product $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'specification' => $p->specification,
                'category_name' => $p->category?->name,
                'nie_number' => $p->registration?->nie_number,
            ])
            ->toArray();
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
