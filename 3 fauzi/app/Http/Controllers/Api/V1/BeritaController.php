<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BeritaResource;
use App\Models\Berita;
use App\Support\Teks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    /** Publik: hanya berita berstatus terbit. */
    public function index(Request $request)
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:50'], 'cari' => ['sometimes', 'string', 'max:100']]);

        $q = Berita::terbit()->with('penulis:id,name')->orderByDesc('diterbitkan_pada');
        if ($cari = $request->query('cari')) {
            $q->where('judul', 'like', '%' . addcslashes($cari, '%_\\') . '%');
        }

        return BeritaResource::collection($q->paginate($request->integer('per_page', 9)));
    }

    public function show(string $slug): BeritaResource
    {
        $berita = Berita::terbit()->with('penulis:id,name')->where('slug', $slug)->firstOrFail();

        return (new BeritaResource($berita))->denganKonten();
    }

    /** Admin: semua berita termasuk draf. */
    public function daftarAdmin(Request $request)
    {
        Gate::authorize('kelola', Berita::class);

        $q = Berita::query()->orderByDesc('updated_at');
        if ($request->filled('status')) {
            $q->where('status', $request->query('status') === 'terbit' ? 'terbit' : 'draf');
        }

        return BeritaResource::collection($q->paginate($request->integer('per_page', 15)));
    }

    public function tampilAdmin(Berita $berita): BeritaResource
    {
        Gate::authorize('kelola', Berita::class);

        return (new BeritaResource($berita))->denganKonten();
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('kelola', Berita::class);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'ringkasan' => ['nullable', 'string', 'max:300'],
            'konten' => ['required', 'string', 'max:20000'],
            'status' => ['required', 'in:draf,terbit'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $data = Teks::bersih($data, ['judul', 'ringkasan', 'konten']);

        $berita = Berita::create([
            'judul' => $data['judul'],
            'slug' => Berita::slugUnik($data['judul']),
            'ringkasan' => $data['ringkasan'] ?? null,
            'konten' => $data['konten'],
            'status' => $data['status'],
            'diterbitkan_pada' => $data['status'] === 'terbit' ? now() : null,
            'gambar_path' => $request->file('gambar')?->store('berita', 'public'),
            'penulis_id' => $request->user()->id,
        ]);

        return (new BeritaResource($berita))->denganKonten()->response()->setStatusCode(201);
    }

    /** Dengan unggahan berkas, kirim POST multipart dengan field _method=PUT. */
    public function update(Request $request, Berita $berita): BeritaResource
    {
        Gate::authorize('kelola', Berita::class);

        $data = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'ringkasan' => ['nullable', 'string', 'max:300'],
            'konten' => ['sometimes', 'required', 'string', 'max:20000'],
            'status' => ['sometimes', 'required', 'in:draf,terbit'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_gambar' => ['sometimes', 'boolean'],
        ]);
        $data = Teks::bersih($data, ['judul', 'ringkasan', 'konten']);
        unset($data['gambar'], $data['hapus_gambar']);

        if ($request->hasFile('gambar')) {
            $this->hapusBerkas($berita->gambar_path);
            $data['gambar_path'] = $request->file('gambar')->store('berita', 'public');
        } elseif ($request->boolean('hapus_gambar')) {
            $this->hapusBerkas($berita->gambar_path);
            $data['gambar_path'] = null;
        }

        if (($data['status'] ?? $berita->status) === 'terbit' && ! $berita->diterbitkan_pada) {
            $data['diterbitkan_pada'] = now();
        }

        $berita->update($data); // slug sengaja tidak berubah agar tautan lama tetap valid

        return (new BeritaResource($berita))->denganKonten();
    }

    public function destroy(Berita $berita): Response
    {
        Gate::authorize('kelola', Berita::class);

        $this->hapusBerkas($berita->gambar_path);
        $berita->delete();

        return response()->noContent();
    }

    private function hapusBerkas(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
