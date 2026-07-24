<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique()->comment('Nama spesifikasi kategori, dari kolom Spesifikasi di BARCODE.xlsx');
            $table->string('slug', 255)->unique()->comment('Slug URL-friendly dari name');
            $table->text('description')->nullable();
            $table->boolean('is_locking')->default(false)->comment('Kategori locking atau non-locking (implant type)');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
