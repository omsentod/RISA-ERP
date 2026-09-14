<?php

namespace Tests\Feature\Domain\Import;

use App\Domain\Import\Data\ProductImportRow;
use App\Domain\Import\Parsers\ProductImportParser;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Registration\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductImportParserTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /**
     * @param array<int, array<int, mixed>> $dataRows
     */
    private function makeXlsx(array $dataRows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NON LOCKING');
        $sheet->fromArray(['Spesifikasi', 'Kode', 'Nama Produk', 'NIE', 'QTY', 'Kode Golongan'], null, 'A1');
        $sheet->fromArray($dataRows, null, 'A2');
        $spreadsheet->createSheet()->setTitle('LOCKING');

        $path = sys_get_temp_dir() . '/import_test_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function seedExistingProduct(): void
    {
        $category = ProductCategory::firstOrCreate(['name' => 'NON LOCKING'], ['slug' => 'non-locking']);
        $registration = Registration::firstOrCreate(['nie_number' => '21302420095'], ['issuer' => 'BPOM']);

        Product::create([
            'code' => 'OF 1010 04',
            'name' => 'Semi Tubular Plate',
            'specification' => 'Bone plate',
            'default_quantity' => 1,
            'product_group_code' => '01',
            'product_category_id' => $category->id,
            'registration_id' => $registration->id,
        ]);
    }

    public function test_only_exact_match_is_duplicate_others_are_new(): void
    {
        $this->seedExistingProduct();

        $path = $this->makeXlsx([
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate', '21302420095', 1, '01'],        // sama persis → DUPLICATE
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate REV2', '21302420095', 1, '01'],   // kode sama, nama beda → NEW
            ['New spec', 'OF 9999 99', 'Produk Baru', '21302420095', 1, '02'],                 // kode baru → NEW
        ]);

        $counts = collect(app(ProductImportParser::class)->parse($path))
            ->groupBy('status')
            ->map->count();

        $this->assertSame(1, $counts[ProductImportRow::STATUS_DUPLICATE] ?? 0);
        $this->assertSame(2, $counts[ProductImportRow::STATUS_NEW] ?? 0);
    }

    public function test_nie_akd_prefix_difference_still_counts_as_exact_match(): void
    {
        $this->seedExistingProduct();

        $path = $this->makeXlsx([
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate', 'AKD 21302420095', 1, '01'],
        ]);

        $rows = app(ProductImportParser::class)->parse($path);

        $this->assertSame(ProductImportRow::STATUS_DUPLICATE, $rows[0]->status);
    }
}
