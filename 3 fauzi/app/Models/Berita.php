<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Berita extends Model
{
    protected $table = 'berita';

    protected $fillable = ['judul', 'slug', 'ringkasan', 'konten', 'gambar_path', 'status', 'diterbitkan_pada', 'penulis_id'];

    protected function casts(): array
    {
        return ['diterbitkan_pada' => 'datetime'];
    }

    public function scopeTerbit(Builder $q): Builder
    {
        return $q->where('status', 'terbit')->where('diterbitkan_pada', '<=', now());
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    public static function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul) ?: 'berita';
        $slug = $dasar;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $dasar . '-' . $i++;
        }
        return $slug;
    }
}
