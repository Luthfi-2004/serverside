<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class BypassAuthForTesting
{
    public function handle($request, Closure $next)
    {
        // Aktifkan hanya kalau BYPASS_AUTH=true di .env
        if (config('app.bypass_auth', env('BYPASS_AUTH', false))) {
            if (!Auth::check()) {
                // Pastikan kita pakai connection yang sama dengan model User
                $connection = (new User())->getConnectionName() ?: 'mysql';

                // Cek apakah kolom is_active ada di tabel tb_user pada koneksi tersebut
                $hasIsActive = false;
                try {
                    $hasIsActive = Schema::connection($connection)->hasColumn('tb_user', 'is_active');
                } catch (\Throwable $e) {
                    // ignore, anggap tidak ada kolom is_active
                    $hasIsActive = false;
                }

                // Ambil user aktif kalau ada, atau fallback ambil first()
                $query = User::query();
                if ($hasIsActive) {
                    $query->where('is_active', 1);
                }

                $u = $query->first();

                // Jika ada user, login tanpa menyimpan di DB
                if ($u) {
                    Auth::login($u);
                }
            }
        }

        return $next($request);
    }
}
