<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Tugas;
use App\Models\TugasSiswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data demonstrasi FIKTIF. Tanpa emoji. Aman dijalankan berulang (idempoten).
 * Bagian tugas membutuhkan minimal 1 guru, 1 kelas, dan beberapa siswa dari seeder Anggota 1/2.
 */
class TugasKontenSeeder extends Seeder
{
    public function run(): void
    {
        $this->berita();
        $this->agenda();
        $this->galeri();
        $this->tugas();
    }

    private function berita(): void
    {
        $penulis = User::where('role', 'admin')->value('id');

        $data = [
            ['Pendaftaran Peserta Didik Baru Tahun Ajaran 2027/2028 Dibuka', 'Pendaftaran daring dibuka dan dapat diselesaikan dalam beberapa menit melalui ponsel.', 'Panitia PPDB membuka pendaftaran daring untuk tahun ajaran 2027/2028. Calon siswa mengisi formulir tiga langkah, menerima nomor pendaftaran, dan dapat memeriksa status kapan saja tanpa login.' . "\n\n" . 'Berkas asli tetap dibawa saat daftar ulang bagi peserta yang dinyatakan lulus.', 2, 'terbit'],
            ['Tim Cerdas Cermat Sekolah Meraih Juara Pertama Tingkat Kota', 'Tiga siswa kelas XI mewakili sekolah dan membawa pulang piala juara pertama.', 'Tim cerdas cermat yang terdiri dari tiga siswa kelas XI berhasil mengalahkan tim dari dua belas sekolah lain. Pembina menyampaikan bahwa latihan rutin setiap pekan menjadi kunci keberhasilan tim.', 5, 'terbit'],
            ['Jadwal Penilaian Tengah Semester Ganjil Telah Diumumkan', 'Siswa diminta memeriksa jadwal dan menyiapkan perlengkapan ujian sejak dini.', 'Penilaian tengah semester ganjil dilaksanakan selama tiga hari. Siswa diharapkan hadir lima belas menit sebelum ujian dimulai dan membawa perlengkapan tulis masing-masing.', 7, 'terbit'],
            ['Kegiatan Jumat Bersih Libatkan Seluruh Kelas', 'Setiap kelas mendapat area tanggung jawab untuk dibersihkan bersama wali kelas.', 'Kegiatan Jumat bersih diadakan rutin untuk menumbuhkan kepedulian terhadap lingkungan. Kelas dengan area terbersih akan mendapat penghargaan pada akhir bulan.', 10, 'terbit'],
            ['Pelatihan Guru: Pemanfaatan Teknologi dalam Pembelajaran', 'Guru berlatih memakai sistem tugas daring untuk memantau progres belajar siswa.', 'Sekolah menyelenggarakan pelatihan satu hari bagi seluruh guru. Materi mencakup pembuatan tugas dengan tenggat, pemantauan status pengerjaan, dan pencatatan kehadiran per kelas.', 14, 'terbit'],
            ['Perpustakaan Menambah Koleksi Buku Fiksi dan Sains', 'Lebih dari seratus judul baru siap dipinjam mulai pekan depan.', 'Perpustakaan sekolah menambah koleksi buku fiksi, sains populer, dan referensi mata pelajaran. Peminjaman dilakukan di jam istirahat dengan kartu pelajar.', 20, 'terbit'],
            ['Rencana Kegiatan Pekan Literasi (Draf)', 'Draf untuk diperiksa admin sebelum diterbitkan.', 'Pekan literasi direncanakan memuat lomba baca puisi, bedah buku, dan pameran karya tulis siswa. Jadwal rinci menunggu persetujuan kepala sekolah.', 0, 'draf'],
        ];

        foreach ($data as [$judul, $ringkasan, $konten, $hariLalu, $status]) {
            Berita::updateOrCreate(
                ['slug' => Str::slug($judul)],
                [
                    'judul' => $judul,
                    'ringkasan' => $ringkasan,
                    'konten' => $konten,
                    'status' => $status,
                    'diterbitkan_pada' => $status === 'terbit' ? now()->subDays($hariLalu)->setTime(8, 0) : null,
                    'penulis_id' => $penulis,
                ]
            );
        }
    }

