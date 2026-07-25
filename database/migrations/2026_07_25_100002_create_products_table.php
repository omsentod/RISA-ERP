<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();
            $table->foreignId('registration_id')
                ->nullable()
                ->constrained('registrations')
                ->nullOnDelete()
                ->comment('NIE / Nomor Izin Edar produk');
            $table->string('code', 100)->unique()->comment('Kode SKU internal, contoh: OF 1010 04');
            $table->string('name', 255)->comment('Nama produk, contoh: 4.5 mm Semi Tubular Plate 4 Holes');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0)->comment('Jumlah stok fisik produk saat ini');
            $table->boolean('is_published')->default(false)->comment('Tampil di company profile website atau tidak');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang publish');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_published', 'products_is_published_idx');
            $table->index('name', 'products_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
