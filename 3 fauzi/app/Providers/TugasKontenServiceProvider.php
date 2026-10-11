<?php

namespace App\Providers;

use App\Models\Agenda;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Tugas;
use App\Policies\KontenPolicy;
use App\Policies\TugasPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TugasKontenServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Tugas::class, TugasPolicy::class);
        Gate::policy(Berita::class, KontenPolicy::class);
        Gate::policy(Agenda::class, KontenPolicy::class);
        Gate::policy(Galeri::class, KontenPolicy::class);

        Route::middleware('api')->prefix('api/v1')->group(base_path('routes/tugas_konten.php'));

        // Pengingat tenggat dijalankan tiap jam (butuh cron `schedule:run` di server).
        $this->app->booted(function () {
            $this->app->make(Schedule::class)
                ->command('tugas:kirim-pengingat')
                ->hourly()
                ->withoutOverlapping();
        });
    }
}
