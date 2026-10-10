<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Spmb;
use Illuminate\Support\Facades\Storage;

class SpmbController extends Controller
{
    // 1. Endpoint Cek Status Pendaftaran (Tanpa Login)
    public function cekStatus($nomor)
    {
        $pendaftar = Spmb::where('nomor_pendaftaran', strtoupper($nomor))->first();

        if (!$pendaftar) {
            return response()->json([
                "success" => false,
                "message" => "Nomor pendaftaran tidak ditemukan. Periksa kembali nomor Anda."
            ], 404);
        }

        return response()->json([
            "success" => true,
            "message" => "Detail status pendaftaran ditemukan",
            "data" => $pendaftar
        ], 200);
    }

    // 2. Endpoint Pendaftaran SPMB Baru (Mendukung Upload Berkas & Jalur)
    public function daftar(Request $request)
    {
        $request->validate([
            'nama_lengkap'      => 'required|string|max:255',
            'nisn'              => 'required|string|unique:spmb,nisn|max:20',
            'nik'               => 'required|string|unique:spmb,nik|max:20',
            'asal_sekolah'      => 'required|string|max:255',
            'tanggal_lahir'     => 'required|date',
            'jalur_pendaftaran' => 'required|in:Zonasi,Afirmasi,Prestasi,Perpindahan Tugas',
            'berkas_ijazah_skl' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'berkas_kk'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'berkas_rapor'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Handle Upload Berkas jika ada
        $pathIjazah = $request->hasFile('berkas_ijazah_skl') ? $request->file('berkas_ijazah_skl')->store('berkas/ijazah', 'public') : null;
        $pathKK     = $request->hasFile('berkas_kk') ? $request->file('berkas_kk')->store('berkas/kk', 'public') : null;
        $pathRapor  = $request->hasFile('berkas_rapor') ? $request->file('berkas_rapor')->store('berkas/rapor', 'public') : null;

        // Generate Nomor Pendaftaran Unik 8 Karakter
        $nomorAcak = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        $pendaftaranBaru = Spmb::create([
            "nomor_pendaftaran" => $nomorAcak,
            "nama_lengkap"      => $request->nama_lengkap,
            "nisn"              => $request->nisn,
            "nik"               => $request->nik,
            "asal_sekolah"      => $request->asal_sekolah,
            "tanggal_lahir"     => $request->tanggal_lahir,
            "jalur_pendaftaran" => $request->jalur_pendaftaran,
            "berkas_ijazah_skl" => $pathIjazah,
            "berkas_kk"         => $pathKK,
            "berkas_rapor"      => $pathRapor,
            "status"            => "Menunggu Verifikasi"
        ]);

        return response()->json([
            "success" => true,
            "message" => "Pendaftaran SPMB berhasil! Simpan nomor pendaftaran Anda untuk pengecekan status.",
            "data"    => $pendaftaranBaru
        ], 201);
    }

    // 3. Endpoint Guru/Admin Memperbarui Status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Menunggu Verifikasi,Diverifikasi,Perlu Perbaikan,Lulus,Tidak Lulus',
        ]);

        $pendaftar = Spmb::find($id);

        if (!$pendaftar) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftar tidak ditemukan.'
            ], 404);
        }

        $pendaftar->status = $request->status;
        $pendaftar->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pendaftaran berhasil diperbarui.',
            'data'    => $pendaftar
        ], 200);
    }
}