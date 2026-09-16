<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_transaction_items', function (Blueprint $table) {
            $table->string('lot_number', 50)->nullable()->after('quantity')->comment('Nomor LOT / Batch');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_transaction_items', function (Blueprint $table) {
            $table->dropColumn('lot_number');
        });
    }
};
