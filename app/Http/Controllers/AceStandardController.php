<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AceStandard;

class AceStandardController extends Controller
{
    /**
     * Tampilkan form tunggal untuk edit semua min/max ACE.
     * Kalau belum ada row, auto-create kosong dulu.
     */
    public function index()
    {
        $std = AceStandard::first();
        if (!$std) {
            $std = AceStandard::create([]); // buat baris kosong
        }
        return view('ace.standards', compact('std'));
    }

    /**
     * Simpan update min/max (satu tombol Save).
     */
    public function update(Request $r)
    {
        $std = AceStandard::query()->firstOrCreate([]);

        $allKeys = [
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

        $clean = [];
        foreach ($allKeys as $k) {
            foreach (['_min', '_max'] as $suf) {
                $key = $k . $suf;
                $val = $r->input($key);
                if ($val === null || $val === '') {
                    $clean[$key] = null;
                    continue;
                }
                $val = str_replace(',', '.', $val);
                $clean[$key] = $val;
            }
        }
        $r->merge($clean);

        $rules = [];
        foreach ($allKeys as $k) {
            $rules[$k . '_min'] = ['nullable', 'numeric'];
            $rules[$k . '_max'] = ['nullable', 'numeric'];
        }
        $v = \Validator::make($r->all(), $rules);
        if ($v->fails())
            return back()->withErrors($v)->withInput();

        $data = [];
        foreach ($allKeys as $k) {
            $data[$k . '_min'] = $r->input($k . '_min');
            $data[$k . '_max'] = $r->input($k . '_max');
        }
        $std->update($data);

        return back()->with('status', 'Standards updated.');
    }

}