    private function agenda(): void
    {
        // [judul, deskripsi, lokasi, selisih hari, jam mulai, durasi (jam)]
        $data = [
            ['Upacara Bendera Rutin', 'Seluruh siswa dan guru mengenakan seragam lengkap.', 'Lapangan Upacara', -5, '07:00', 1],
            ['Penilaian Tengah Semester Ganjil', 'Ujian berlangsung tiga hari berturut-turut.', 'Ruang Kelas Masing-masing', 6, '07:30', 52],
            ['Lomba Kebersihan Antar Kelas', 'Penilaian dilakukan oleh tim guru piket.', 'Seluruh Area Sekolah', 9, '09:00', 3],
            ['Rapat Orang Tua dan Wali Murid', 'Pembahasan program semester dan penggunaan sistem informasi sekolah.', 'Aula Sekolah', 12, '09:00', 3],
            ['Upacara Peringatan Hari Sumpah Pemuda', 'Upacara dilanjutkan pentas seni dari ekstrakurikuler.', 'Lapangan Upacara', 17, '07:00', 2],
            ['Seminar Persiapan Karier dan Kuliah', 'Narasumber dari perguruan tinggi dan praktisi industri.', 'Aula Sekolah', 25, '13:00', 3],
            ['Pembagian Laporan Hasil Belajar', 'Orang tua mengambil laporan hasil belajar bersama wali kelas.', 'Ruang Kelas Masing-masing', 50, '08:00', 4],
        ];

        foreach ($data as [$judul, $deskripsi, $lokasi, $hari, $jam, $durasi]) {
            [$h, $m] = array_map('intval', explode(':', $jam));
            $mulai = now()->addDays($hari)->setTime($h, $m);
            Agenda::updateOrCreate(
                ['judul' => $judul],
                ['deskripsi' => $deskripsi, 'lokasi' => $lokasi, 'mulai' => $mulai, 'selesai' => $mulai->copy()->addHours($durasi)]
            );
        }
    }

    private function galeri(): void
    {
        // gambar_path dikosongkan; frontend menampilkan placeholder SVG. Unggah foto asli lewat panel admin.
        $data = [
            ['Upacara Bendera Hari Senin', 'Kegiatan Sekolah'],
            ['Praktikum Laboratorium Sains', 'Kegiatan Sekolah'],
            ['Pentas Seni Akhir Semester', 'Kegiatan Sekolah'],
            ['Perpustakaan Sekolah', 'Fasilitas'],
            ['Laboratorium Komputer', 'Fasilitas'],
            ['Piala Cerdas Cermat Tingkat Kota', 'Prestasi'],
        ];

        foreach ($data as [$judul, $album]) {
            Galeri::updateOrCreate(
                ['judul' => $judul],
                ['album' => $album, 'keterangan' => 'Dokumentasi demonstrasi sistem.']
            );
        }
    }

    private function tugas(): void
    {
        $guru = User::where('role', 'guru')->first();
        $kelasIds = DB::table('kelas')->orderBy('id')->limit(3)->pluck('id');

        if (! $guru || $kelasIds->isEmpty()) {
            $this->command?->warn('Data tugas dilewati: seed dulu akun guru dan kelas (seeder Anggota 1/2).');
            return;
        }

        // [judul, mata pelajaran, prioritas, penyesuaian tenggat]
        $contoh = [
            ['Latihan Soal Persamaan Linear', 'Matematika', 'tinggi', fn () => now()->addHours(20)],      // memicu pengingat
            ['Ringkasan Bab Ekosistem', 'Biologi', 'sedang', fn () => now()->addDays(3)->setTime(23, 59)],
            ['Esai Dampak Teknologi bagi Pelajar', 'Bahasa Indonesia', 'rendah', fn () => now()->addDays(7)->setTime(23, 59)],
            ['Laporan Praktikum Rangkaian Listrik', 'Fisika', 'tinggi', fn () => now()->subDays(2)->setTime(23, 59)], // lewat tenggat
        ];
        $urutanStatus = ['selesai', 'dikerjakan', 'belum'];

        foreach ($kelasIds as $kelasId) {
            $siswa = User::where('role', 'siswa')->where('kelas_id', $kelasId)->limit(6)->get();

            foreach ($contoh as [$judul, $mapel, $prioritas, $tenggat]) {
                $tugas = Tugas::updateOrCreate(
                    ['judul' => $judul, 'kelas_id' => $kelasId],
                    [
                        'deskripsi' => 'Kerjakan sesuai petunjuk guru dan periksa kembali sebelum tenggat.',
                        'mata_pelajaran' => $mapel,
                        'guru_id' => $guru->id,
                        'prioritas' => $prioritas,
                        'tenggat' => $tenggat(),
                    ]
                );

                foreach ($siswa as $i => $s) {
                    $status = $urutanStatus[$i % 3];
                    if ($status === 'belum') {
                        continue; // tanpa baris = belum dikerjakan
                    }
                    TugasSiswa::updateOrCreate(
                        ['tugas_id' => $tugas->id, 'siswa_id' => $s->id],
                        ['status' => $status, 'diselesaikan_pada' => $status === 'selesai' ? now()->subHours(3) : null]
                    );
                }
            }
        }
    }
}
