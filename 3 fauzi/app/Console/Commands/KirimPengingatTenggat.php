<?php

namespace App\Console\Commands;

use App\Models\Tugas;
use App\Models\TugasSiswa;
use App\Models\User;
use App\Notifications\TenggatTugasNotification;
use Illuminate\Console\Command;

class KirimPengingatTenggat extends Command
{
    protected $signature = 'tugas:kirim-pengingat {--jam=24 : Kirim untuk tugas yang berakhir dalam sekian jam}';

    protected $description = 'Kirim notifikasi tenggat tugas kepada siswa yang belum menyelesaikannya';

    public function handle(): int
    {
        $jam = max(1, (int) $this->option('jam'));
        $terkirim = 0;

        Tugas::whereBetween('tenggat', [now(), now()->addHours($jam)])
            ->chunkById(100, function ($daftarTugas) use (&$terkirim) {
                foreach ($daftarTugas as $tugas) {
                    User::where('role', 'siswa')
                        ->where('kelas_id', $tugas->kelas_id)
                        ->each(function (User $siswa) use ($tugas, &$terkirim) {
                            $baris = TugasSiswa::firstOrCreate([
                                'tugas_id' => $tugas->id,
                                'siswa_id' => $siswa->id,
                            ]);

                            // Lewati bila sudah selesai atau pengingat sudah pernah dikirim.
                            if ($baris->status === 'selesai' || $baris->pengingat_dikirim_pada) {
                                return;
                            }

                            $siswa->notify(new TenggatTugasNotification($tugas));
                            $baris->update(['pengingat_dikirim_pada' => now()]);
                            $terkirim++;
                        });
                }
            });

        $this->info("Pengingat terkirim: {$terkirim}");

        return self::SUCCESS;
    }
}
