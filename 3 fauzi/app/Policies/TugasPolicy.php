<?php

namespace App\Policies;

use App\Models\Tugas;
use App\Models\User;

class TugasPolicy
{
    public function viewAny(User $u): bool
    {
        return in_array($u->role, ['admin', 'guru', 'siswa', 'kepala_sekolah'], true);
    }

    public function view(User $u, Tugas $t): bool
    {
        return match ($u->role) {
            'admin', 'kepala_sekolah' => true,
            'guru' => (int) $t->guru_id === (int) $u->id,
            'siswa' => (int) $t->kelas_id === (int) $u->kelas_id,
            default => false,
        };
    }

    public function create(User $u): bool
    {
        return in_array($u->role, ['admin', 'guru'], true);
    }

    public function update(User $u, Tugas $t): bool
    {
        return $u->role === 'admin' || ($u->role === 'guru' && (int) $t->guru_id === (int) $u->id);
    }

    public function delete(User $u, Tugas $t): bool
    {
        return $this->update($u, $t);
    }

    public function lihatProgres(User $u, Tugas $t): bool
    {
        return $u->role === 'kepala_sekolah' || $this->update($u, $t);
    }

    public function ubahStatus(User $u, Tugas $t): bool
    {
        return $u->role === 'siswa' && (int) $t->kelas_id === (int) $u->kelas_id;
    }
}
