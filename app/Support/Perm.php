<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Perm
{
    protected static ?array $map = null;

    protected static function load(): void
    {
        if (self::$map !== null) return;

        $user = Auth::user();
        if (!$user) { self::$map = []; return; }

        $userIds = array_filter([$user->id ?? null, $user->kode_user ?? null]);

        $rows = DB::connection('mysql_aicc')
            ->table('v_user_permissions')
            ->whereIn('user_id', $userIds)
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $key = ltrim((string)$r->url, '/'); // normalisasi
            $map[$key] = (array) $r;
        }
        self::$map = $map;
    }

    public static function can(string $url, string $flag = 'can_access'): bool
    {
        self::load();

        // (Optional) bypass untuk admin
        $user = Auth::user();
        if ($user && (property_exists($user,'is_admin') ? $user->is_admin : (($user->role ?? '') === 'admin'))) {
            return true;
        }

        $key1 = ltrim($url, '/');
        $key2 = '/'.$key1;

        $row = self::$map[$key1] ?? self::$map[$key2] ?? null;
        return $row && !empty($row[$flag]);
    }
}
