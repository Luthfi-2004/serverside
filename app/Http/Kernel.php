<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global middleware (dijalankan di setiap request).
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // Tambahkan middleware global lain jika perlu
    ];

    /**
     * Middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

            // BYPASS auth untuk DEV saja (dikontrol .env BYPASS_AUTH)
            \App\Http\Middleware\BypassAuthForTesting::class,
        ],

        'api' => [
            'throttle:60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Route middleware (dipanggil per-route via alias).
     */
    protected $routeMiddleware = [
        'auth'    => \App\Http\Middleware\Authenticate::class,
        'guest'   => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'verified'=> \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // Role-based gate (cek kolom role/level/kode_user)
        'role' => \App\Http\Middleware\RoleMiddleware::class,

        // Permission check ke v_user_permission (alias opsional kalau mau dipakai per-route)
        'check.permission' => \App\Http\Middleware\CheckPermission::class,

        // Alias opsional kalau mau panggil bypass per-route (biasanya cukup di group 'web' di atas)
        'bypass.auth' => \App\Http\Middleware\BypassAuthForTesting::class,
    ];
}
