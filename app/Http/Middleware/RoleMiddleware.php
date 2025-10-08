<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Ambil nilai role dari beberapa kemungkinan kolom
        $userRole = null;
        if (isset($user->role)) {
            $userRole = $user->role;
        } elseif (isset($user->level)) {
            $userRole = $user->level;
        } elseif (isset($user->kode_user)) {
            $userRole = $user->kode_user;
        }

        // Jika masih null, dianggap tidak authorized
        if ($userRole === null) {
            abort(403, 'Unauthorized (no role info).');
        }

        // Cast ke string untuk perbandingan aman
        $userRole = (string)$userRole;

        if (!in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
