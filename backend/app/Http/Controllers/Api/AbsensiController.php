<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\Response;

class AbsensiController extends Controller
{
    // 1. Guru menyimpan/mencatat absensi per kelas
    public function simpanAbsensi(Request $request)
    {
        $request->validate([
            'kelas'          => 'required|string|max:50',
            'tanggal'        => 'required|date',
            'data_kehadiran' => 'required|array', // Contoh format: [{"nama": "Budi", "status": "Hadir"}, ...]
        ]);

        $absensi = Absensi::create([
            'kelas'          => $request->kelas,
            'tanggal'        => $request->tanggal,
            'data_kehadiran' => $request->data_kehadiran,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absensi kelas berhasil disimpan.',
            'data'    => $absensi
        ], 201);
    }

    // 2. Melihat rekap absensi berdasarkan kelas
    public function rekapAbsensi($kelas)
    {
        $dataAbsensi = Absensi::where('kelas', 'LIKE', "%{$kelas}%")->orderBy('tanggal', 'DESC')->get();

        if ($dataAbsensi->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data absensi untuk kelas tersebut.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data rekap absensi ditemukan.',
            'data'    => $dataAbsensi
        ], 200);
    }

    // 3. Ekspor Data Absensi ke Format CSV yang Rapi per Siswa
    public function exportCsv($kelas)
    {
        $dataAbsensi = Absensi::where('kelas', 'LIKE', "%{$kelas}%")->get();

        if ($dataAbsensi->isEmpty()) {
            return response()->json(['message' => 'Data tidak ditemukan untuk diekspor'], 404);
        }

        $filename = "rekap-absensi-" . str_replace(' ', '-', strtolower($kelas)) . "-" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($dataAbsensi) {
            $file = fopen('php://output', 'w');
            
            // Header kolom CSV yang bersih dan terstruktur
            fputcsv($file, ['No', 'Kelas', 'Tanggal', 'Nama Siswa', 'Status Kehadiran']);

            $no = 1;
            foreach ($dataAbsensi as $absen) {
                // $absen->data_kehadiran sudah otomatis jadi array berkat $casts di Model
                $kehadiranList = $absen->data_kehadiran;

                if (is_array($kehadiranList)) {
                    foreach ($kehadiranList as $siswa) {
                        fputcsv($file, [
                            $no++,
                            $absen->kelas,
                            $absen->tanggal,
                            $siswa['nama'] ?? '-',
                            $siswa['status'] ?? '-'
                        ]);
                    }
                }
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}