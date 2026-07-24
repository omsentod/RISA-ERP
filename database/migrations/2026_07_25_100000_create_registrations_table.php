<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('nie_number', 100)->unique()->comment('Nomor Izin Edar BPOM, format contoh: AKD 21302420095');
            $table->string('issuer', 100)->default('BPOM')->comment('Otoritas yang mengeluarkan izin');
            $table->date('issued_at')->nullable()->comment('Tanggal NIE dikeluarkan');
            $table->date('expired_at')->nullable()->comment('Tanggal NIE expired (biasanya 5 tahun)');
            $table->string('attachment_path')->nullable()->comment('Path file scan NIE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expired_at', 'registrations_expired_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
