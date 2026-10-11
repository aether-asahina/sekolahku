<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'lokasi' => $this->lokasi,
            'mulai' => $this->mulai?->toIso8601String(),
            'selesai' => $this->selesai?->toIso8601String(),
            'url_ics' => url('/api/v1/agenda/' . $this->id . '/ics'),
        ];
    }
}
