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
        $q = User::query()->select(['id', 'username', 'email', 'role']);

        return DataTables::of($q)
            ->addColumn('action', function ($row) {
                return '
                <div class="btn-group btn-group-sm se-2">
                    <button type="button" class="btn btn-outline-warning btn-sm mr-2 btn-edit" data-id="'.$row->id.'" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-id="'.$row->id.'" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function json(User $user)
    {
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email'    => ['nullable', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'pekerja'])],
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $user = new User();
                // ISI NAME OTOMATIS => username (FIX ERROR name NOT NULL)
                $user->name     = $validated['username'];
                $user->username = $validated['username'];
                $user->email    = $validated['email'] ?? null;
                $user->role     = $validated['role'];
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
                'message' => 'Store failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'pekerja'])],
        ]);

        try {
            return DB::transaction(function () use ($validated, $user) {
                // Jaga konsistensi: kalau name kosong/NULL, isi ulang dari username
                $user->name     = $user->name ?: $validated['username'];
                $user->username = $validated['username'];
                $user->email    = $validated['email'] ?? null;
                $user->role     = $validated['role'];
                if (!empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->save();

                return response()->json(['ok' => true]);
            });
        } catch (\Throwable $e) {
            Log::error('User update failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('User delete failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Delete failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
