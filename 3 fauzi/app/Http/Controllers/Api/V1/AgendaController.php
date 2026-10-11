<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendaResource;
use App\Models\Agenda;
use App\Support\Teks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'akan_datang' => ['sometimes', 'boolean'],
            'dari' => ['sometimes', 'date'],
            'sampai' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $q = Agenda::query()->orderBy('mulai');
        if ($request->boolean('akan_datang')) {
            $q->where('mulai', '>=', now()->startOfDay());
        }
        if ($request->filled('dari')) {
            $q->where('mulai', '>=', $request->date('dari')->startOfDay());
        }
        if ($request->filled('sampai')) {
            $q->where('mulai', '<=', $request->date('sampai')->endOfDay());
        }

        return AgendaResource::collection($q->paginate($request->integer('per_page', 10)));
    }

    public function show(Agenda $agenda): AgendaResource
    {
        return new AgendaResource($agenda);
    }

    /** Unduh agenda sebagai berkas kalender (.ics) untuk ditambahkan ke kalender perangkat. */
    public function ics(Agenda $agenda): Response
    {
        $akhir = $agenda->selesai ?? $agenda->mulai->copy()->addHour();
        $fmt = fn ($waktu) => $waktu->copy()->utc()->format('Ymd\THis\Z');

        $baris = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SekolahKu Digital//Agenda//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:agenda-' . $agenda->id . '@sekolahku.digital',
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($agenda->mulai),
            'DTEND:' . $fmt($akhir),
            'SUMMARY:' . $this->escape($agenda->judul),
        ];
        if ($agenda->deskripsi) {
            $baris[] = 'DESCRIPTION:' . $this->escape($agenda->deskripsi);
        }
        if ($agenda->lokasi) {
            $baris[] = 'LOCATION:' . $this->escape($agenda->lokasi);
        }
        $baris[] = 'END:VEVENT';
        $baris[] = 'END:VCALENDAR';

        $isi = implode("\r\n", array_map([$this, 'lipat'], $baris)) . "\r\n";

        return response($isi, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="agenda-' . $agenda->id . '.ics"',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('kelola', Agenda::class);

        $agenda = Agenda::create($this->validasi($request));

        return (new AgendaResource($agenda))->response()->setStatusCode(201);
    }

    /** PUT mengganti seluruh field (kirim data lengkap). */
    public function update(Request $request, Agenda $agenda): AgendaResource
    {
        Gate::authorize('kelola', Agenda::class);

        $agenda->update($this->validasi($request));

        return new AgendaResource($agenda);
    }

    public function destroy(Agenda $agenda): Response
    {
        Gate::authorize('kelola', Agenda::class);
        $agenda->delete();

        return response()->noContent();
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:3000'],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'mulai' => ['required', 'date'],
            'selesai' => ['nullable', 'date', 'after_or_equal:mulai'],
        ]);

        return Teks::bersih($data, ['judul', 'deskripsi', 'lokasi']);
    }

    /** Escape teks sesuai RFC 5545 dan cegah penyisipan baris baru ke berkas .ics. */
    private function escape(string $teks): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            $teks
        );
    }

    /** Lipat baris panjang (maks. 75 oktet) sesuai RFC 5545. */
    private function lipat(string $baris): string
    {
        $hasil = '';
        while (strlen($baris) > 73) {
            $potong = mb_strcut($baris, 0, 73, 'UTF-8');
            $hasil .= $potong . "\r\n ";
            $baris = substr($baris, strlen($potong));
        }

        return $hasil . $baris;
    }
}
