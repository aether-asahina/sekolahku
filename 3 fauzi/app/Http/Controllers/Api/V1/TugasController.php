<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TugasResource;
use App\Models\Tugas;
use App\Models\TugasSiswa;
use App\Models\User;
use App\Support\Teks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TugasController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Tugas::class);

        $request->validate([
            'status' => ['sometimes', 'in:belum,dikerjakan,selesai'],
            'prioritas' => ['sometimes', 'in:rendah,sedang,tinggi'],
            'kelas_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $q = Tugas::query()->with(['kelas:id,nama', 'guru:id,name'])->orderBy('tenggat');

        if ($user->role === 'siswa') {
            $q->where('kelas_id', $user->kelas_id)
                ->with(['statusSiswa' => fn ($s) => $s->where('siswa_id', $user->id)]);

            if ($status = $request->query('status')) {
                $q->where(function ($w) use ($status, $user) {
                    $w->whereHas('statusSiswa', fn ($s) => $s->where('siswa_id', $user->id)->where('status', $status));
                    if ($status === 'belum') {
                        $w->orWhereDoesntHave('statusSiswa', fn ($s) => $s->where('siswa_id', $user->id));
                    }
                });
            }
        } elseif ($user->role === 'guru') {
            $q->where('guru_id', $user->id);
        }

        if ($user->role !== 'siswa' && $request->filled('kelas_id')) {
            $q->where('kelas_id', $request->integer('kelas_id'));
        }
        if ($request->filled('prioritas')) {
            $q->where('prioritas', $request->query('prioritas'));
        }

        return TugasResource::collection($q->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Tugas::class);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'mata_pelajaran' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'tenggat' => ['required', 'date', 'after:now'],
            'prioritas' => ['required', 'in:rendah,sedang,tinggi'],
        ]);
        $data = Teks::bersih($data, ['judul', 'deskripsi', 'mata_pelajaran']);
        $data['guru_id'] = $request->user()->id;

        $tugas = Tugas::create($data)->load(['kelas:id,nama', 'guru:id,name']);

        return (new TugasResource($tugas))->response()->setStatusCode(201);
    }

    public function show(Request $request, Tugas $tugas): TugasResource
    {
        Gate::authorize('view', $tugas);

        $tugas->load(['kelas:id,nama', 'guru:id,name']);
        if ($request->user()->role === 'siswa') {
            $tugas->load(['statusSiswa' => fn ($s) => $s->where('siswa_id', $request->user()->id)]);
        }

        return new TugasResource($tugas);
    }

    public function update(Request $request, Tugas $tugas): TugasResource
    {
        Gate::authorize('update', $tugas);

        $data = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'mata_pelajaran' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['sometimes', 'required', 'integer', 'exists:kelas,id'],
            'tenggat' => ['sometimes', 'required', 'date'],
            'prioritas' => ['sometimes', 'required', 'in:rendah,sedang,tinggi'],
        ]);
        $data = Teks::bersih($data, ['judul', 'deskripsi', 'mata_pelajaran']);

        $tugas->fill($data);
        $tenggatBerubah = $tugas->isDirty('tenggat');
        $tugas->save();

        // Tenggat baru berarti pengingat boleh dikirim ulang.
        if ($tenggatBerubah) {
            $tugas->statusSiswa()->update(['pengingat_dikirim_pada' => null]);
        }

        return new TugasResource($tugas->load(['kelas:id,nama', 'guru:id,name']));
    }

    public function destroy(Tugas $tugas): Response
    {
        Gate::authorize('delete', $tugas);
        $tugas->delete();

        return response()->noContent();
    }

    /** Siswa mengubah status tugasnya sendiri. */
    public function ubahStatus(Request $request, Tugas $tugas): JsonResponse
    {
        Gate::authorize('ubahStatus', $tugas);

        $data = $request->validate(['status' => ['required', 'in:belum,dikerjakan,selesai']]);

        $baris = TugasSiswa::updateOrCreate(
            ['tugas_id' => $tugas->id, 'siswa_id' => $request->user()->id],
            [
                'status' => $data['status'],
                'diselesaikan_pada' => $data['status'] === 'selesai' ? now() : null,
            ]
        );

        return response()->json(['data' => [
            'tugas_id' => $tugas->id,
            'status' => $baris->status,
            'diselesaikan_pada' => $baris->diselesaikan_pada?->toIso8601String(),
        ]]);
    }

    /** Guru melihat progres seluruh siswa di kelas tugas tersebut. */
    public function progres(Tugas $tugas): JsonResponse
    {
        Gate::authorize('lihatProgres', $tugas);

        $siswa = User::where('role', 'siswa')
            ->where('kelas_id', $tugas->kelas_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $status = $tugas->statusSiswa()->get()->keyBy('siswa_id');

        $daftar = $siswa->map(function ($s) use ($status) {
            $baris = $status->get($s->id);
            return [
                'siswa_id' => $s->id,
                'nama' => $s->name,
                'status' => $baris?->status ?? 'belum',
                'diselesaikan_pada' => $baris?->diselesaikan_pada?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $daftar->values(),
            'ringkasan' => [
                'total' => $daftar->count(),
                'belum' => $daftar->where('status', 'belum')->count(),
                'dikerjakan' => $daftar->where('status', 'dikerjakan')->count(),
                'selesai' => $daftar->where('status', 'selesai')->count(),
            ],
        ]);
    }
}
