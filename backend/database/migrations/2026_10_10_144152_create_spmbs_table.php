<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spmb', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pendaftaran')->unique();
            $table->string('nama_lengkap');
            $table->string('nisn')->unique();
            $table->string('nik')->unique();
            $table->string('asal_sekolah');
            $table->date('tanggal_lahir');
            
            // Kolom Jalur Pendaftaran
            $table->enum('jalur_pendaftaran', ['Zonasi', 'Afirmasi', 'Prestasi', 'Perpindahan Tugas']);
            
            // Kolom Berkas Lampiran (Menyimpan path file)
            $table->string('berkas_ijazah_skl')->nullable();
            $table->string('berkas_kk')->nullable();
            $table->string('berkas_rapor')->nullable();
            
            $table->string('status')->default('Menunggu Verifikasi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spmb');
    }
};