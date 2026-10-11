<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_rejects_invalid_status(): void
    {
        $teacher = User::factory()->create();
        $teacher->forceFill(['role' => 'guru'])->save();

        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/absensi/simpan', [
                'kelas' => 'X IPA 1',
                'tanggal' => '2026-10-11',
                'data_kehadiran' => [
                    [
                        'nama' => 'Siswa Uji',
                        'status' => 'Hadirkan',
                    ],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('absensi', [
            'kelas' => 'X IPA 1',
        ]);
    }

    public function test_attendance_recap_matches_exact_class(): void
    {
        $student = User::factory()->create();
        $student->forceFill(['role' => 'siswa'])->save();

        Absensi::create([
            'kelas' => 'X IPA 1',
            'tanggal' => '2026-10-10',
            'data_kehadiran' => [
                ['nama' => 'Siswa A', 'status' => 'Hadir'],
            ],
        ]);

        Absensi::create([
            'kelas' => 'XI IPA 1',
            'tanggal' => '2026-10-10',
            'data_kehadiran' => [
                ['nama' => 'Siswa B', 'status' => 'Izin'],
            ],
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/absensi/rekap/X IPA 1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kelas', 'X IPA 1');
    }
}
