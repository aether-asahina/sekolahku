<?php

namespace Tests\Feature;

use App\Models\Spmb;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpmbAndTaskPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_spmb_status_does_not_expose_personal_details(): void
    {
        Spmb::create([
            'nomor_pendaftaran' => 'TEST1234',
            'nama_lengkap' => 'Nama Rahasia',
            'nisn' => '1234567890',
            'nik' => '1234567890123456',
            'asal_sekolah' => 'Sekolah Rahasia',
            'tanggal_lahir' => '2009-05-10',
            'jalur_pendaftaran' => 'Zonasi',
            'status' => 'Menunggu Verifikasi',
        ]);

        $response = $this->getJson('/api/spmb/status/TEST1234');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nomor_pendaftaran', 'TEST1234')
            ->assertJsonPath('data.status', 'Menunggu Verifikasi')
            ->assertJsonMissingPath('data.nama_lengkap')
            ->assertJsonMissingPath('data.nisn')
            ->assertJsonMissingPath('data.nik')
            ->assertJsonMissingPath('data.asal_sekolah')
            ->assertJsonMissingPath('data.jalur_pendaftaran');
    }

    public function test_task_list_only_returns_exact_class_matches(): void
    {
        $siswa = User::create([
            'name' => 'Siswa Tes',
            'email' => 'siswa@example.test',
            'password' => 'Password-aman-123',
        ]);

        $siswa->forceFill(['role' => 'siswa'])->save();

        Tugas::create([
            'judul_tugas' => 'Tugas Kelas X',
            'deskripsi' => 'Tugas untuk X IPA 1',
            'kelas' => 'X IPA 1',
            'tenggat_waktu' => '2027-01-10 23:59:00',
            'prioritas' => 'Normal',
        ]);

        Tugas::create([
            'judul_tugas' => 'Tugas Kelas XI',
            'deskripsi' => 'Tugas untuk XI IPA 1',
            'kelas' => 'XI IPA 1',
            'tenggat_waktu' => '2027-01-11 23:59:00',
            'prioritas' => 'Normal',
        ]);

        Sanctum::actingAs($siswa);

        $response = $this->getJson(
            '/api/tugas/kelas/' . rawurlencode('X IPA 1')
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul_tugas', 'Tugas Kelas X')
            ->assertJsonPath('data.0.kelas', 'X IPA 1');
    }

    public function test_teacher_cannot_update_spmb_status(): void
    {
        $teacher = User::factory()->create();
        $teacher->forceFill(['role' => 'guru'])->save();

        $spmb = Spmb::create([
            'nomor_pendaftaran' => 'GURUTEST1',
            'nama_lengkap' => 'Pendaftar Tes',
            'nisn' => '9912345678',
            'nik' => '9912345678901234',
            'asal_sekolah' => 'Sekolah Tes',
            'tanggal_lahir' => '2009-05-10',
            'jalur_pendaftaran' => 'Zonasi',
            'status' => 'Menunggu Verifikasi',
        ]);

        Sanctum::actingAs($teacher);

        $this->putJson('/api/admin/spmb/status/' . $spmb->id, [
            'status' => 'Lulus',
        ])->assertForbidden();

        $this->assertDatabaseHas('spmb', [
            'id' => $spmb->id,
            'status' => 'Menunggu Verifikasi',
        ]);
    }

    public function test_admin_can_update_spmb_status(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $spmb = Spmb::create([
            'nomor_pendaftaran' => 'ADMIN001',
            'nama_lengkap' => 'Pendaftar Tes',
            'nisn' => '8812345678',
            'nik' => '8812345678901234',
            'asal_sekolah' => 'Sekolah Tes',
            'tanggal_lahir' => '2009-05-10',
            'jalur_pendaftaran' => 'Zonasi',
            'status' => 'Menunggu Verifikasi',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/spmb/status/' . $spmb->id, [
            'status' => 'Diverifikasi',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Diverifikasi');

        $this->assertDatabaseHas('spmb', [
            'id' => $spmb->id,
            'status' => 'Diverifikasi',
        ]);
    }

    public function test_public_spmb_registration_does_not_expose_uploaded_file_paths(): void
    {
        Storage::fake('local');

        $response = $this->postJson('/api/spmb/daftar', [
            'nama_lengkap' => 'Pendaftar Privasi',
            'nisn' => '7712345678',
            'nik' => '7712345678901234',
            'asal_sekolah' => 'Sekolah Tes',
            'tanggal_lahir' => '2009-05-10',
            'jalur_pendaftaran' => 'Zonasi',
            'berkas_ijazah_skl' => UploadedFile::fake()->create(
                'ijazah.pdf', 100, 'application/pdf'
            ),
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.berkas_ijazah_skl')
            ->assertJsonMissingPath('data.berkas_kk')
            ->assertJsonMissingPath('data.berkas_rapor');

        $pendaftar = Spmb::where('nisn', '7712345678')->firstOrFail();

        $this->assertNotEmpty($pendaftar->berkas_ijazah_skl);
        Storage::disk('local')->assertExists($pendaftar->berkas_ijazah_skl);
    }

}
