<?php

namespace Database\Seeders;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Registration\Models\Registration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ProductSeeder extends Seeder
{
    private const REFERENCE_FILE = 'app/reference/BARCODE.xlsx';

    public function run(): void
    {
        $path = storage_path(self::REFERENCE_FILE);

        if (!file_exists($path)) {
            $this->command->error("Reference file not found: {$path}");

            return;
        }

        $this->command->info('Reading BARCODE.xlsx …');
        $sheets = Excel::toArray(new class implements WithHeadingRow
        {
            public function headingRow(): int
            {
                return 1;
            }
        }, $path);

        // Sheet 0 = NON LOCKING, Sheet 1 = LOCKING
        $nonLocking = $sheets[0] ?? [];
        $locking = $sheets[1] ?? [];

        $this->command->info(sprintf(
            'Loaded: %d non-locking rows, %d locking rows',
            count($nonLocking),
            count($locking)
        ));

        DB::transaction(function () use ($nonLocking, $locking) {
            // Bikin/ambil 2 kategori tetap sesuai nama sheet Excel
            $nonLockingCategory = ProductCategory::firstOrCreate(
                ['name' => 'NON LOCKING'],
                ['slug' => Str::slug('NON LOCKING'), 'is_locking' => false]
            );
            $lockingCategory = ProductCategory::firstOrCreate(
                ['name' => 'LOCKING'],
                ['slug' => Str::slug('LOCKING'), 'is_locking' => true]
            );

            $this->seedRows($nonLocking, $nonLockingCategory->id);
            $this->seedRows($locking, $lockingCategory->id);
        });

        $this->command->info(sprintf(
            'Done. Registrations: %d, Categories: %d, Products: %d',
            Registration::count(),
            ProductCategory::count(),
            Product::count(),
        ));
    }

    private function seedRows(array $rows, int $categoryId): void
    {
        $categoryName = ProductCategory::find($categoryId)?->name ?? 'UNKNOWN';
        $bar = $this->command->getOutput()->createProgressBar(count($rows));
        $bar->setFormat("  {$categoryName}: %current%/%max% [%bar%] %percent:3s%%");
        $bar->start();

        $registrationCache = [];

        foreach ($rows as $row) {
            $spec = $this->cleanValue($row['spesifikasi'] ?? null);
            $code = $this->cleanValue($row['kode'] ?? null);
            $name = $this->cleanValue($row['nama_produk'] ?? null);
            $nie = $this->cleanValue($row['nie'] ?? null);

            if (empty($code) || empty($name)) {
                $bar->advance();

                continue;
            }

            // Registration (dedupe within run)
            $registrationId = null;
            if ($nie !== null) {
                if (!isset($registrationCache[$nie])) {
                    $registration = Registration::firstOrCreate(
                        ['nie_number' => $nie],
                        ['issuer' => 'BPOM']
                    );
                    $registrationCache[$nie] = $registration->id;
                }
                $registrationId = $registrationCache[$nie];
            }

            Product::updateOrCreate(
                ['code' => $code],
                [
                    'product_category_id' => $categoryId,
                    'registration_id' => $registrationId,
                    'name' => $name,
                    'specification' => $spec,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
    }

    private function cleanValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
