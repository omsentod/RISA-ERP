<?php

namespace Tests\Unit\Domain\Product\Actions;

use App\Domain\Product\Actions\FormatProductNameForPrint;
use PHPUnit\Framework\TestCase;

class FormatProductNameForPrintTest extends TestCase
{
    private FormatProductNameForPrint $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new FormatProductNameForPrint();
    }

    public function test_formats_4_holes_and_mm_with_smart_break(): void
    {
        $input = '4.5 mm Semi Tubular Plate 4 Holes';
        $expected = '4.5&nbsp;mm Semi Tubular Plate<br>4&nbsp;Holes';
        $this->assertEquals($expected, $this->formatter->handle($input));
    }

    public function test_formats_holes_number_pattern(): void
    {
        $input = 'Broad Plate DCP Holes 12';
        $expected = 'Broad Plate DCP<br>Holes&nbsp;12';
        $this->assertEquals($expected, $this->formatter->handle($input));
    }

    public function test_formats_side_directional_suffix(): void
    {
        $input = 'Proximal Lateral Femoral Plate 8 Holes - Left';
        $expected = 'Proximal Lateral Femoral Plate<br>8&nbsp;Holes&nbsp;-&nbsp;Left';
        $this->assertEquals($expected, $this->formatter->handle($input));
    }

    public function test_short_name_does_not_insert_line_break(): void
    {
        $input = 'Plate 4 Holes';
        $expected = 'Plate 4&nbsp;Holes';
        $this->assertEquals($expected, $this->formatter->handle($input));
    }
}
