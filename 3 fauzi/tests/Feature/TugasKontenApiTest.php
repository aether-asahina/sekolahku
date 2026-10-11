<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Berita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TugasKontenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_publik_hanya_melihat_berita_terbit(): void
    {
        Berita::create(['judul' => 'Terbit', 'slug' => 'terbit', 'konten' => 'isi', 'status' => 'terbit', 'diterbitkan_pada' => now()->subDay()]);
        Berita::create(['judul' => 'Draf', 'slug' => 'draf', 'konten' => 'isi', 'status' => 'draf']);

        $this->getJson('/api/v1/berita')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/berita/draf')->assertNotFound();
    }

    public function test_admin_boleh_membuat_berita_dan_guru_tidak(): void
    {
        $payload = ['judul' => 'Berita Uji', 'konten' => '<b>isi</b>', 'status' => 'terbit'];

        Sanctum::actingAs(User::factory()->create(['role' => 'guru']));
        $this->postJson('/api/v1/admin/berita', $payload)->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/v1/admin/berita', $payload)
            ->assertCreated()
            ->assertJsonPath('data.konten', 'isi'); // tag HTML dibersihkan
    }

    public function test_agenda_dapat_diunduh_sebagai_ics(): void
    {
        $agenda = Agenda::create(['judul' => 'Rapat; uji, koma', 'mulai' => now()->addDay()]);

        $res = $this->get('/api/v1/agenda/' . $agenda->id . '/ics')->assertOk();
        $this->assertStringContainsString('text/calendar', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('SUMMARY:Rapat\\; uji\\, koma', $res->getContent());
    }

    public function test_tugas_memerlukan_login(): void
    {
        $this->getJson('/api/v1/tugas')->assertUnauthorized();
    }
}
