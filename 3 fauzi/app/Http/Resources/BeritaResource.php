<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BeritaResource extends JsonResource
{
    protected bool $denganKonten = false;

    /** Sertakan isi lengkap (untuk halaman detail dan respons admin). */
    public function denganKonten(): static
    {
        $this->denganKonten = true;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'ringkasan' => $this->ringkasan,
            'konten' => $this->when($this->denganKonten, $this->konten),
            'gambar_url' => $this->gambar_path ? Storage::disk('public')->url($this->gambar_path) : null,
            'status' => $this->status,
            'diterbitkan_pada' => $this->diterbitkan_pada?->toIso8601String(),
            'penulis' => $this->whenLoaded('penulis', fn () => $this->penulis?->name),
        ];
    }
}
