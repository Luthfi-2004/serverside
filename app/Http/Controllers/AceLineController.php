<?php

namespace App\Http\Controllers;

use App\Models\AceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AceLineExportFull;

class AceLineController extends Controller
{
    // Util Tools
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

    // Datetime Parse
    private function ymdHisFromInput(?string $s): ?string
    {
        if (!$s)
            return null;
        try {
            foreach ([
                'Y-m-d H:i:s',
                'd-m-Y H:i:s',
                'Y/m/d H:i:s',
                'd/m/Y H:i:s',
                'Y-m-d H:i',
                'd-m-Y H:i',
                'Y/m/d H:i',
                'd/m/Y H:i',
            ] as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $s)->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                }
            }
            foreach (['Y-m-d', 'd-m-Y', 'Y/m/d', 'd/m/Y'] as $fmt) {
                try {
                    $d = Carbon::createFromFormat($fmt, $s)->format('Y-m-d');
                    $t = Carbon::now('Asia/Jakarta')->format('H:i:s');
                    return "{$d} {$t}";
                } catch (\Throwable $e) {
                }
            }
            $dt = Carbon::parse($s);
            if ($dt->format('H:i:s') === '00:00:00' && preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}$/', trim($s))) {
                $date = $dt->format('Y-m-d');
                $t = Carbon::now('Asia/Jakarta')->format('H:i:s');
                return "{$date} {$t}";
            }
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    // Shift Detect
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

    // Input Rules
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
            'most',
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

    // Data Map
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
            'most',
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

        if (!empty($data['date'])) {
            $dt = $this->ymdHisFromInput($data['date']);
            $data['date'] = $dt ?: Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        return $data;
    }

    // Reorder Day
    private function renumberDay(string $ymd): void
    {
        DB::transaction(function () use ($ymd) {
            $rows = AceLine::whereRaw('DATE(`date`) = ?', [$ymd])
                ->orderBy('date', 'asc')->orderBy('id', 'asc')
                ->lockForUpdate()->get(['id', 'number', 'date']);

            $n = 1;
            foreach ($rows as $row) {
                if ((int) $row->number !== $n) {
                    AceLine::where('id', $row->id)->update(['number' => $n]);
                }
                $n++;
            }
        });
    }

    // Table Data
    public function data(Request $request)
    {
        $q = AceLine::query();

        if ($d = $this->ymd($request->get('date')))
            $q->whereRaw('DATE(`date`) = ?', [$d]);
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
            ->editColumn('date', function (AceLine $r) {
                // Show YmdHis
                if ($r->date) {
                    try {
                        return Carbon::parse($r->date)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        return (string) $r->date;
                    }
                }
                return $r->created_at
                    ? Carbon::parse($r->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    : null;
            })
            ->addColumn('created_time', function (AceLine $r) {
                // Old Fallback
                return $r->created_at
                    ? Carbon::parse($r->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    : null;
            })
            ->make(true);
    }

    // CRUD Ops
    public function store(Request $request)
    {
        $request->validate($this->rules());
        $data = $this->fillable($request);

        // Ensure Datetime
        if (empty($data['date'])) {
            $data['date'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
        } else {
            try {
                $dt = Carbon::parse($data['date']);
                if ($dt->format('H:i:s') === '00:00:00') {
                    $data['date'] = $dt->format('Y-m-d') . ' ' . Carbon::now('Asia/Jakarta')->format('H:i:s');
                }
            } catch (\Throwable $e) {
                $data['date'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
        }

        // Auto Shift
        if (empty($data['shift']))
            $data['shift'] = $this->detectShift();

        // Auto Number
        if (empty($data['number'])) {
            $day = Carbon::parse($data['date'])->format('Y-m-d');
            $max = AceLine::whereRaw('DATE(`date`) = ?', [$day])->max('number');
            $data['number'] = ((int) ($max ?? 0)) + 1;
        }

        $row = AceLine::create($data);

        // Append Only
        return response()->json(['ok' => true, 'id' => $row->id, 'message' => 'Saved']);
    }

    // Show One
    public function show($id)
    {
        $row = AceLine::findOrFail($id);

        if ($row->sample_start)
            $row->sample_start = Carbon::parse($row->sample_start)->format('H:i');
        if ($row->sample_finish)
            $row->sample_finish = Carbon::parse($row->sample_finish)->format('H:i');

        try {
            if ($row->date)
                $row->date = Carbon::parse($row->date)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
        }

        return response()->json($row);
    }

    // Update One
    public function update(Request $request, $id)
    {
        $request->validate($this->rules());
        $row = AceLine::findOrFail($id);
        $oldDay = $row->date ? Carbon::parse($row->date)->format('Y-m-d') : null;

        $data = $this->fillable($request);

        if (!array_key_exists('number', $data))
            unset($data['number']);
        if (!array_key_exists('date', $data))
            unset($data['date']);
        if (!array_key_exists('shift', $data))
            unset($data['shift']);

        if (array_key_exists('date', $data) && $data['date']) {
            try {
                $dt = Carbon::parse($data['date']);
                if ($dt->format('H:i:s') === '00:00:00') {
                    $data['date'] = $dt->format('Y-m-d') . ' ' . Carbon::now('Asia/Jakarta')->format('H:i:s');
                }
            } catch (\Throwable $e) {
                $data['date'] = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
        }

        $row->update($data);

        // Day Renumber
        if (array_key_exists('date', $data) && $data['date']) {
            $newDay = Carbon::parse($data['date'])->format('Y-m-d');
            if ($oldDay && $oldDay !== $newDay) {
                $this->renumberDay($oldDay);
                $this->renumberDay($newDay);
            }
        }

        return response()->json(['ok' => true, 'id' => $row->id, 'message' => 'Updated']);
    }

    // Delete One
    public function destroy($id)
    {
        $row = AceLine::findOrFail($id);
        $day = $row->date ? Carbon::parse($row->date)->format('Y-m-d') : null;

        $row->delete();

        // Gap Fix
        if ($day) {
            try {
                $this->renumberDay($day);
            } catch (\Throwable $e) {
            }
        }

        return response()->json(['ok' => true, 'message' => 'Deleted']);
    }

    // Summary Export
    public function summary(Request $request)
    {
        $q = AceLine::query();

        if ($d = $this->ymd($request->get('date')))
            $q->whereRaw('DATE(`date`) = ?', [$d]);
        if ($s = $request->get('shift'))
            $q->where('shift', $s);
        if ($pt = $request->get('product_type_id'))
            $q->where('product_type_id', $pt);

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

        $parts = [];
        foreach ($numericCols as $c) {
            $parts[] = "AVG($c) as avg_$c";
            $parts[] = "MIN($c) as min_$c";
            $parts[] = "MAX($c) as max_$c";
            $parts[] = "COUNT($c) as cnt_$c";
        }
        $agg = $q->selectRaw(implode(', ', $parts))->first();

        $fmt2 = fn($v) => is_null($v) ? '' : number_format((float) $v, 2, '.', '');

        $rowMin = $rowMax = $rowAvg = $present = [];
        foreach ($numericCols as $name) {
            $rowMin[$name] = $fmt2($agg?->{"min_$name"});
            $rowMax[$name] = $fmt2($agg?->{"max_$name"});
            $rowAvg[$name] = $fmt2($agg?->{"avg_$name"});
            $present[$name] = (int) ($agg?->{"cnt_$name"} ?? 0) > 0;
        }

        $spec = [
            'p' => ['min' => 150, 'max' => 240],
            'c' => ['min' => 16, 'max' => 21],
            'gt' => ['min' => 400, 'max' => 700],
            'cb_lab' => ['min' => 33, 'max' => 43],
            'moisture' => ['min' => 3, 'max' => 4],
            'bakunetsu' => ['max' => 80],
            'ac' => ['min' => 8, 'max' => 11],
            'tc' => ['min' => 10, 'max' => 16],
            'vsd' => ['min' => 0.2, 'max' => 0.7],
            'ig' => ['min' => 2, 'max' => 3],
            'cb_weight' => ['min' => 169, 'max' => 181],
            'ssi' => ['min' => 90],
        ];

        $judgeVal = function ($val, array $rule): string {
            if ($val === null)
                return '';
            if (isset($rule['min']) && $val < $rule['min'])
                return 'NG';
            if (isset($rule['max']) && $val > $rule['max'])
                return 'NG';
            return 'OK';
        };

        $rowJudge = [];
        $okFlags = [];
        foreach ($numericCols as $name) {
            if (!array_key_exists($name, $spec) || !$present[$name]) {
                $rowJudge[$name] = '';
                continue;
            }
            $val = $agg?->{"avg_{$name}"};
            $j = $judgeVal(is_null($val) ? null : (float) $val, $spec[$name]);
            $rowJudge[$name] = $j;
            if ($j !== '')
                $okFlags[] = ($j === 'OK');
        }

        $overall = count($okFlags) ? (array_sum($okFlags) === count($okFlags) ? 'OK' : 'NG') : '—';

        return response()->json([
            'rows' => ['min' => $rowMin, 'max' => $rowMax, 'avg' => $rowAvg, 'judge' => $rowJudge],
            'present' => $present,
            'overall' => $overall,
        ]);
    }

    // File Export
    public function export(Request $request)
    {
        $ymd = $this->ymd($request->get('date'));
        $shift = $request->get('shift');
        $ptId = $request->get('product_type_id');

        $fname = 'ACE_' .
            ($ymd ? str_replace('-', '', $ymd) : date('Ymd')) .
            ($shift ? '_' . $shift : '') .
            ($ptId ? '_PT' . $ptId : '') .
            '_' . date('His') . '.xlsx';

        return Excel::download(new AceLineExportFull($ymd, $shift, $ptId), $fname);
    }

    // Product Lookup
    public function lookupProducts(Request $request)
    {
        try {
            $term = trim((string) $request->get('q', ''));
            $page = max(1, (int) $request->get('page', 1));
            $take = 20;
            $offset = ($page - 1) * $take;

            $query = DB::connection('mysql_wip')
                ->table('products')
                ->select(['id', 'no', 'name'])
                ->when($term, function ($q) use ($term) {
                    $q->where(function ($w) use ($term) {
                        $w->where('no', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%");
                    });
                })
                ->orderBy('no');

            $rows = $query->offset($offset)->limit($take)->get();

            $results = $rows->map(fn($r) => [
                'id' => $r->id,
                'text' => "{$r->no} - {$r->name}",
                'no' => $r->no,
                'name' => $r->name,
            ]);

            return response()->json([
                'results' => $results,
                'pagination' => ['more' => $rows->count() === $take],
            ]);
        } catch (\Throwable $e) {
            \Log::error('lookupProducts error: ' . $e->getMessage());
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);
        }
    }
}
