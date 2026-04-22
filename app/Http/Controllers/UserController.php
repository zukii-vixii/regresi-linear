<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = User::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:160|unique:users,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,user',
        ]);
        $user = User::create($data);
        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:160|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6',
            'role'     => 'required|in:admin,user',
        ]);
        if (empty($data['password'])) unset($data['password']);
        $user->update($data);
        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }
        $user->delete();
        return response()->json(['ok' => true]);
    }
}
