<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SpmbController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\TugasController;

// Rute Modul SPMB
Route::get('/spmb/status/{nomor}', [SpmbController::class, 'cekStatus']);
Route::post('/spmb/daftar', [SpmbController::class, 'daftar']);
Route::put('/spmb/status/{id}', [SpmbController::class, 'updateStatus']);

// Rute Modul Absensi
Route::post('/absensi/simpan', [AbsensiController::class, 'simpanAbsensi']);
Route::get('/absensi/rekap/{kelas}', [AbsensiController::class, 'rekapAbsensi']);
Route::get('/absensi/export/{kelas}', [AbsensiController::class, 'exportCsv']);

// Rute Modul Manajemen Tugas
Route::post('/tugas/buat', [TugasController::class, 'buatTugas']);
Route::get('/tugas/kelas/{kelas}', [TugasController::class, 'daftarTugas']);
Route::delete('/tugas/{id}', [TugasController::class, 'hapusTugas']);