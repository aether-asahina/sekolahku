<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->string('judul_tugas');
            $table->text('deskripsi');
            $table->string('kelas'); // Contoh: "X IPA 1"
            $table->dateTime('tenggat_waktu');
            $table->enum('prioritas', ['Rendah', 'Normal', 'Tinggi'])->default('Normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};