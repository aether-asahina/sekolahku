<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Prasyarat: tabel `users` (kolom role, kelas_id) dan tabel `kelas` sudah ada (milik Anggota 1 dan 2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $t) {
            $t->id();
            $t->string('judul', 150);
            $t->text('deskripsi')->nullable();
            $t->string('mata_pelajaran', 100)->nullable();
            $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $t->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $t->dateTime('tenggat');
            $t->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $t->timestamps();
            $t->index(['kelas_id', 'tenggat']);
        });

        Schema::create('tugas_siswa', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $t->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $t->enum('status', ['belum', 'dikerjakan', 'selesai'])->default('belum');
            $t->dateTime('diselesaikan_pada')->nullable();
            $t->dateTime('pengingat_dikirim_pada')->nullable();
            $t->timestamps();
            $t->unique(['tugas_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas_siswa');
        Schema::dropIfExists('tugas');
    }
};
