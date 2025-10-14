<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AceStandard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AceStandardController extends Controller
{
    // URL permission khusus untuk halaman standards ACE
    private string $permUrl = 'quality/ace/standards';

    public function index()
    {
        if (!$this->can('can_read')) abort(403);

        $std = AceStandard::first();
        if (!$std) $std = AceStandard::create([]);
        return view('ace.standards', compact('std'));
    }

    public function update(Request $r)
    {
        if (!$this->can('can_edit')) abort(403);

        $std = AceStandard::query()->firstOrCreate([]);

        $keys = [
            'p','c','gt','cb_lab','moisture','bakunetsu','ac','tc','vsd','ig',
            'cb_weight','tp50_weight','ssi',
            'bc13_cb','bc13_c','bc13_m',
        ];

        $data = [];
        foreach ($keys as $k) {
            $minKey = $k.'_min';
            $maxKey = $k.'_max';

            $min = $r->input($minKey);
            $max = $r->input($maxKey);

            $min = ($min === null || $min === '') ? null : str_replace(',', '.', (string)$min);
            $max = ($max === null || $max === '') ? null : str_replace(',', '.', (string)$max);

            if ($min !== null && $max !== null && is_numeric($min) && is_numeric($max) && (float)$min > (float)$max) {
                $tmp = $min; $min = $max; $max = $tmp;
            }

            $data[$minKey] = $min;
            $data[$maxKey] = $max;
        }

        $rules = [];
        foreach ($keys as $k) {
            $rules[$k.'_min'] = ['nullable','numeric'];
            $rules[$k.'_max'] = ['nullable','numeric'];
        }
        $v = \Validator::make($data, $rules);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $std->update($data);

        return back()->with('status', 'Standards updated.');
    }

    /** ===== Permission helper khusus controller ini ===== */
    private function can(string $flag): bool
    {
        if (config('app.bypass_auth', env('BYPASS_AUTH', false))) return true;

        $user = Auth::user();
        if (!$user) return false;

        $userIds = array_filter([$user->id ?? null, $user->kode_user ?? null]);
        $urls = [ltrim($this->permUrl, '/'), '/'.ltrim($this->permUrl, '/')];

        try {
            return DB::connection('mysql_aicc')
                ->table('v_user_permissions')
                ->whereIn('user_id', $userIds)
                ->whereIn('url', $urls)
                ->where($flag, 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
