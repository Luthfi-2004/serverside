<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GreensandExportFull;
use Carbon\Carbon;

class GreensandController extends Controller
{
    public function export(Request $request)
    {
        $start = $this->toYmd($request->input('start_date'));
        $end = $this->toYmd($request->input('end_date'));
        $shift = $request->input('shift');                     // 'D'|'S'|'N'|null
        $keyword = $request->input('keyword');                   // string|null
        $mm = $this->normalizeMm($request->input('mm'));    // 'MM1'|'MM2'|null

        // DEBUG sementara: lihat apa yg diterima controller
        // \Log::info('EXPORT params', compact('start','end','shift','keyword','mm'));

        return Excel::download(
            new GreensandExportFull($start, $end, $shift, $keyword, $mm),
            'greensand.xlsx'
        );
    }

    private function toYmd(?string $val): ?string
    {
        if (!$val)
            return null;
        try {
            return Carbon::createFromFormat('d-m-Y', $val)->toDateString();
        } catch (\Throwable $e) {
        }
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $val)->toDateString();
            } catch (\Throwable $e) {
            }
        }
        try {
            return Carbon::parse($val)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeMm($val): ?string
    {
        if ($val === null || $val === '')
            return null;
        $v = strtoupper(trim((string) $val));
        if ($v === '1' || $v === 'MM1')
            return 'MM1';
        if ($v === '2' || $v === 'MM2')
            return 'MM2';
        return null;
    }
}
