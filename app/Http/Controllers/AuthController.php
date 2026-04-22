<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            // Jangan bocorkan keberadaan email — tetap balas sukses generik.
            return response()->json([
                'ok'      => true,
                'message' => 'Jika email terdaftar, tautan reset telah dibuat.',
            ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = url('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        // Karena aplikasi offline tanpa SMTP, kembalikan token & URL langsung
        // agar user dapat menyalinnya. (Untuk produksi, kirim via email.)
        return response()->json([
            'ok'        => true,
            'message'   => 'Tautan reset password berhasil dibuat. Berlaku 60 menit.',
            'reset_url' => $resetUrl,
            'token'     => $token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        if (! $row || ! Hash::check($data['token'], $row->token)) {
            throw ValidationException::withMessages([
                'token' => ['Token reset tidak valid.'],
            ]);
        }

        if (now()->diffInMinutes($row->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            throw ValidationException::withMessages([
                'token' => ['Token reset sudah kedaluwarsa. Silakan minta ulang.'],
            ]);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak ditemukan.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        // Cabut semua token aktif user supaya sesi lama tidak terpakai.
        $user->tokens()->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Password berhasil direset. Silakan login kembali.',
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:160|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => User::query()->count() === 0 ? 'admin' : 'user',
        ]);

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'             => 'required|string|max:120',
            'email'            => 'required|email|max:160|unique:users,email,' . $user->id,
            'password'         => 'nullable|string|min:6|confirmed',
            'current_password' => 'nullable|string',
        ]);

        if (! empty($data['password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password lama tidak sesuai.'],
                ]);
            }
            $user->password = $data['password'];
        }
        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return response()->json($user);
    }
}
