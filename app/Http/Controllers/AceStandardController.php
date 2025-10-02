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
        $std = AceStandard::firstOrFail();

        // Ambil semua input yang relevan (biar flexible, validasi numeric/nullable)
        $rules = [];
        foreach ($std->getFillable() as $f) {
            $rules[$f] = ['nullable','numeric'];
        }
        $data = $r->validate($rules);

        $std->update($data);

        return back()->with('status','Standards updated.');
    }
}
