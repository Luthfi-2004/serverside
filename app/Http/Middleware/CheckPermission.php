<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckPermission
{
    // Endpoint yang tidak perlu dicek ke v_user_permissions
    private array $whitelist = [
        '',                       // dashboard
        'lookup/products',        // ajax lookup
        'debug/wip-products',     // debug
    ];

    // Root modul yang ada di menu permission
    private array $moduleRoots = [
        'greensand',
        'ace',
        'jsh-gfn',
        'aceline-gfn',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // 1) Whitelist cepat
        $rawPath = trim($request->path(), '/'); // "" | "greensand/data/mm1" | "lookup/products"
        if (in_array($rawPath, $this->whitelist, true)) {
            return $next($request);
        }
        if ($rawPath === '') { // dashboard
            return $next($request);
        }

        // 2) Normalisasi ke "menu root" (segment pertama yang dikenali)
        $seg = explode('/', $rawPath);
        $first = $seg[0] ?? '';
        $root  = in_array($first, $this->moduleRoots, true) ? $first : null;

        // Jika ketemu root modul, pakai root untuk cek permission; jika tidak, pakai rawPath apa adanya
        $target = $root ? "quality/{$root}" : $rawPath;

        // Bangun kandidat url untuk toleransi variasi slash
        $candidates = [
            ltrim($target, '/'),        // "quality/greensand"
            '/' . ltrim($target, '/'),  // "/quality/greensand"
        ];

        // 3) Siapkan kandidat user_id: id & kode_user (kalau ada)
        $userIds = [];
        if (isset($user->id)) {
            $userIds[] = $user->id;
        }
        if (!empty($user->kode_user)) {
            $userIds[] = $user->kode_user;
        }

        // 4) Cek ke v_user_permissions -> can_access=1
        try {
            $perm = DB::connection('mysql_aicc')
                ->table('v_user_permissions')
                ->whereIn('user_id', $userIds)
                ->where('can_access', 1)
                ->where(function ($q) use ($candidates) {
                    foreach ($candidates as $u) {
                        $q->orWhere('url', $u);
                    }
                })
                ->first();
        } catch (\Throwable $e) {
            abort(500, 'Permission view error: ' . $e->getMessage());
        }

        if (!$perm) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
