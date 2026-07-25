<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('doc_no', 50)->unique()->comment('Nomor surat jalan, format: SJ-YYYYMMDD-NNN');
            $table->date('doc_date')->comment('Tanggal dokumen surat jalan');
            $table->string('destination', 255)->nullable()->comment('Tujuan pengiriman (RS / customer)');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft')->index();
            $table->timestamp('started_at')->nullable()->comment('Waktu sesi scan dimulai');
            $table->timestamp('completed_at')->nullable()->comment('Waktu sesi scan selesai / cancelled');
            $table->unsignedInteger('total_qty')->default(0)->comment('Denormalized total quantity items untuk speed di list');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['doc_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_transactions');
    }
};
