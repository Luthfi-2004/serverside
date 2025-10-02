<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AceStandard;

class AceStandardController extends Controller
{
    public function index()
    {
        $std = AceStandard::first();
        if (!$std) {
            $std = AceStandard::create([]);
        }
        return view('ace.standards', compact('std'));
    }

    public function update(Request $r)
    {
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
}
