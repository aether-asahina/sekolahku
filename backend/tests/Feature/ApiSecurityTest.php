<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_sia_registration_is_not_available(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Siswa Uji',
            'email' => 'siswa-uji@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'siswa-uji@example.com',
        ]);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login-uji@example.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'login-uji@example.com',
            'password' => 'Password123!',
        ])->assertOk()
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.id', $user->id)
          ->assertJsonStructure(['token']);
    }

    public function test_profile_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_student_cannot_save_attendance(): void
    {
        $student = User::factory()->create();
        $student->forceFill(['role' => 'siswa'])->save();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/absensi/simpan', [])
            ->assertForbidden();
    }

    public function test_teacher_cannot_manage_accounts(): void
    {
        $teacher = User::factory()->create();
        $teacher->forceFill(['role' => 'guru'])->save();

        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/admin/users', [
                'name' => 'Admin Palsu',
                'email' => 'admin-palsu@example.com',
                'password' => 'Password123456',
                'password_confirmation' => 'Password123456',
                'role' => 'admin',
            ])->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'admin-palsu@example.com',
        ]);
    }

    public function test_admin_can_create_teacher_account(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', [
                'name' => 'Guru Uji',
                'email' => 'guru-uji@example.com',
                'password' => 'Password123456',
                'password_confirmation' => 'Password123456',
                'role' => 'guru',
            ])->assertCreated()
              ->assertJsonPath('data.role', 'guru');

        $this->assertDatabaseHas('users', [
            'email' => 'guru-uji@example.com',
            'role' => 'guru',
        ]);
    }

    public function test_admin_passes_role_check_for_attendance_endpoint(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/absensi/simpan', [])
            ->assertUnprocessable();
    }
}
