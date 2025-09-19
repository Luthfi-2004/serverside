<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\GreensandJsh;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GreensandJshController extends Controller
{
    // endpoint
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

    // summary (min/max/avg + judge OK/NG)
    public function summaryAll(Request $request)
    {
        $q = GreensandJsh::query();

        // filter date & shift
        $d = $request->filled('date') ? $this->toYmd($request->date) : null;
        if ($d) {
            $q->whereDate('date', $d);
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

        // kolom numerik untuk summary
        $fields = [
            'mm_p','mm_c','mm_gt','mm_cb_mm','mm_cb_lab','mm_m','mm_bakunetsu','mm_ac','mm_tc',
            'mm_vsd','mm_ig','mm_cb_weight','mm_tp50_weight','mm_ssi',
            'add_m3','add_vsd','add_sc',
            'bc12_cb','bc12_m','bc11_ac','bc11_vsd','bc16_cb','bc16_m',
            'bc9_moist','bc10_moist','bc11_moist','bc9_temp','bc10_temp','bc11_temp',
        ];

        // spec range hardcode (isi sesuai standar kamu)
        $spec = [
            'mm_p' => ['min' => 220, 'max' => 260],
            'mm_c' => ['min' => 13.5, 'max' => 17.5],
            'mm_gt' => ['min' => 450, 'max' => 650],
            'mm_cb_mm'=> ['min'=> 40, 'max'=> 43],
            'mm_cb_lab'=> ['min'=> 32, 'max'=> 42],
            'mm_m' => ['min' => 2.45, 'max' => 2.85],
            'mm_bakunetsu' => ['min' => 20, 'max' => 85],
            'mm_ac' => ['min' => 6.7, 'max' => 7.3],
            'mm_tc' => ['min' => 9, 'max' => 11],
            'mm_vsd' => ['min' => 0.7, 'max' => 1.3],
            'mm_ig' => ['min' => 3, 'max' => 4],
            'mm_cb_weight' => ['min' => 163, 'max' => 170],
            'mm_tp50_weight' => ['min' => 141, 'max' => 144],
            'mm_ssi' => ['min' => 85, 'max' => 95],
        ];

        // agregasi min/max/avg
        $agg = [];
        foreach ($fields as $f) {
            $agg[] = DB::raw("MIN($f) as min_$f");
            $agg[] = DB::raw("MAX($f) as max_$f");
            $agg[] = DB::raw("AVG($f) as avg_$f");
        }
        $row = $q->select($agg)->first();

        $result = [];
        foreach ($fields as $f) {
            $min = $row->{"min_$f"} ?? null;
            $max = $row->{"max_$f"} ?? null;
            $avg = $row->{"avg_$f"} ?? null;
            $judge = null;

            if ($avg !== null && isset($spec[$f])) {
                $judge = ($avg >= $spec[$f]['min'] && $avg <= $spec[$f]['max']) ? "OK" : "NG";
            }

            $result[] = [
                'field' => $f,
                'min' => $min,
                'max' => $max,
                'avg' => $avg !== null ? round($avg, 2) : null,
                'spec' => $spec[$f] ?? null,
                'judge' => $judge,
            ];
        }

        return response()->json(['summary' => $result]);
    }

    // datatables
    private function makeResponse(Request $request, ?string $mmFilter)
    {
        try {
            $q = GreensandJsh::query();

            // mm filter
            if ($mmFilter) {
                $q->where('mm', $mmFilter);
            }

            // single date filter (exact day)
            $d = $request->filled('date') ? $this->toYmd($request->date) : null;
            if ($d) {
                $q->whereDate('date', $d);
            }

            // shift
            if ($request->filled('shift')) {
                $q->where('shift', $request->shift);
            }

            // keyword
            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $q->where(function ($x) use ($kw) {
                    $x->where('mix_ke', 'like', "%{$kw}%")
                      ->orWhere('rs_type', 'like', "%{$kw}%");
                });
            }

            // select
            $q->select([
                'id','date','shift','mm','mix_ke','mix_start','mix_finish',
                'mm_p','mm_c','mm_gt','mm_cb_mm','mm_cb_lab','mm_m','mm_bakunetsu','mm_ac','mm_tc',
                'mm_vsd','mm_ig','mm_cb_weight','mm_tp50_weight','mm_ssi',
                'add_m3','add_vsd','add_sc',
                'bc12_cb','bc12_m','bc11_ac','bc11_vsd','bc16_cb','bc16_m',
                'rs_time','rs_type',
                'bc9_moist','bc10_moist','bc11_moist','bc9_temp','bc10_temp','bc11_temp',
            ]);

            return DataTables::of($q)
                ->addColumn('action', function ($row) {
                    return '<div class="btn-group btn-group-sm se-2">
                        <button class="btn btn-outline-warning btn-sm mr-2 btn-edit-gs" data-id="'.$row->id.'" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-outline-danger btn-sm btn-delete-gs" data-id="'.$row->id.'" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div>';
                })
                ->editColumn('mm', function ($row) {
                    if ($row->mm === 'MM1') return 1;
                    if ($row->mm === 'MM2') return 2;
                    return $row->mm;
                })
                ->editColumn('date', function ($row) {
                    if (!$row->date) return null;
                    if ($row->date instanceof \DateTimeInterface) {
                        return $row->date->format('d-m-Y H:i:s');
                    }
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
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ], 500);
        }
    }

    // create
    public function store(Request $request)
    {
        $in = $request->all();
        $v = $this->validator($in, 'store');
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $mm    = $this->normalizeMm($in['mm'] ?? null);
        $shift = $in['shift'];
        $day   = $this->toYmd($in['date'] ?? null) ?: now('Asia/Jakarta')->toDateString();
        $mixKe = (int) ($in['mix_ke'] ?? 0);

        // unik per hari + shift + mm + mix_ke
        if ($this->isDuplicateMix($mm, $shift, $mixKe, $day, null)) {
            return response()->json([
                'errors' => ['mix_ke' => ["Mix ke {$mixKe} sudah dipakai untuk {$mm} di shift {$shift} pada {$day}."]]
            ], 422);
        }

        $data = $this->mapRequestToModel($in, null, $day);
        $row  = GreensandJsh::create($data);

        return response()->json(['message' => 'Created', 'id' => $row->id]);
    }

    // read
    public function show($id)
    {
        $row = GreensandJsh::findOrFail($id);
        return response()->json(['data' => $row]);
    }

    // update 
    public function update(Request $request, $id)
    {
        $row = GreensandJsh::findOrFail($id);
        $in  = $request->all();

        $v = $this->validator($in, 'update');
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $mm     = $this->normalizeMm($in['mm'] ?? $row->mm);
        $shift  = $row->shift; // lock shift on update
        $mixKe  = isset($in['mix_ke']) ? (int) $in['mix_ke'] : (int) $row->mix_ke;
        $day    = $this->dayString($row->date); // lock date (day part)

        if ($this->isDuplicateMix($mm, $shift, $mixKe, $day, (int) $row->id)) {
            return response()->json([
                'errors' => ['mix_ke' => ["Mix ke {$mixKe} sudah dipakai untuk {$mm} di shift {$shift} pada {$day}."]]
            ], 422);
        }

        $data = $this->mapRequestToModel($in, $row, $day, true, true);
        $row->update($data);

        return response()->json(['message' => 'Updated']);
    }

    // delete
    public function destroy($id)
    {
        $row = GreensandJsh::findOrFail($id);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // --- helpers ---
    private function normalizeMm($val): ?string
    {
        if ($val === null || $val === '') return null;
        $str = strtoupper((string) $val);
        if ($str === '1' || $str === 'MM1') return 'MM1';
        if ($str === '2' || $str === 'MM2') return 'MM2';
        return null;
    }

    private function validator(array $in, string $mode = 'store')
    {
        $in['mm'] = $this->normalizeMm($in['mm'] ?? null);
        return Validator::make($in, [
            'mm'         => 'required|in:MM1,MM2',
            'shift'      => 'required|in:D,S,N',
            'mix_ke'     => 'required|integer|min:1',
            'mix_start'  => 'nullable|date_format:H:i',
            'mix_finish' => 'nullable|date_format:H:i',
            'rs_time'    => 'nullable|date_format:H:i',
        ]);
    }

    private function dayString($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }
        return Carbon::parse($value)->toDateString();
    }

    private function isDuplicateMix(string $mm, string $shift, int $mixKe, string $dayYmd, ?int $ignoreId = null): bool
    {
        $q = GreensandJsh::query()
            ->whereDate('date', $dayYmd)
            ->where('shift', $shift)
            ->where('mm', $mm)
            ->where('mix_ke', $mixKe);

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }
        return $q->exists();
    }

    private function toYmd(?string $val): ?string
    {
        if (!$val) return null;
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $val)->toDateString();
            } catch (\Throwable $e) {}
        }
        try {
            return Carbon::parse($val)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mapRequestToModel(array $in, ?GreensandJsh $existing = null, ?string $dayYmd = null, bool $lockDate = false, bool $lockShift = false): array
    {
        $mm = $this->normalizeMm($in['mm'] ?? ($existing->mm ?? null));

        // date
        if ($existing && $lockDate) {
            try {
                $dateTime = $existing->date instanceof \DateTimeInterface
                    ? Carbon::instance($existing->date)->format('Y-m-d H:i:s')
                    : Carbon::parse($existing->date)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $dateTime = now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
        } else {
            $day     = $dayYmd ?: now('Asia/Jakarta')->toDateString();
            $timeNow = now('Asia/Jakarta')->format('H:i:s');
            $dateTime = "{$day} {$timeNow}";
        }

        // shift
        $shiftVal = $lockShift
            ? ($existing->shift ?? null)
            : ($in['shift'] ?? ($existing->shift ?? null));

        return [
            'date'            => $dateTime,
            'shift'           => $shiftVal,
            'mm'              => $mm,
            'mix_ke'          => $in['mix_ke'] ?? ($existing->mix_ke ?? null),
            'mix_start'       => $in['mix_start'] ?? ($existing->mix_start ?? null),
            'mix_finish'      => $in['mix_finish'] ?? ($existing->mix_finish ?? null),
            'mm_p'            => $in['mm_p'] ?? ($existing->mm_p ?? null),
            'mm_c'            => $in['mm_c'] ?? ($existing->mm_c ?? null),
            'mm_gt'           => $in['mm_gt'] ?? ($existing->mm_gt ?? null),
            'mm_cb_mm'        => $in['mm_cb_mm'] ?? ($existing->mm_cb_mm ?? null),
            'mm_cb_lab'       => $in['mm_cb_lab'] ?? ($existing->mm_cb_lab ?? null),
            'mm_m'            => $in['mm_m'] ?? ($existing->mm_m ?? null),
            'mm_bakunetsu'    => $in['mm_bakunetsu'] ?? ($existing->mm_bakunetsu ?? null),
            'mm_ac'           => $in['mm_ac'] ?? ($existing->mm_ac ?? null),
            'mm_tc'           => $in['mm_tc'] ?? ($existing->mm_tc ?? null),
            'mm_vsd'          => $in['mm_vsd'] ?? ($existing->mm_vsd ?? null),
            'mm_ig'           => $in['mm_ig'] ?? ($existing->mm_ig ?? null),
            'mm_cb_weight'    => $in['mm_cb_weight'] ?? ($existing->mm_cb_weight ?? null),
            'mm_tp50_weight'  => $in['mm_tp50_weight'] ?? ($existing->mm_tp50_weight ?? null),
            'mm_ssi'          => $in['mm_ssi'] ?? ($existing->mm_ssi ?? null),
            'add_m3'          => $in['add_m3'] ?? ($existing->add_m3 ?? null),
            'add_vsd'         => $in['add_vsd'] ?? ($existing->add_vsd ?? null),
            'add_sc'          => $in['add_sc'] ?? ($existing->add_sc ?? null),
            'bc12_cb'         => $in['bc12_cb'] ?? ($existing->bc12_cb ?? null),
            'bc12_m'          => $in['bc12_m'] ?? ($existing->bc12_m ?? null),
            'bc11_ac'         => $in['bc11_ac'] ?? ($existing->bc11_ac ?? null),
            'bc11_vsd'        => $in['bc11_vsd'] ?? ($existing->bc11_vsd ?? null),
            'bc16_cb'         => $in['bc16_cb'] ?? ($existing->bc16_cb ?? null),
            'bc16_m'          => $in['bc16_m'] ?? ($existing->bc16_m ?? null),
            'rs_time'         => $in['rs_time'] ?? ($existing->rs_time ?? null),
            'rs_type'         => $in['rs_type'] ?? ($existing->rs_type ?? null),
            'bc9_moist'       => $in['bc9_moist'] ?? ($existing->bc9_moist ?? null),
            'bc10_moist'      => $in['bc10_moist'] ?? ($existing->bc10_moist ?? null),
            'bc11_moist'      => $in['bc11_moist'] ?? ($existing->bc11_moist ?? null),
            'bc9_temp'        => $in['bc9_temp'] ?? ($existing->bc9_temp ?? null),
            'bc10_temp'       => $in['bc10_temp'] ?? ($existing->bc10_temp ?? null),
            'bc11_temp'       => $in['bc11_temp'] ?? ($existing->bc11_temp ?? null),
        ];
    }
}
