<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return redirect()->route('admin.users.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['nullable','string','max:100'],
            'username' => ['required','string','max:100','unique:users,username'],
            'email'    => ['nullable','email','max:150','unique:users,email'],
            'password' => ['required','string','min:6'],
            'role'     => ['required', Rule::in(['admin','pekerja'])],
        ]);

        // Auto-isi name bila kosong
        if (empty($data['name'])) {
            $data['name'] = $data['username'];
        }

        $user = new User();
        $user->name     = $data['name'];
        $user->username = $data['username'];
        $user->email    = $data['email'] ?? null;
        $user->password = Hash::make($data['password']);
        $user->role     = $data['role'];
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user)
    {
        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['nullable','string','max:100'],
            'username' => ['required','string','max:100', Rule::unique('users','username')->ignore($user->id)],
            'email'    => ['nullable','email','max:150', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6'],
            'role'     => ['required', Rule::in(['admin','pekerja'])],
        ]);

        $user->name     = $data['name'] ?: $data['username'];
        $user->username = $data['username'];
        $user->email    = $data['email'] ?? null;
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->role     = $data['role'];
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        // Jangan hapus diri sendiri
        if (auth()->id() === $user->id) {
            return back()->with('success', 'Tidak dapat menghapus akun yang sedang login.');
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
