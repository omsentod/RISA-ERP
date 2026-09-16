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
        $sheet->fromArray(['Spesifikasi', 'Kode', 'Nama Produk', 'NIE', 'QTY', 'Kode Golongan', 'Custom'], null, 'A1');
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
            'is_custom' => false,
            'product_group_code' => '01',
            'product_category_id' => $category->id,
            'registration_id' => $registration->id,
        ]);
    }

    public function test_only_exact_match_is_duplicate_others_are_new(): void
    {
        $this->seedExistingProduct();

        $path = $this->makeXlsx([
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate', '21302420095', 1, '01', 'Tidak'],        // sama persis → DUPLICATE
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate REV2', '21302420095', 1, '01', 'Tidak'],   // kode sama, nama beda → NEW
            ['New spec', 'OF 9999 99', 'Produk Baru', '21302420095', 1, '02', 'Tidak'],                 // kode baru → NEW
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
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate', 'AKD 21302420095', 1, '01', 'Tidak'],
        ]);

        $rows = app(ProductImportParser::class)->parse($path);

        $this->assertSame(ProductImportRow::STATUS_DUPLICATE, $rows[0]->status);
    }

    public function test_parses_custom_column_correctly(): void
    {
        $path = $this->makeXlsx([
            ['Spec 1', 'SKU-001', 'Prod Standar', '21302420095', 1, '01', 'Tidak'],
            ['Spec 2', 'SKU-002', 'Prod Custom Ya', '21302420095', 1, '01', 'Ya'],
            ['Spec 3', 'SKU-003', 'Prod Custom 1', '21302420095', 1, '01', '1'],
            ['Spec 4', 'SKU-004', 'Prod Custom Empty', '21302420095', 1, '01', null],
        ]);

        $rows = app(ProductImportParser::class)->parse($path);

        $this->assertCount(4, $rows);
        $this->assertFalse($rows[0]->isCustom);
        $this->assertTrue($rows[1]->isCustom);
        $this->assertTrue($rows[2]->isCustom);
        $this->assertFalse($rows[3]->isCustom);
    }

    public function test_custom_product_with_same_sku_is_treated_as_new_not_duplicate(): void
    {
        $this->seedExistingProduct(); // existing OF 1010 04 has is_custom = false

        $path = $this->makeXlsx([
            ['Bone plate', 'OF 1010 04', 'Semi Tubular Plate', '21302420095', 1, '01', 'Ya'], // same name & sku, but custom = Ya -> NEW
        ]);

        $rows = app(ProductImportParser::class)->parse($path);

        $this->assertSame(ProductImportRow::STATUS_NEW, $rows[0]->status);
    }

    public function test_apply_product_import_persists_is_custom(): void
    {
        $path = $this->makeXlsx([
            ['Spec Custom', 'OF 1076 05LR', 'Custom Plate', '21302420095', 1, '01', 'Ya'],
        ]);

        $rows = app(ProductImportParser::class)->parse($path);
        app(\App\Domain\Import\Actions\ApplyProductImport::class)->handle($rows, 'create_new');

        $this->assertDatabaseHas('products', [
            'code' => 'OF 1076 05LR',
            'is_custom' => true,
        ]);
    }
}
