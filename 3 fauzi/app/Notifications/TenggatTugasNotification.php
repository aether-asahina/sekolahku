<?php

namespace App\Notifications;

use App\Models\Tugas;
use Illuminate\Notifications\Notification;

class TenggatTugasNotification extends Notification
{
    public function __construct(public Tugas $tugas)
    {
    }

    /**
     * Saluran bawaan: database. Untuk WhatsApp gateway atau email (opsional, dikerjakan Anggota 1),
     * tambahkan saluran di sini tanpa mengubah modul lain.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipe' => 'tenggat_tugas',
            'tugas_id' => $this->tugas->id,
            'judul' => $this->tugas->judul,
            'tenggat' => $this->tugas->tenggat->toIso8601String(),
            'pesan' => sprintf(
                'Tugas "%s" berakhir pada %s.',
                $this->tugas->judul,
                $this->tugas->tenggat->format('d/m/Y H:i')
            ),
        ];
    }
}
