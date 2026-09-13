<?php

use App\Domain\Product\Actions\SaveProductLabelLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['web', 'auth'])->post('/admin/products/save-label-layout', function (Request $request, SaveProductLabelLayout $action) {
    $validated = $request->validate([
        'layouts' => ['required', 'array'],
    ]);

    $count = $action->handle($validated['layouts']);

    return response()->json([
        'success' => true,
        'updated_count' => $count,
        'message' => "Preset untuk {$count} produk berhasil disimpan ke database",
    ]);
})->name('products.save-label-layout');
