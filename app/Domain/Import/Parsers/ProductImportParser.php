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
                $isCustom = $this->parseBoolean($this->clean($row['custom'] ?? $row['produk_custom'] ?? $row['is_custom'] ?? null));

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
                        isCustom: $isCustom,
                        status: ProductImportRow::STATUS_INVALID,
                        errorReason: $code === null ? 'Kolom Kode kosong' : 'Kolom Nama Produk kosong',
                    );

                    continue;
                }

                $resolvedQty = $qty > 0 ? $qty : 1;
                $targetMatch = $this->findTargetMatch(
                    $existingCodes[$code] ?? [],
                    $name,
                    $spec,
                    $isCustom
                );

                $status = ProductImportRow::STATUS_NEW;
                if ($targetMatch !== null) {
                    if ($this->isIdentical($targetMatch, $categoryName, $name, $spec, $nie, $resolvedQty, $gol, $isCustom)) {
                        $status = ProductImportRow::STATUS_DUPLICATE;
                    } else {
                        $status = ProductImportRow::STATUS_UPDATE;
                    }
                }

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
                    isCustom: $isCustom,
                    status: $status,
                    existingData: $targetMatch,
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
        return Product::withTrashed()
            ->with(['category:id,name', 'registration:id,nie_number'])
            ->get(['id', 'code', 'name', 'specification', 'default_quantity', 'is_custom', 'product_group_code', 'product_category_id', 'registration_id', 'deleted_at'])
            ->groupBy('code')
            ->map(fn ($group) => $group->map(fn (Product $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'specification' => $p->specification,
                'category_name' => $p->category?->name,
                'nie_number' => $p->registration?->nie_number,
                'default_quantity' => $p->default_quantity,
                'is_custom' => (bool) $p->is_custom,
                'product_group_code' => $p->product_group_code,
                'deleted_at' => $p->deleted_at,
            ])->all())
            ->toArray();
    }

    /**
     * Mencari target produk yang akan di-update (berdasarkan is_custom, name/spec jika ada lebih dari 1 varian custom).
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<string, mixed>|null
     */
    private function findTargetMatch(
        array $candidates,
        ?string $name,
        ?string $spec,
        bool $isCustom
    ): ?array {
        $matches = array_filter($candidates, fn ($c) => (bool) ($c['is_custom'] ?? false) === $isCustom);
        
        if (empty($matches)) {
            return null;
        }

        if (count($matches) === 1) {
            return reset($matches);
        }

        // Jika ada lebih dari 1 kandidat (misal beberapa variasi custom pada kode yang sama),
        // coba temukan yang cocok secara spesifikasi dan nama.
        foreach ($matches as $match) {
            if (($match['specification'] ?? null) === $spec && ($match['name'] ?? null) === $name) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Cek apakah seluruh atribut 100% sama persis dan produk tidak sedang terhapus.
     */
    private function isIdentical(
        array $existing,
        string $categoryName,
        ?string $name,
        ?string $spec,
        ?string $nie,
        int $qty,
        ?string $gol,
        bool $isCustom
    ): bool {
        return empty($existing['deleted_at'])
            && $name === ($existing['name'] ?? null)
            && $spec === ($existing['specification'] ?? null)
            && $gol === ($existing['product_group_code'] ?? null)
            && $qty === (int) ($existing['default_quantity'] ?? 1)
            && (bool) ($existing['is_custom'] ?? false) === $isCustom
            && $categoryName === (string) ($existing['category_name'] ?? '')
            && $this->normalizeNie($nie) === $this->normalizeNie($existing['nie_number'] ?? null);
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

    private function parseBoolean(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['ya', 'yes', '1', 'true', 'y', 'v', 'custom'], true);
    }
}
