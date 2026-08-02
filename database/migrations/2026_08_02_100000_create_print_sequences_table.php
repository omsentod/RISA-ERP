<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->comment('Tanggal aktivitas cetak');
            $table->integer('sequence_number')->comment('Urutan unik cetak');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_sequences');
    }
};
