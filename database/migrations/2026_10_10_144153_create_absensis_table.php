<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->string('kelas'); // Contoh: "X IPA 1", "XI IPS 2"
            $table->date('tanggal');
            $table->json('data_kehadiran'); // Berisi array data siswa dan statusnya (Hadir, Izin, Sakit, Alpa)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};