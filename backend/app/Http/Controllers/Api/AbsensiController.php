<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AbsensiController extends Controller
{
    private const STATUS_KEHADIRAN = [
        'Hadir',
        'Izin',
        'Sakit',
        'Alpa',
    ];

    public function simpanAbsensi(Request $request)
    {
        $data = $request->validate([
            'kelas' => 'required|string|max:50',
            'tanggal' => 'required|date',
            'data_kehadiran' => 'required|array|min:1',
            'data_kehadiran.*' => 'required|array',
            'data_kehadiran.*.nama' => 'required|string|max:255',
            'data_kehadiran.*.status' => [
                'required',
                'string',
                Rule::in(self::STATUS_KEHADIRAN),
            ],
        ]);

        $absensi = Absensi::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Absensi kelas berhasil disimpan.',
            'data' => [
                'id' => $absensi->id,
                'kelas' => $absensi->kelas,
                'tanggal' => $absensi->tanggal,
                'data_kehadiran' => $absensi->data_kehadiran,
                'created_at' => $absensi->created_at,
            ],
        ], 201);
    }

    public function rekapAbsensi(string $kelas)
    {
        $dataAbsensi = Absensi::where('kelas', $kelas)
            ->orderByDesc('tanggal')
            ->get();

        if ($dataAbsensi->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data absensi untuk kelas tersebut.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data rekap absensi ditemukan.',
            'data' => $dataAbsensi,
        ]);
    }

    public function exportCsv(string $kelas)
    {
        $dataAbsensi = Absensi::where('kelas', $kelas)
            ->orderByDesc('tanggal')
            ->get();

        if ($dataAbsensi->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan untuk diekspor.',
            ], 404);
        }

        $filename = 'rekap-absensi-'
            . Str::slug($kelas)
            . '-'
            . now()->format('Y-m-d')
            . '.csv';

        $callback = static function () use ($dataAbsensi): void {
            $file = fopen('php://output', 'w');

            // BOM membantu aplikasi spreadsheet mengenali UTF-8.
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'No',
                'Kelas',
                'Tanggal',
                'Nama Siswa',
                'Status Kehadiran',
            ]);

            $no = 1;

            foreach ($dataAbsensi as $absen) {
                $kehadiranList = $absen->data_kehadiran;

                if (!is_array($kehadiranList)) {
                    continue;
                }

                foreach ($kehadiranList as $siswa) {
                    fputcsv($file, [
                        $no++,
                        self::safeCsvCell((string) $absen->kelas),
                        self::safeCsvCell((string) $absen->tanggal),
                        self::safeCsvCell((string) ($siswa['nama'] ?? '-')),
                        self::safeCsvCell((string) ($siswa['status'] ?? '-')),
                    ]);
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Mencegah teks dari pengguna diperlakukan sebagai formula spreadsheet.
     */
    private static function safeCsvCell(string $value): string
    {
        $trimmed = ltrim($value);

        if ($trimmed !== '' && preg_match('/^[=+\-@]/', $trimmed)) {
            return "'" . $value;
        }

        if (preg_match('/^[\t\r\n]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
