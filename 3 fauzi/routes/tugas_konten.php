<?php

use App\Http\Controllers\Api\V1\AgendaController;
use App\Http\Controllers\Api\V1\BeritaController;
use App\Http\Controllers\Api\V1\GaleriController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\TugasController;
use Illuminate\Support\Facades\Route;

// Dimuat oleh TugasKontenServiceProvider dengan prefix /api/v1.

// Publik (tanpa login)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('berita', [BeritaController::class, 'index']);
    Route::get('berita/{slug}', [BeritaController::class, 'show']);
    Route::get('agenda', [AgendaController::class, 'index']);
    Route::get('agenda/{agenda}', [AgendaController::class, 'show'])->whereNumber('agenda');
    Route::get('agenda/{agenda}/ics', [AgendaController::class, 'ics'])->whereNumber('agenda');
    Route::get('galeri', [GaleriController::class, 'index']);
});

// Terautentikasi (token Sanctum)
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Tugas
    Route::get('tugas', [TugasController::class, 'index']);
    Route::post('tugas', [TugasController::class, 'store']);
    Route::get('tugas/{tugas}', [TugasController::class, 'show']);
    Route::put('tugas/{tugas}', [TugasController::class, 'update']);
    Route::patch('tugas/{tugas}', [TugasController::class, 'update']);
    Route::delete('tugas/{tugas}', [TugasController::class, 'destroy']);
    Route::patch('tugas/{tugas}/status', [TugasController::class, 'ubahStatus']);
    Route::get('tugas/{tugas}/progres', [TugasController::class, 'progres']);

    // Notifikasi
    Route::get('notifikasi', [NotifikasiController::class, 'index']);
    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua']);
    Route::post('notifikasi/{id}/baca', [NotifikasiController::class, 'baca']);

    // Pengelolaan konten (admin)
    Route::get('admin/berita', [BeritaController::class, 'daftarAdmin']);
    Route::get('admin/berita/{berita}', [BeritaController::class, 'tampilAdmin']);
    Route::post('admin/berita', [BeritaController::class, 'store']);
    Route::put('admin/berita/{berita}', [BeritaController::class, 'update']);
    Route::delete('admin/berita/{berita}', [BeritaController::class, 'destroy']);

    Route::post('admin/agenda', [AgendaController::class, 'store']);
    Route::put('admin/agenda/{agenda}', [AgendaController::class, 'update']);
    Route::delete('admin/agenda/{agenda}', [AgendaController::class, 'destroy']);

    Route::post('admin/galeri', [GaleriController::class, 'store']);
    Route::put('admin/galeri/{galeri}', [GaleriController::class, 'update']);
    Route::delete('admin/galeri/{galeri}', [GaleriController::class, 'destroy']);
});
