<?php

namespace App\Domain\Import\Actions;

use App\Domain\Import\Data\ProductImportRow;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Registration\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplyProductImport
{
    /**
     * @param array<int, ProductImportRow> $rows
     * @param string $duplicateStrategy one of: create_new, skip
     * @return array{inserted:int, skipped:int, invalid:int}
     */
    public function handle(array $rows, string $duplicateStrategy): array
    {
        $inserted = 0;
        $skipped = 0;
        $invalid = 0;

        DB::transaction(function () use ($rows, $duplicateStrategy, &$inserted, &$skipped, &$invalid) {
            $categoryCache = [];
            $registrationCache = [];

            foreach ($rows as $row) {
                if ($row->status === ProductImportRow::STATUS_INVALID) {
                    $invalid++;

                    continue;
                }

                if ($row->status === ProductImportRow::STATUS_DUPLICATE && $duplicateStrategy === 'skip') {
                    $skipped++;

                    continue;
                }

                $categoryId = $categoryCache[$row->categoryName] ??= ProductCategory::firstOrCreate(
                    ['name' => $row->categoryName],
                    ['slug' => Str::slug($row->categoryName)]
                )->id;

                $registrationId = null;
                if ($row->nieNumber !== null) {
                    $registrationId = $registrationCache[$row->nieNumber] ??= Registration::firstOrCreate(
                        ['nie_number' => $row->nieNumber],
                        [
                            'issuer' => 'BPOM',
                            'notes' => 'Dibuat otomatis via Excel Import. Perlu melengkapi tanggal kadaluarsa (expiry date).',
                        ]
                    )->id;
                }

                Product::create([
                    'code' => $row->code,
                    'product_category_id' => $categoryId,
                    'registration_id' => $registrationId,
                    'name' => $row->name,
                    'specification' => $row->specification,
                    'default_quantity' => $row->defaultQuantity > 0 ? $row->defaultQuantity : 1,
                    'product_group_code' => $row->productGroupCode,
                ]);

                $inserted++;
            }
        });

        return compact('inserted', 'skipped', 'invalid');
    }
}
