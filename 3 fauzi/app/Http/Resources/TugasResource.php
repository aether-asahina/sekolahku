<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TugasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'mata_pelajaran' => $this->mata_pelajaran,
            'prioritas' => $this->prioritas,
            'tenggat' => $this->tenggat?->toIso8601String(),
            'lewat_tenggat' => $this->tenggat?->isPast() ?? false,
            'kelas_id' => $this->kelas_id,
            'kelas' => $this->whenLoaded('kelas', fn () => [
                'id' => $this->kelas->id,
                'nama' => $this->kelas->nama,
            ]),
            'guru' => $this->whenLoaded('guru', fn () => [
                'id' => $this->guru->id,
                'nama' => $this->guru->name,
            ]),
            'status_saya' => $this->when(
                $this->relationLoaded('statusSiswa') && $request->user()?->role === 'siswa',
                fn () => $this->statusSiswa->first()?->status ?? 'belum'
            ),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
