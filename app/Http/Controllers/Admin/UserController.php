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

        $q = User::query()->select(['id', 'username', 'email', 'role']);

        return DataTables::of($q)
            ->addColumn('action', function ($row) use ($adminCount) {
                $isSelf = auth()->id() === $row->id;
                $isLastAdmin = ($row->role === 'admin' && $adminCount <= 1);

                $canDelete = !($isSelf || $isLastAdmin);
                $canChangeRole = !($isSelf || $isLastAdmin);

                $editBtn = '
                <button type="button"
                    class="btn btn-outline-warning btn-sm mr-2 btn-edit"
                    data-id="' . $row->id . '"
                    data-can-change-role="' . ($canChangeRole ? '1' : '0') . '"
                    title="Edit">
                    <i class="fas fa-edit"></i>
                </button>';

                $delBtn = '
                <button type="button"
                    class="btn btn-outline-danger btn-sm btn-delete"
                    data-id="' . $row->id . '"
                    ' . ($canDelete ? '' : 'disabled') . '
                    title="' . ($canDelete ? 'Hapus' : 'Tidak dapat dihapus') . '">
                    <i class="fas fa-trash"></i>
                </button>';

                return '<div class="btn-group btn-group-sm se-2">' . $editBtn . $delBtn . '</div>';
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


    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'pekerja'])],
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $user = new User();
                // ISI NAME OTOMATIS => username (FIX ERROR name NOT NULL)
                $user->name = $validated['username'];
                $user->username = $validated['username'];
                $user->email = $validated['email'] ?? null;
                $user->role = $validated['role'];
                $user->password = Hash::make($validated['password']);
                $user->save();

                return response()->json(['ok' => true, 'id' => $user->id]);
            });
        } catch (\Throwable $e) {
            Log::error('User store failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Store failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            // role tidak wajib kalau tidak dikirim
            'role' => ['sometimes', 'required', Rule::in(['admin', 'pekerja'])],
        ]);

        $data = [
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
        ];

        // hanya update role kalau dikirim
        if ($request->has('role')) {
            $data['role'] = $validated['role'];
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json(['message' => 'Updated']);
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 422);
        }

        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Tidak boleh menghapus admin terakhir.'], 422);
            }
        }

        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }

}
