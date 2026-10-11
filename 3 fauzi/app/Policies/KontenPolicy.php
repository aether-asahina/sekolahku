<?php

namespace App\Policies;

use App\Models\User;

/** Dipakai bersama oleh Berita, Agenda, dan Galeri: hanya admin sekolah yang boleh mengelola. */
class KontenPolicy
{
    public function kelola(User $u): bool
    {
        return $u->role === 'admin';
    }
}
