<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckPermission
{
    private array $whitelist = [
        '', 'lookup/products', 'debug/wip-products',
    ];

    /**
     * Pemetaan "nama route/prefix" -> "URL izin di v_user_permissions"
     * Sesuaikan dgn yang kamu punya di DB (lihat screenshot kamu).
     */
    private function resolvePermUrl(Request $request): ?string
    {
        $route = $request->route();
        $name  = $route?->getName();         // contoh: "greensand.data.mm1", "ace.index", "greensand.standards"
        $path  = trim($request->path(), '/'); // contoh: "greensand/data/mm1"

        // 1) By route name (paling akurat)
        if ($name) {
            // JSH daily (greensand.* kecuali standards)
            if (str_starts_with($name, 'greensand.') && !in_array($name, ['greensand.standards','greensand.standards.update'])) {
                return 'quality/greensand/jsh-greensand-check';
            }
            if ($name === 'greensand.standards' || $name === 'greensand.standards.update') {
                return 'quality/greensand/jsh-greensand-std';
            }

            // JSH GFN
            if (str_starts_with($name, 'jshgfn.')) {
                return 'quality/greensand/jsh-gfn';
            }

            // ACE daily (ace.* kecuali standards)
            if (str_starts_with($name, 'ace.') && !in_array($name, ['ace.standards','ace.standards.update'])) {
                return 'quality/greensand/ace-greensand-check';
            }
            if ($name === 'ace.standards' || $name === 'ace.standards.update') {
                return 'quality/greensand/ace-greensand-std';
            }

            // ACELINE GFN
            if (str_starts_with($name, 'acelinegfn.')) {
                return 'quality/greensand/ace-gfn';
            }
        }

        // 2) Fallback by path (kalau perlu)
        if (str_starts_with($path, 'greensand/standards')) {
            return 'quality/greensand/jsh-greensand-std';
        }
        if (str_starts_with($path, 'greensand')) {
            return 'quality/greensand/jsh-greensand-check';
        }
        if (str_starts_with($path, 'jsh-gfn')) {
            return 'quality/greensand/jsh-gfn';
        }
        if ($path === 'ace' || str_starts_with($path, 'ace/summary') || str_starts_with($path, 'ace/data') || preg_match('#^ace/\d+#', $path)) {
            return 'quality/greensand/ace-greensand-check';
        }
        if (str_starts_with($path, 'ace/standards')) {
            return 'quality/greensand/ace-greensand-std';
        }
        if (str_starts_with($path, 'aceline-gfn')) {
            return 'quality/greensand/ace-gfn';
        }

        // kalau gak terpetakan, balikin null -> akan dianggap tidak punya izin
        return null;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) return redirect()->route('login');

        $rawPath = trim($request->path(), '/');
        if ($rawPath === '' || in_array($rawPath, $this->whitelist, true)) {
            return $next($request);
        }

        $permUrl = $this->resolvePermUrl($request);
        if (!$permUrl) {
            abort(403, 'Permission URL mapping not found.');
        }

        // kandidat url: tanpa & dengan leading slash (untuk toleransi)
        $candidates = [ ltrim($permUrl, '/'), '/' . ltrim($permUrl, '/') ];

        $user = auth()->user();
        $userIds = array_filter([$user->id ?? null, $user->kode_user ?? null]);

        try {
            $has = DB::connection('mysql_aicc')
                ->table('v_user_permissions')
                ->whereIn('user_id', $userIds)
                ->where('can_access', 1)
                ->where(function ($q) use ($candidates) {
                    foreach ($candidates as $u) {
                        $q->orWhere('url', $u);
                    }
                })
                ->exists();
        } catch (\Throwable $e) {
            abort(500, 'Permission view error: ' . $e->getMessage());
        }

        if (!$has) abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        return $next($request);
    }
}
