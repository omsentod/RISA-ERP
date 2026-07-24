<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('specification')
                ->nullable()
                ->after('name')
                ->comment('Spesifikasi lengkap produk, dari kolom Spesifikasi di BARCODE.xlsx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specification');
        });
    }
};
