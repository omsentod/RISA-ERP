<?php

namespace App\Domain\Product\Actions;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class GenerateBarcode
{
    public function svg(string $code, int $widthFactor = 2, int $height = 60): string
    {
        $generator = new BarcodeGeneratorSVG;

        return $generator->getBarcode($code, BarcodeGeneratorSVG::TYPE_CODE_128, $widthFactor, $height);
    }

    public function pngDataUri(string $code, int $widthFactor = 2, int $height = 60): string
    {
        $generator = new BarcodeGeneratorPNG;
        $binary = $generator->getBarcode($code, BarcodeGeneratorPNG::TYPE_CODE_128, $widthFactor, $height);

        return 'data:image/png;base64,' . base64_encode($binary);
    }
}
