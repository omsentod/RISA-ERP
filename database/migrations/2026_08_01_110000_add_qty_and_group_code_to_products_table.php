<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('default_quantity')->default(1)->after('default_lot')->comment('Quantity default untuk cetak label');
            $table->string('product_group_code', 20)->nullable()->after('default_quantity')->comment('Kode Golongan Produk, contoh: 01, 03');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['default_quantity', 'product_group_code']);
        });
    }
};
