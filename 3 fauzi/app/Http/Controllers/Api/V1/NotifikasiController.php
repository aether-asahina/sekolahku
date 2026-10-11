<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $p = $user->notifications()->latest()->paginate(20);

        return response()->json([
            'data' => $p->getCollection()->map(fn ($n) => [
                'id' => $n->id,
                'tipe' => $n->data['tipe'] ?? null,
                'pesan' => $n->data['pesan'] ?? '',
                'data' => $n->data,
                'dibaca' => $n->read_at !== null,
                'dibuat_pada' => $n->created_at->toIso8601String(),
            ])->values(),
            'meta' => [
                'halaman_saat_ini' => $p->currentPage(),
                'halaman_terakhir' => $p->lastPage(),
                'total' => $p->total(),
                'belum_dibaca' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function baca(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $n->markAsRead();

        return response()->json(['data' => ['id' => $n->id, 'dibaca' => true]]);
    }

    public function bacaSemua(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['data' => ['belum_dibaca' => 0]]);
    }
}
