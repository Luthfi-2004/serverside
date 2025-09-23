<?php

namespace App\Http\Controllers;

use App\Models\AceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AceLineController extends Controller
{
    /* ========= Helpers ========= */

    private function ymd(?string $s): ?string
    {
        if (!$s)
            return null;
        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function detectShift(?Carbon $now = null): string
    {
        $now = $now ? $now->copy() : Carbon::now('Asia/Jakarta');
        $h = (int) $now->format('H');
        if ($h >= 6 && $h < 16)
            return 'D';
        if ($h >= 16 && $h < 22)
            return 'S';
        return 'N';
    }

    private function rules(): array
    {
        $numeric = [
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
            'dw29_vas',
            'dw29_debu',
            'dw31_vas',
            'dw31_id',
            'dw31_moldex',
            'dw31_sc',
            'bc13_cb',
            'bc13_c',
            'bc13_m',
            'no_mix',
        ];

        $rules = [
            'date' => ['nullable', 'date'],
            'shift' => ['nullable', 'in:D,S,N'],
            'product_type_id' => ['nullable', 'integer'],
            'product_type_name' => ['nullable', 'string', 'max:100'],
            'number' => ['nullable', 'integer', 'min:0'],
            'no_mix' => ['nullable', 'integer', 'min:0'],
            'sample_start' => ['nullable', 'date_format:H:i'],
            'sample_finish' => ['nullable', 'date_format:H:i'],
            'machine_no' => ['nullable', 'string', 'max:50'],
        ];
        foreach ($numeric as $f)
            $rules[$f] = ['nullable', 'numeric'];
        return $rules;
    }

    private function fillable(Request $r): array
    {
        $data = $r->only([
            'date',
            'shift',
            'product_type_id',
            'product_type_name',
            'number',
            'no_mix',
            'sample_start',
            'sample_finish',
            'machine_no',
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
            'dw29_vas',
            'dw29_debu',
            'dw31_vas',
            'dw31_id',
            'dw31_moldex',
            'dw31_sc',
            'bc13_cb',
            'bc13_c',
            'bc13_m',
        ]);
        if (!empty($data['date']))
            $data['date'] = $this->ymd($data['date']);
        return $data;
    }

    /* ========= DataTables ========= */

    public function data(Request $request)
    {
        $q = AceLine::query();

        if ($d = $this->ymd($request->get('date')))
            $q->whereDate('date', $d);
        if ($s = $request->get('shift'))
            $q->where('shift', $s);
        if ($pt = $request->get('product_type_id'))
            $q->where('product_type_id', $pt);

        return DataTables::of($q)
            ->filter(function ($builder) use ($request) {
                $search = $request->input('search.value');
                if ($search) {
                    $builder->where(function ($w) use ($search) {
                        $w->where('product_type_name', 'like', "%{$search}%")
                            ->orWhere('machine_no', 'like', "%{$search}%");
                    });
                }
            })
            ->editColumn('date', fn(AceLine $r) => $r->date ? Carbon::parse($r->date)->format('Y-m-d') : null)
            ->make(true);
    }

    /* ========= CRUD ========= */

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $data = $this->fillable($request);

        if (empty($data['date']))
            $data['date'] = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        if (empty($data['shift']))
            $data['shift'] = $this->detectShift();

        if (empty($data['number'])) {
            $max = AceLine::whereDate('date', $data['date'])->max('number');
            $data['number'] = ($max ?? 0) + 1;
        }

        $row = AceLine::create($data);
        return response()->json(['ok' => true, 'id' => $row->id, 'message' => 'Saved']);
    }

    public function show($id)
    {
        return response()->json(AceLine::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules());
        $row = AceLine::findOrFail($id);

        $data = $this->fillable($request);
        if (!array_key_exists('number', $data))
            unset($data['number']);
        if (!array_key_exists('date', $data))
            unset($data['date']);
        if (!array_key_exists('shift', $data))
            unset($data['shift']);

        $row->update($data);
        return response()->json(['ok' => true, 'id' => $row->id, 'message' => 'Updated']);
    }

    public function destroy($id)
    {
        AceLine::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'message' => 'Deleted']);
    }

    /* ========= SUMMARY (MIN / MAX / AVG / JUDGE) ========= */
    public function summary(Request $request)
    {
        $q = AceLine::query();
        if ($d = $this->ymd($request->get('date')))
            $q->whereDate('date', $d);
        if ($s = $request->get('shift'))
            $q->where('shift', $s);
        if ($pt = $request->get('product_type_id'))
            $q->where('product_type_id', $pt);

        // Kolom numeric yang dihitung (urut sesuai tabel, kecuali machine_no (text) dan number (biar nggak muncul di footer))
        $numericCols = [
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
            'dw29_vas',
            'dw29_debu',
            'dw31_vas',
            'dw31_id',
            'dw31_moldex',
            'dw31_sc',
            'no_mix',
            'bc13_cb',
            'bc13_c',
            'bc13_m',
        ];

        // Build SELECT agregat: AVG/MIN/MAX + COUNT(bukan null) utk deteksi "ada data"
        $parts = [];
        foreach ($numericCols as $c) {
            $parts[] = "AVG($c) as avg_$c";
            $parts[] = "MIN($c) as min_$c";
            $parts[] = "MAX($c) as max_$c";
            $parts[] = "COUNT($c) as cnt_$c";
        }
        $agg = $q->selectRaw(implode(', ', $parts))->first();

        $fmt2 = fn($v) => is_null($v) ? '' : number_format((float) $v, 2, '.', '');

        // Susun object keyed (biar JS aman dan nggak geser)
        $rowMin = [];
        $rowMax = [];
        $rowAvg = [];
        $present = []; // ada data pada kolom tsb?

        foreach ($numericCols as $name) {
            $rowMin[$name] = $fmt2($agg?->{"min_$name"});
            $rowMax[$name] = $fmt2($agg?->{"max_$name"});
            $rowAvg[$name] = $fmt2($agg?->{"avg_$name"});
            $present[$name] = (int) ($agg?->{"cnt_$name"} ?? 0) > 0;
        }

        /* ====== JUDGE RULES (pakai AVG) ====== */
        $judgeBase = 'avg_'; // bisa diganti 'min_' / 'max_' kalau mau
        // SPEC
        $spec = [
            'p' => ['min' => 150, 'max' => 240],
            'c' => ['min' => 16, 'max' => 21],
            'gt' => ['min' => 400, 'max' => 700],
            'cb_lab' => ['min' => 33, 'max' => 43],
            'moisture' => ['min' => 3, 'max' => 4],
            'bakunetsu' => ['max' => 80],                 // max only
            'ac' => ['min' => 8, 'max' => 11],
            'tc' => ['min' => 10, 'max' => 16],
            'vsd' => ['min' => 0.2, 'max' => 0.7],
            'ig' => ['min' => 2, 'max' => 3],
            'cb_weight' => ['min' => 169, 'max' => 181],
            'ssi' => ['min' => 90],                 // min only
            // kolom lain tidak dinilai → judge kosong
        ];

        $judgeVal = function ($val, array $rule): string {
            if ($val === null)
                return '';             // <— kosong kalau belum ada data
            if (isset($rule['min']) && $val < $rule['min'])
                return 'NG';
            if (isset($rule['max']) && $val > $rule['max'])
                return 'NG';
            return 'OK';
        };

        $rowJudge = [];
        $okFlags = [];

        foreach ($numericCols as $name) {
            // Hanya nilai yang ada spec-nya yang dinilai; lainnya kosong
            if (!array_key_exists($name, $spec)) {
                $rowJudge[$name] = '';
                continue;
            }

            // Kalau kolom ini belum ada data sama sekali → kosong (bukan NG)
            if (!$present[$name]) {
                $rowJudge[$name] = '';
                continue;
            }

            $val = $agg?->{$judgeBase . $name};
            $j = $judgeVal(is_null($val) ? null : (float) $val, $spec[$name]);
            $rowJudge[$name] = $j;

            // hitung overall hanya dari kolom yang dinilai & ada data
            if ($j !== '')
                $okFlags[] = ($j === 'OK');
        }

        $overall = count($okFlags) ? (array_sum($okFlags) === count($okFlags) ? 'OK' : 'NG') : '—';

        return response()->json([
            // object keyed by field (aman buat JS, nggak tergantung jumlah kolom di depan)
            'rows' => [
                'min' => $rowMin,
                'max' => $rowMax,
                'avg' => $rowAvg,
                'judge' => $rowJudge,
            ],
            // info tambahan untuk JS (opsional): mana kolom yang ada datanya
            'present' => $present,
            // label keseluruhan
            'overall' => $overall,
        ]);
    }

    /* ========= Export (stub) ========= */
    public function export(Request $request)
    {
        return response()->json([
            'todo' => 'Implement Excel export (Maatwebsite\\Excel)',
            'filters' => [
                'date' => $this->ymd($request->get('date')),
                'shift' => $request->get('shift'),
                'product_type_id' => $request->get('product_type_id'),
            ],
        ]);
    }
}
