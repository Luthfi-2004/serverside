<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Process;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ServersideController extends Controller
{
    /** =======================
     *  DATA ENDPOINTS (DT)
     *  ======================= */
    public function dataMM1(Request $request)
    {
        return $this->makeResponse($request, 'MM1');
    }

    public function dataMM2(Request $request)
    {
        return $this->makeResponse($request, 'MM2');
    }

    public function dataAll(Request $request)
    {
        return $this->makeResponse($request, null);
    }

    private function makeResponse(Request $request, ?string $mmFilter)
    {
        try {
            $q = Process::query();

            // Filter tab MM (DB menyimpan 'MM1'/'MM2')
            if ($mmFilter) {
                $q->where('mm', $mmFilter);
            }

            // Filter tanggal/shift/keyword
            if ($request->filled('start_date')) {
                $q->whereDate('date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $q->whereDate('date', '<=', $request->end_date);
            }
            if ($request->filled('shift')) {
                $q->where('shift', $request->shift);
            }
            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $q->where(function ($x) use ($kw) {
                    $x->where('mix_ke', 'like', "%{$kw}%")
                        ->orWhere('rs_type', 'like', "%{$kw}%");
                });
            }

            // Kolom yang dipakai DataTables (URUTAN harus cocok dengan JS)
            $q->select([
                'id',
                'date',
                'shift',
                'mm',
                'mix_ke',
                'mix_start',
                'mix_finish',
                'mm_p',
                'mm_c',
                'mm_gt',
                'mm_cb_mm',
                'mm_cb_lab',
                'mm_m',
                'mm_bakunetsu',
                'mm_ac',
                'mm_tc',
                'mm_vsd',
                'mm_ig',
                'mm_cb_weight',
                'mm_tp50_weight',
                'mm_ssi',
                'add_m3',
                'add_vsd',
                'add_sc',
                'bc12_cb',
                'bc12_m',
                'bc11_ac',
                'bc11_vsd',
                'bc16_cb',
                'bc16_m',
                'rs_time',
                'rs_type',
                'bc9_moist',
                'bc10_moist',
                'bc11_moist',
                'bc9_temp',
                'bc10_temp',
                'bc11_temp',
            ]);

            return DataTables::of($q)
                ->addColumn('action', function ($row) {
                    return '<div class="btn-group btn-group-sm se-2">
        <button class="btn btn-warning btn-sm mr-2 btn-edit-gs" data-id="' . $row->id . '" title="Edit">
            <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-sm btn-delete-gs" data-id="' . $row->id . '" title="Hapus">
            <i class="fas fa-trash"></i>
        </button>
    </div>';
                })

                // Tampilkan 1/2 di tabel (DB tetap simpan 'MM1'/'MM2')
                ->editColumn('mm', function ($row) {
                    if ($row->mm === 'MM1')
                        return 1;
                    if ($row->mm === 'MM2')
                        return 2;
                    return $row->mm; // fallback
                })
                // Format tanggal
                ->editColumn('date', function ($row) {
                    if (!$row->date)
                        return null;
                    if ($row->date instanceof \DateTimeInterface)
                        return $row->date->format('d-m-Y H:i:s');
                    try {
                        return Carbon::parse($row->date)->format('d-m-Y H:i:s');
                    } catch (\Throwable $e) {
                        return (string) $row->date;
                    }
                })
                ->rawColumns(['action'])
                ->toJson();

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /** =======================
     *  CRUD JSON (Modal)
     *  ======================= */

    // Create
    public function store(Request $request)
    {
        $in = $request->all();
        $v = $this->validator($in);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $this->mapRequestToProcess($in);
        $row = Process::create($data);

        return response()->json(['message' => 'Created', 'id' => $row->id]);
    }

    // Read single (untuk Edit modal)
    public function show($id)
    {
        $row = Process::findOrFail($id);
        return response()->json(['data' => $row]);
    }

    // Update
    public function update(Request $request, $id)
    {
        $in = $request->all();
        $v = $this->validator($in);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $row = Process::findOrFail($id);
        $data = $this->mapRequestToProcess($in);
        $row->update($data);

        return response()->json(['message' => 'Updated']);
    }

    // Delete
    public function destroy($id)
    {
        $row = Process::findOrFail($id);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Normalisasi nilai 'mm' dari form (1/2) → 'MM1'/'MM2'
    private function normalizeMm($val): ?string
    {
        if ($val === null || $val === '')
            return null;
        $str = strtoupper((string) $val);
        if ($str === '1' || $str === 'MM1')
            return 'MM1';
        if ($str === '2' || $str === 'MM2')
            return 'MM2';
        return null;
    }

    // Validasi input modal
    private function validator(array $in)
    {
        // injek hasil normalisasi mm untuk validasi
        $in['mm'] = $this->normalizeMm($in['mm'] ?? null);

        return \Validator::make($in, [
            'mm' => 'required|in:MM1,MM2',
            'shift' => 'required|in:D,S,N',
            'mix_ke' => 'required|integer|min:1',

            // TANGGAL + JAM
            'process_date' => 'required|date',           // contoh: 2025-09-12
            'mix_start' => 'nullable|date_format:H:i',// contoh: 08:15
            'mix_finish' => 'nullable|date_format:H:i',
            'rs_time' => 'nullable|date_format:H:i',

            // field numerik lain opsional
        ]);
    }

    // Mapping form → kolom tabel Process
    private function mapRequestToProcess(array $in): array
    {
        $mm = $this->normalizeMm($in['mm'] ?? null);

        // Kolom 'date' (datetime) = process_date + mix_start (jika ada) else jam 00:00:00
        $processDate = $in['process_date'] ?? now()->toDateString();   // 'Y-m-d'
        $mixStart = $in['mix_start'] ?? null;                        // 'H:i'
        $dateTime = $processDate . ' ' . ($mixStart ? "$mixStart:00" : '00:00:00');

        return [
            'date' => $dateTime,
            'shift' => $in['shift'] ?? null,
            'mm' => $mm,
            'mix_ke' => $in['mix_ke'] ?? null,
            'mix_start' => $in['mix_start'] ?? null,
            'mix_finish' => $in['mix_finish'] ?? null,

            // MM Sample
            'mm_p' => $in['mm_p'] ?? null,
            'mm_c' => $in['mm_c'] ?? null,
            'mm_gt' => $in['mm_gt'] ?? null,
            'mm_cb_mm' => $in['mm_cb_mm'] ?? null,
            'mm_cb_lab' => $in['mm_cb_lab'] ?? null,
            'mm_m' => $in['mm_m'] ?? null,
            'mm_bakunetsu' => $in['mm_bakunetsu'] ?? null,
            'mm_ac' => $in['mm_ac'] ?? null,
            'mm_tc' => $in['mm_tc'] ?? null,
            'mm_vsd' => $in['mm_vsd'] ?? null,
            'mm_ig' => $in['mm_ig'] ?? null,
            'mm_cb_weight' => $in['mm_cb_weight'] ?? null,
            'mm_tp50_weight' => $in['mm_tp50_weight'] ?? null,
            'mm_ssi' => $in['mm_ssi'] ?? null,

            // Additives
            'add_m3' => $in['add_m3'] ?? null,
            'add_vsd' => $in['add_vsd'] ?? null,
            'add_sc' => $in['add_sc'] ?? null,

            // BC Sample
            'bc12_cb' => $in['bc12_cb'] ?? null,
            'bc12_m' => $in['bc12_m'] ?? null,
            'bc11_ac' => $in['bc11_ac'] ?? null,
            'bc11_vsd' => $in['bc11_vsd'] ?? null,
            'bc16_cb' => $in['bc16_cb'] ?? null,
            'bc16_m' => $in['bc16_m'] ?? null,

            // Return Sand
            'rs_time' => $in['rs_time'] ?? null,
            'rs_type' => $in['rs_type'] ?? null,
            'bc9_moist' => $in['bc9_moist'] ?? null,
            'bc10_moist' => $in['bc10_moist'] ?? null,
            'bc11_moist' => $in['bc11_moist'] ?? null,
            'bc9_temp' => $in['bc9_temp'] ?? null,
            'bc10_temp' => $in['bc10_temp'] ?? null,
            'bc11_temp' => $in['bc11_temp'] ?? null,
        ];
    }

}
