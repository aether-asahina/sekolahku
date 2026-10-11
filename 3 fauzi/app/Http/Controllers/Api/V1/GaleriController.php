<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GaleriResource;
use App\Models\Galeri;
use App\Support\Teks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class GaleriController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['album' => ['sometimes', 'string', 'max:80'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']]);

        $q = Galeri::query()->latest();
        if ($request->filled('album')) {
            $q->where('album', $request->query('album'));
        }

        return GaleriResource::collection($q->paginate($request->integer('per_page', 12)));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('kelola', Galeri::class);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:300'],
            'album' => ['nullable', 'string', 'max:80'],
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $data = Teks::bersih($data, ['judul', 'keterangan', 'album']);

        $galeri = Galeri::create([
            'judul' => $data['judul'],
            'keterangan' => $data['keterangan'] ?? null,
            'album' => $data['album'] ?? null,
            'gambar_path' => $request->file('gambar')->store('galeri', 'public'),
        ]);

        return (new GaleriResource($galeri))->response()->setStatusCode(201);
    }

    /** Dengan unggahan berkas, kirim POST multipart dengan field _method=PUT. */
    public function update(Request $request, Galeri $galeri): GaleriResource
    {
        Gate::authorize('kelola', Galeri::class);

        $data = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:300'],
            'album' => ['nullable', 'string', 'max:80'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $data = Teks::bersih($data, ['judul', 'keterangan', 'album']);
        unset($data['gambar']);

        if ($request->hasFile('gambar')) {
            if ($galeri->gambar_path) {
                Storage::disk('public')->delete($galeri->gambar_path);
            }
            $data['gambar_path'] = $request->file('gambar')->store('galeri', 'public');
        }

        $galeri->update($data);

        return new GaleriResource($galeri);
    }

    public function destroy(Galeri $galeri): Response
    {
        Gate::authorize('kelola', Galeri::class);

        if ($galeri->gambar_path) {
            Storage::disk('public')->delete($galeri->gambar_path);
        }
        $galeri->delete();

        return response()->noContent();
    }
}
