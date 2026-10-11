<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil dicabut.',
        ]);
    }

    // Semua endpoint manajemen akun di bawah ini khusus admin.
    public function users()
    {
        return response()->json([
            'success' => true,
            'data' => User::query()
                ->select('id', 'name', 'email', 'role', 'created_at')
                ->orderBy('id')
                ->paginate(20),
        ]);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255|unique:users,email',
            'password' => 'required|string|min:12|confirmed',
            'role' => ['required', Rule::in(['siswa', 'guru', 'admin'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        // Role tidak mass-assignable dari input biasa.
        $user->forceFill(['role' => $data['role']])->save();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuat oleh admin.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['siswa', 'guru', 'admin'])],
        ]);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak dapat mengubah role akunnya sendiri melalui endpoint ini.',
            ], 409);
        }

        if ($user->role === 'admin'
            && $data['role'] !== 'admin'
            && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Role admin terakhir tidak dapat dihapus.',
            ], 409);
        }

        $user->forceFill(['role' => $data['role']])->save();

        return response()->json([
            'success' => true,
            'message' => 'Role akun berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function deleteUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak dapat menghapus akunnya sendiri.',
            ], 409);
        }

        if ($user->role === 'admin'
            && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Admin terakhir tidak dapat dihapus.',
            ], 409);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus.',
        ]);
    }
}
