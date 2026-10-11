<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpmbController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

// SPMB publik terpisah dari akun SIA.
Route::get('/spmb/status/{nomor}', [SpmbController::class, 'cekStatus'])
    ->middleware('throttle:30,1');

Route::post('/spmb/daftar', [SpmbController::class, 'daftar'])
    ->middleware('throttle:5,1');

// SIA hanya menyediakan login publik. Tidak ada registrasi publik.
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Pengelolaan akun hanya oleh admin.
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'users']);
        Route::post('/users', [AuthController::class, 'createUser']);
        Route::patch('/users/{user}/role', [AuthController::class, 'updateRole']);
        Route::delete('/users/{user}', [AuthController::class, 'deleteUser']);

        Route::put('/spmb/status/{id}', [SpmbController::class, 'updateStatus']);
    });

    // Fitur guru: mengelola absensi dan tugas.
    Route::middleware('role:guru,admin')->group(function () {
        Route::post('/absensi/simpan', [AbsensiController::class, 'simpanAbsensi']);
        Route::get('/absensi/export/{kelas}', [AbsensiController::class, 'exportCsv']);
        Route::post('/tugas/buat', [TugasController::class, 'buatTugas']);
        Route::delete('/tugas/{id}', [TugasController::class, 'hapusTugas']);
    });

    // Siswa dan guru yang sudah login dapat membaca endpoint ini.
    Route::get('/absensi/rekap/{kelas}', [AbsensiController::class, 'rekapAbsensi']);
    Route::get('/tugas/kelas/{kelas}', [TugasController::class, 'daftarTugas']);
});
