<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AceStandard;
use App\Support\Perm;

class AceStandardController extends Controller
{
    private string $permUrlNoQual = 'greensand/ace-greensand-std';
    private string $permUrlWithQual = 'quality/greensand/ace-greensand-std';

    public function index()
    {
        if (
            !Perm::can($this->permUrlNoQual, 'can_read')
            && !Perm::can($this->permUrlWithQual, 'can_read')
        ) {
            abort(403);
        }

        $std = AceStandard::first();
        if (!$std)
            $std = AceStandard::create([]);
        $canEdit =
            Perm::can($this->permUrlNoQual, 'can_edit') ||
            Perm::can($this->permUrlWithQual, 'can_edit');

        return view('ace.standards', compact('std', 'canEdit'));
    }

    public function update(Request $r)
    {
        // cek can_edit juga pakai 2 URL
        if (
            !Perm::can($this->permUrlNoQual, 'can_edit')
            && !Perm::can($this->permUrlWithQual, 'can_edit')
        ) {
            abort(403);
        }

        $std = AceStandard::query()->firstOrCreate([]);

        $keys = [
            'p',
            'c',
            'gt',
            'cb_lab',
            'moisture',
            'bakunetsu',
            'ac',
            'tc',
            'vsd',
            'ig',
            'cb_weight',
            'tp50_weight',
            'ssi',
            'bc13_cb',
            'bc13_c',
            'bc13_m',
        ];

        $data = [];
        foreach ($keys as $k) {
            $minKey = $k . '_min';
            $maxKey = $k . '_max';

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
        foreach ($keys as $k) {
            $rules[$k . '_min'] = ['nullable', 'numeric'];
            $rules[$k . '_max'] = ['nullable', 'numeric'];
        }
        $v = \Validator::make($data, $rules);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $std->update($data);

        return back()->with('status', 'Standards updated.');
    }
}
