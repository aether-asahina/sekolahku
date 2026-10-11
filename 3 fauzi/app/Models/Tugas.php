<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = ['judul', 'deskripsi', 'mata_pelajaran', 'kelas_id', 'guru_id', 'tenggat', 'prioritas'];

    protected function casts(): array
    {
        return ['tenggat' => 'datetime'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function statusSiswa(): HasMany
    {
        return $this->hasMany(TugasSiswa::class, 'tugas_id');
    }
}
