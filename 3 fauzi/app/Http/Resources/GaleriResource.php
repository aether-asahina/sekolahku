<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GaleriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'keterangan' => $this->keterangan,
            'album' => $this->album,
            'gambar_url' => $this->gambar_path ? Storage::disk('public')->url($this->gambar_path) : null,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
