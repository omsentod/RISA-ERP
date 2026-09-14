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
                $qty = isset($row['qty']) ? (int) $row['qty'] : 1;
                $gol = $this->clean($row['kode_gol_prod'] ?? $row['kode_golongan'] ?? null);
                if ($gol !== null && strlen($gol) === 1) {
                    $gol = '0' . $gol;
                }

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
                        defaultQuantity: $qty > 0 ? $qty : 1,
                        productGroupCode: $gol,
                        status: ProductImportRow::STATUS_INVALID,
                        errorReason: $code === null ? 'Kolom Kode kosong' : 'Kolom Nama Produk kosong',
                    );

                    continue;
                }

                $resolvedQty = $qty > 0 ? $qty : 1;
                $exactMatch = $this->findExactMatch(
                    $existingCodes[$code] ?? [],
                    $categoryName,
                    $name,
                    $spec,
                    $nie,
                    $resolvedQty,
                    $gol,
                );

                $rows[] = new ProductImportRow(
                    sheetIndex: $sheetIndex,
                    rowNumber: $rowNumber,
                    categoryName: $categoryName,
                    code: $code,
                    name: $name,
                    specification: $spec,
                    nieNumber: $nie,
                    defaultQuantity: $resolvedQty,
                    productGroupCode: $gol,
                    // Duplikat HANYA jika seluruh kolom sama persis dengan produk existing.
                    // Kode sama tapi ada kolom yang beda → NEW (dibuat sebagai produk baru).
                    status: $exactMatch !== null ? ProductImportRow::STATUS_DUPLICATE : ProductImportRow::STATUS_NEW,
                    existingData: $exactMatch,
                );
            }
        }

        return $rows;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>> daftar produk existing dikelompokkan per kode
     */
    private function loadExistingCodes(): array
    {
        return Product::query()
            ->with(['category:id,name', 'registration:id,nie_number'])
            ->get(['id', 'code', 'name', 'specification', 'default_quantity', 'product_group_code', 'product_category_id', 'registration_id'])
            ->groupBy('code')
            ->map(fn ($group) => $group->map(fn (Product $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'specification' => $p->specification,
                'category_name' => $p->category?->name,
                'nie_number' => $p->registration?->nie_number,
                'default_quantity' => $p->default_quantity,
                'product_group_code' => $p->product_group_code,
            ])->all())
            ->toArray();
    }

    /**
     * Baris dianggap duplikat hanya jika SELURUH kolom sama persis dengan salah satu produk existing berkode sama.
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<string, mixed>|null
     */
    private function findExactMatch(
        array $candidates,
        string $categoryName,
        ?string $name,
        ?string $spec,
        ?string $nie,
        int $qty,
        ?string $gol,
    ): ?array {
        foreach ($candidates as $existing) {
            if (
                $name === ($existing['name'] ?? null)
                && $spec === ($existing['specification'] ?? null)
                && $gol === ($existing['product_group_code'] ?? null)
                && $qty === (int) ($existing['default_quantity'] ?? 1)
                && $categoryName === (string) ($existing['category_name'] ?? '')
                && $this->normalizeNie($nie) === $this->normalizeNie($existing['nie_number'] ?? null)
            ) {
                return $existing;
            }
        }

        return null;
    }

    private function normalizeNie(?string $nie): string
    {
        return strtoupper(preg_replace('/[^0-9A-Z]/i', '', str_ireplace('AKD', '', (string) $nie)));
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
