<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tugas;

class TugasController extends Controller
{
    // 1. Guru membuat/menambahkan tugas baru
    public function buatTugas(Request $request)
    {
        $request->validate([
            'judul_tugas'   => 'required|string|max:255',
            'deskripsi'     => 'required|string',
            'kelas'         => 'required|string|max:50',
            'tenggat_waktu' => 'required|date',
            'prioritas'     => 'nullable|in:Rendah,Normal,Tinggi',
        ]);

        $tugas = Tugas::create([
            'judul_tugas'   => $request->judul_tugas,
            'deskripsi'     => $request->deskripsi,
            'kelas'         => $request->kelas,
            'tenggat_waktu' => $request->tenggat_waktu,
            'prioritas'     => $request->prioritas ?? 'Normal',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dibuat dan dibagikan ke kelas.',
            'data'    => $tugas
        ], 201);
    }

    // 2. Siswa melihat daftar tugas berdasarkan kelas mereka
    public function daftarTugas($kelas)
    {
        $tugasList = Tugas::where('kelas', $kelas)
                          ->orderBy('tenggat_waktu', 'ASC')
                          ->get();

        if ($tugasList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tugas aktif untuk kelas tersebut.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas berhasil dimuat.',
            'data'    => $tugasList
        ], 200);
    }

    // 3. Menghapus tugas (jika dibatalkan guru)
    public function hapusTugas($id)
    {
        $tugas = Tugas::find($id);

        if (!$tugas) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan.'
            ], 404);
        }

        $tugas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dihapus.'
        ], 200);
    }
}