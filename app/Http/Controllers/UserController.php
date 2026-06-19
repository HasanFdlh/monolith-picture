<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->where('id', '!=', Auth::id())
            ->latest()
            ->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('User');

        return response()->json([
            'message' => 'User created',
            'user' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update($request->only('name', 'email'));

        return response()->json([
            'message' => 'User updated'
        ]);
    }

    public function destroy($id)
    {
        if ((int)$id === Auth::user()->id) {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun sendiri'
            ], 403);
        }

        User::findOrFail($id)->delete();

        return response()->json([
            'message' => 'User deleted'
        ]);
    }
}
