<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'kelas',
        'tanggal',
        'data_kehadiran',
    ];

    // Otomatis mengubah JSON di database menjadi array PHP saat dipanggil
    protected $casts = [
        'data_kehadiran' => 'array',
    ];
}