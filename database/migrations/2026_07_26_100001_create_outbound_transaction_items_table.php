<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_transaction_id')
                ->constrained('outbound_transactions')
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('scanned_at')->comment('Waktu barcode di-scan / item ditambah ke transaksi');
            $table->timestamps();

            $table->unique(['outbound_transaction_id', 'product_id'], 'uniq_outbound_transaction_product');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_transaction_items');
    }
};
