<?php

namespace App\Http\Controllers;

use App\Models\JshStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JshStandardController extends Controller
{
    private const PERM_URLS = [
        'greensand/jsh-greensand-std',
        'quality/greensand/jsh-greensand-std',
    ];

    public function index()
    {
        if (!$this->can('can_read')) {
            abort(403);
        }
        $canEdit = $this->can('can_edit');
        $std = JshStandard::query()->first();
        if (!$std) {
            $std = JshStandard::create([]);
        }
        $groupDefs = [
            'MM Sample' => [
                'mm_p' => 'P',
                'mm_c' => 'C',
                'mm_gt' => 'G.T',
                'mm_cb_mm' => 'CB MM',
                'mm_cb_lab' => 'CB Lab',
                'mm_m' => 'M',
                'mm_bakunetsu' => 'Bakunetsu',
                'mm_ac' => 'AC',
                'mm_tc' => 'TC',
                'mm_vsd' => 'Vsd',
                'mm_ig' => 'IG',
                'mm_cb_weight' => 'CB weight',
                'mm_tp50_weight' => 'TP 50 weight',
                'mm_tp50_height' => 'TP 50 Height',
                'mm_ssi' => 'SSI',
            ],
            'BC Sample' => [
                'bc12_cb' => 'BC12 CB',
                'bc12_m' => 'BC12 M',
                'bc11_ac' => 'BC11 AC',
                'bc11_vsd' => 'BC11 VSD',
                'bc16_cb' => 'BC16 CB',
                'bc16_m' => 'BC16 M',
            ],
        ];
        $fmt = function ($v) {
            if ($v === null || $v === '')
                return null;
            $s = str_replace(',', '.', (string) $v);
            if (is_numeric($s))
                $s = rtrim(rtrim($s, '0'), '.');
            return $s;
        };
        $groups = [];
        foreach ($groupDefs as $groupName => $fields) {
            $items = [];
            foreach ($fields as $key => $label) {
                $minKey = $key . '_min';
                $maxKey = $key . '_max';
                $items[] = [
                    'key' => $key,
                    'label' => $label,
                    'min' => $fmt($std->{$minKey}),
                    'max' => $fmt($std->{$maxKey}),
                ];
            }
            $groups[$groupName] = $items;
        }

        return view('greensand.standards', compact('groups', 'canEdit'));
    }

    public function update(Request $r)
    {
        if (!$this->can('can_edit')) {
            abort(403);
        }

        $std = JshStandard::query()->firstOrCreate([]);
        $data = [];
        foreach (JshStandard::fields() as $f) {
            $minKey = $f . '_min';
            $maxKey = $f . '_max';

            $min = $r->input($minKey);
            $max = $r->input($maxKey);

            $min = ($min === null || $min === '') ? null : str_replace(',', '.', (string) $min);
            $max = ($max === null || $max === '') ? null : str_replace(',', '.', (string) $max);

            if ($min !== null && $max !== null && is_numeric($min) && is_numeric($max) && (float) $min > (float) $max) {
                [$min, $max] = [$max, $min];
            }

            $data[$minKey] = $min;
            $data[$maxKey] = $max;
        }
        $rules = [];
        foreach (JshStandard::fields() as $f) {
            $rules[$f . '_min'] = ['nullable', 'numeric'];
            $rules[$f . '_max'] = ['nullable', 'numeric'];
        }
        $v = \Validator::make($data, $rules);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $std->update($data);
        return back()->with('status', 'Standards updated.');
    }
    private function can(string $flag): bool
    {
        if (config('app.bypass_auth', env('BYPASS_AUTH', false))) {
            return true;
        }

        $user = Auth::user();
        if (!$user)
            return false;

        $userIds = array_filter([$user->id ?? null, $user->kode_user ?? null]);

        $urls = [];
        foreach (self::PERM_URLS as $u) {
            $clean = ltrim($u, '/');
            $urls[] = $clean;           
            $urls[] = '/' . $clean;   
        }

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
