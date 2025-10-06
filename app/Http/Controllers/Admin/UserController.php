<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    public function data()
    {
        $adminCount = User::where('role', 'admin')->count();

        $q = User::select(['id', 'username', 'email', 'role']);
        return DataTables::of($q)
            ->addColumn('action', function ($row) use ($adminCount) {
                $isSelf = auth()->id() === $row->id;
                $isLastAdmin = ($row->role === 'admin' && $adminCount <= 1);
                $canDelete = !($isSelf || $isLastAdmin);

                return '
                <div class="btn-group btn-group-sm se-2">
                    <button type="button" class="btn btn-outline-warning btn-sm mr-2 btn-edit" data-id="' . $row->id . '" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-id="' . $row->id . '" ' . ($canDelete ? '' : 'disabled') . ' title="' . ($canDelete ? 'Hapus' : 'Tidak dapat dihapus') . '">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function json(User $user)
    {
        $adminCount = User::where('role', 'admin')->count();
        $isSelf = auth()->id() === $user->id;
        $isLastAdmin = ($user->role === 'admin' && $adminCount <= 1);

        return response()->json([
            'user' => $user,
            'meta' => [
                'can_edit_role' => !($isSelf || $isLastAdmin),
                'can_delete' => !($isSelf || $isLastAdmin),
            ],
        ]);
    }

    // CREATE: tanpa konfirmasi password
    public function store(Request $r)
    {
        $r->validate([
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'pekerja'])],
        ]);

        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $r->username; // isi otomatis supaya tidak null
            $user->username = $r->username;
            $user->email = $r->email ?? null;
            $user->role = $r->role;
            $user->password = Hash::make($r->password);
            $user->save();
            DB::commit();

            return response()->json(['ok' => true, 'id' => $user->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User store failed: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan user.'], 500);
        }
    }

    // UPDATE: tanpa konfirmasi password
    public function update(Request $r, User $user)
    {
        $r->validate([
            'username' => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['sometimes', Rule::in(['admin', 'pekerja'])],
        ]);

        $data = [
            'username' => $r->username,
            'email' => $r->email ?? null,
        ];
        if ($r->has('role')) {
            $data['role'] = $r->role;
        }
        if ($r->filled('password')) {
            $data['password'] = Hash::make($r->password);
        }

        $user->update($data);
        return response()->json(['message' => 'Updated']);
    }

    // DELETE: tetap minta konfirmasi password
    public function destroy(Request $r, User $user)
    {
        $r->validate(['confirm_password' => ['required', 'string']]);

        if (!Hash::check($r->confirm_password, auth()->user()->password)) {
            return response()->json(['errors' => ['confirm_password' => ['Password konfirmasi salah.']]], 422);
        }

        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        $adminCount = User::where('role', 'admin')->count();
        if ($user->role === 'admin' && $adminCount <= 1) {
            return response()->json(['message' => 'Tidak boleh menghapus admin terakhir.'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
