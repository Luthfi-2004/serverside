<?php

namespace App\Http\Controllers;

use App\Models\JshStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JshStandardController extends Controller
{
    // Halaman form tunggal untuk update standar
    public function index()
    {
        $std = JshStandard::query()->first();
        if (!$std) {
            // tanpa seeder: auto-create 1 baris kosong
            $std = JshStandard::create([]);
        }

        // daftar untuk rendering
        $groups = [
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

        return view('greensand.standards', compact('std', 'groups'));
    }

    // Update: tidak ada delete
    public function update(Request $r)
    {
        $std = JshStandard::query()->firstOrCreate([]);

        $clean = [];
        foreach (JshStandard::fields() as $f) {
            foreach (['_min', '_max'] as $suf) {
                $key = $f . $suf;
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
        foreach (JshStandard::fields() as $f) {
            $rules[$f . '_min'] = ['nullable', 'numeric'];
            $rules[$f . '_max'] = ['nullable', 'numeric'];
        }

        $v = \Validator::make($r->all(), $rules);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $data = [];
        foreach (JshStandard::fields() as $f) {
            $data[$f . '_min'] = $r->input($f . '_min');
            $data[$f . '_max'] = $r->input($f . '_max');
        }
        $std->update($data);

        return back()->with('status', 'Standards updated.');
    }

}
