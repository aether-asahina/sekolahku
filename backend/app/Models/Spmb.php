<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spmb extends Model
{
    use HasFactory;

    protected $table = 'spmb';

    protected $fillable = [
        'nomor_pendaftaran',
        'nama_lengkap',
        'nisn',
        'nik',
        'asal_sekolah',
        'tanggal_lahir',
        'jalur_pendaftaran',
        'berkas_ijazah_skl',
        'berkas_kk',
        'berkas_rapor',
        'status',
    ];
}