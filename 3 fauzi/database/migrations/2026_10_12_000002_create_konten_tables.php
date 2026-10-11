<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita', function (Blueprint $t) {
            $t->id();
            $t->string('judul', 150);
            $t->string('slug', 180)->unique();
            $t->string('ringkasan', 300)->nullable();
            $t->longText('konten');
            $t->string('gambar_path')->nullable();
            $t->enum('status', ['draf', 'terbit'])->default('draf');
            $t->dateTime('diterbitkan_pada')->nullable();
            $t->foreignId('penulis_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'diterbitkan_pada']);
        });

        Schema::create('agenda', function (Blueprint $t) {
            $t->id();
            $t->string('judul', 150);
            $t->text('deskripsi')->nullable();
            $t->string('lokasi', 150)->nullable();
            $t->dateTime('mulai');
            $t->dateTime('selesai')->nullable();
            $t->timestamps();
            $t->index('mulai');
        });

        Schema::create('galeri', function (Blueprint $t) {
            $t->id();
            $t->string('judul', 150);
            $t->string('keterangan', 300)->nullable();
            $t->string('album', 80)->nullable();
            $t->string('gambar_path')->nullable();
            $t->timestamps();
            $t->index('album');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeri');
        Schema::dropIfExists('agenda');
        Schema::dropIfExists('berita');
    }
};
