<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckPermission
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $userId = $user->id;

        // Ambil url dari request (contoh: quality/greensand)
        // sesuaikan basis url sesuai route prefix (tanpa leading slash)
        $path = ltrim($request->path(), '/');

        // Cek di connection aicc (User berada di aicc-master)
        try {
            $exists = DB::connection('mysql_aicc')
                ->table('v_user_permission') // sesuaikan nama view/tabel
                ->where('user_id', $userId)
                ->where('url', $path)
                ->exists();
        } catch (\Throwable $e) {
            // Jika v_user_permission tidak ada -> anggap permissive false
            $exists = false;
        }

        if (!$exists) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
