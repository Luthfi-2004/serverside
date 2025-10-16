<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\GreensandJsh;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GreensandExportFull;

class GreensandJshController extends Controller
{
    private const URL_MAIN = 'quality/greensand/jsh-greensand-check';

    // data mm1
    public function dataMM1(Request $request)
    {
        if (!$this->can('can_read'))
            abort(403);
        return $this->makeResponse($request, 'MM1');
    }

    // data mm2
    public function dataMM2(Request $request)
    {
        if (!$this->can('can_read'))
            abort(403);
        return $this->makeResponse($request, 'MM2');
    }

    // data semua
    public function dataAll(Request $request)
    {
        if (!$this->can('can_read'))
            abort(403);
        return $this->makeResponse($request, null);
    }

    // ekspor excel
    public function export(Request $r)
    {
        if (!$this->can('can_read'))
            abort(403);

        $date = $r->query('date');
        $shift = $r->query('shift');
        $keyword = $r->query('keyword');
        $mm = $r->query('mm');

        $fname = 'Greensand_'
            . ($mm ? $mm . '_' : '')
            . ($date ? str_replace(['/', '-'], '', $date) : now('Asia/Jakarta')->format('Ymd'))
            . ($shift ? '_' . $shift : '')
            . '_' . now('Asia/Jakarta')->format('His')
            . '.xlsx';

        return Excel::download(new GreensandExportFull($date, $shift, $keyword, $mm), $fname);
    }

    // simpan data
    public function store(Request $request)
    {
        if (!$this->can('can_add'))
            abort(403);

        $in = $this->normalizeAllDecimals($request->all());
        $v = $this->validator($in, 'store');
        if ($v->fails())
            return response()->json(['errors' => $v->errors()], 422);

        $mm = $this->normalizeMm($in['mm'] ?? null);
        $shift = $in['shift'];
        $day = $this->toYmd($in['date'] ?? null) ?: now('Asia/Jakarta')->toDateString();
        $mixKe = (int) ($in['mix_ke'] ?? 0);

        if ($this->isDuplicateMix($mm, $shift, $mixKe, $day, null)) {
            return response()->json([
                'errors' => ['mix_ke' => ["Mix ke {$mixKe} sudah dipakai untuk {$mm} di shift {$shift} pada {$day}."]]
            ], 422);
        }

        $data = $this->mapRequestToModel($in, null, $day);
        $row = GreensandJsh::create($data);

        return response()->json(['message' => 'Created', 'id' => $row->id]);
    }

    // tampil satu
    public function show($id)
    {
        if (!$this->can('can_read'))
            abort(403);

        $row = GreensandJsh::findOrFail($id);
        return response()->json(['data' => $row]);
    }

    // perbarui data
    public function update(Request $request, $id)
    {
        if (!$this->can('can_edit'))
            abort(403);

        $row = GreensandJsh::findOrFail($id);
        $in = $this->normalizeAllDecimals($request->all());

        $v = $this->validator($in, 'update');
        if ($v->fails())
            return response()->json(['errors' => $v->errors()], 422);

        $mm = $this->normalizeMm($in['mm'] ?? $row->mm);
        $shift = $row->shift;
        $mixKe = isset($in['mix_ke']) ? (int) $in['mix_ke'] : (int) $row->mix_ke;
        $day = $this->dayString($row->date);

        if ($this->isDuplicateMix($mm, $shift, $mixKe, $day, (int) $row->id)) {
            return response()->json([
                'errors' => ['mix_ke' => ["Mix ke {$mixKe} sudah dipakai untuk {$mm} di shift {$shift} pada {$day}."]]
            ], 422);
        }

        $data = $this->mapRequestToModel($in, $row, $day, true, true);
        $row->update($data);

        return response()->json(['message' => 'Updated']);
    }

    // hapus data
    public function destroy($id)
    {
        if (!$this->can('can_delete'))
            abort(403);

        $row = GreensandJsh::findOrFail($id);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // buat response
    private function makeResponse(Request $request, ?string $mmFilter)
    {
        try {
            $q = GreensandJsh::query();
            if ($mmFilter)
                $q->where('mm', $mmFilter);

            $d = $request->filled('date') ? $this->toYmd($request->date) : null;
            if ($d)
                $q->whereDate('date', $d);
            if ($request->filled('shift'))
                $q->where('shift', $request->shift);
            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $q->where(function ($x) use ($kw) {
                    $x->where('mix_ke', 'like', "%{$kw}%")
                        ->orWhere('rs_type', 'like', "%{$kw}%")
                        ->orWhere('machine_no', 'like', "%{$kw}%")
                        ->orWhere('rating_pasir_es', 'like', "%{$kw}%");
                });
            }

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
                'machine_no',
                'mm_bakunetsu',
                'mm_ac',
                'mm_tc',
                'mm_vsd',
                'mm_ig',
                'mm_cb_weight',
                'mm_tp50_weight',
                'mm_tp50_height',
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
                'add_water_mm',
                'add_water_mm_2',
                'temp_sand_mm_1',
                'rcs_pick_up',
                'total_flask',
                'rcs_avg',
                'add_bentonite_ma',
                'total_sand',
                'add_water_bc10',
                'lama_bc10_jalan',
                'rating_pasir_es',
            ]);

            $canEdit = $this->can('can_edit');
            $canDelete = $this->can('can_delete');

            return DataTables::of($q)
                ->addColumn('action', function ($row) use ($canEdit, $canDelete) {
                    $html = '<div class="btn-group btn-group-sm se-2">';
                    if ($canEdit)
                        $html .= '<button class="btn btn-outline-warning btn-sm mr-2 btn-edit-gs" data-id="' . $row->id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                    if ($canDelete)
                        $html .= '<button class="btn btn-outline-danger btn-sm btn-delete-gs" data-id="' . $row->id . '" title="Hapus"><i class="fas fa-trash"></i></button>';
                    $html .= '</div>';
                    return $html;
                })
                ->editColumn('mm', fn($row) => $row->mm === 'MM1' ? 1 : ($row->mm === 'MM2' ? 2 : $row->mm))
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

    // validasi input
    private function validator(array $in, string $mode = 'store')
    {
        $in['mm'] = $this->normalizeMm($in['mm'] ?? null);

        return Validator::make($in, [
            'mm' => 'required|in:MM1,MM2',
            'shift' => 'required|in:D,S,N',
            'mix_ke' => 'required|integer|min:1',
            'mix_start' => 'nullable|date_format:H:i',
            'mix_finish' => 'nullable|date_format:H:i',
            'rs_time' => 'nullable|date_format:H:i',
            'machine_no' => 'nullable|string|max:50',
            'add_water_bc10' => 'nullable|numeric|min:0',
            'lama_bc10_jalan' => 'nullable|numeric|min:0',
            'rating_pasir_es' => 'nullable|numeric',
        ]);
    }

    // normalisasi desimal
    private function normalizeAllDecimals(array $in): array
    {
        $numericFields = [
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
            'mm_tp50_height',
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
            'bc9_moist',
            'bc10_moist',
            'bc11_moist',
            'bc9_temp',
            'bc10_temp',
            'bc11_temp',
            'add_water_mm',
            'add_water_mm_2',
            'temp_sand_mm_1',
            'rcs_pick_up',
            'total_flask',
            'rcs_avg',
            'add_bentonite_ma',
            'total_sand',
            'add_water_bc10',
            'lama_bc10_jalan',
            'rating_pasir_es',
        ];

        foreach ($numericFields as $key) {
            if (!array_key_exists($key, $in))
                continue;
            $v = $in[$key];
            if ($v === '' || $v === null) {
                $in[$key] = null;
                continue;
            }
            if (is_string($v)) {
                $v = trim($v);
                $v = str_replace(',', '.', $v);
            }
            $in[$key] = $v;
        }
        if (array_key_exists('mix_ke', $in) && $in['mix_ke'] === '')
            $in['mix_ke'] = null;

        return $in;
    }

    // normalisasi mm
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

    // format tanggal
    private function dayString($value): string
    {
        if ($value instanceof \DateTimeInterface)
            return Carbon::instance($value)->toDateString();
        return Carbon::parse($value)->toDateString();
    }

    // ke ymd
    private function toYmd(?string $val): ?string
    {
        if (!$val)
            return null;
        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
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

    // cek duplikat
    private function isDuplicateMix(string $mm, string $shift, int $mixKe, string $dayYmd, ?int $ignoreId = null): bool
    {
        $q = GreensandJsh::query()
            ->whereDate('date', $dayYmd)
            ->where('shift', $shift)
            ->where('mm', $mm)
            ->where('mix_ke', $mixKe);

        if ($ignoreId)
            $q->where('id', '!=', $ignoreId);
        return $q->exists();
    }

    // map request
    private function mapRequestToModel(
        array $in,
        ?GreensandJsh $existing = null,
        ?string $dayYmd = null,
        bool $lockDate = false,
        bool $lockShift = false
    ): array {
        $mm = $this->normalizeMm($in['mm'] ?? ($existing->mm ?? null));

        if ($existing && $lockDate) {
            try {
                $dateTime = $existing->date instanceof \DateTimeInterface
                    ? Carbon::instance($existing->date)->format('Y-m-d H:i:s')
                    : Carbon::parse($existing->date)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                $dateTime = now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
        } else {
            $day = $dayYmd ?: now('Asia/Jakarta')->toDateString();
            $timeNow = now('Asia/Jakarta')->format('H:i:s');
            $dateTime = "{$day} {$timeNow}";
        }

        $shiftVal = $lockShift ? ($existing->shift ?? null) : ($in['shift'] ?? ($existing->shift ?? null));

        return [
            'date' => $dateTime,
            'shift' => $shiftVal,
            'mm' => $mm,
            'mix_ke' => $in['mix_ke'] ?? ($existing->mix_ke ?? null),

            'mix_start' => $in['mix_start'] ?? ($existing->mix_start ?? null),
            'mix_finish' => $in['mix_finish'] ?? ($existing->mix_finish ?? null),

            'mm_p' => $in['mm_p'] ?? ($existing->mm_p ?? null),
            'mm_c' => $in['mm_c'] ?? ($existing->mm_c ?? null),
            'mm_gt' => $in['mm_gt'] ?? ($existing->mm_gt ?? null),
            'mm_cb_mm' => $in['mm_cb_mm'] ?? ($existing->mm_cb_mm ?? null),
            'mm_cb_lab' => $in['mm_cb_lab'] ?? ($existing->mm_cb_lab ?? null),
            'mm_m' => $in['mm_m'] ?? ($existing->mm_m ?? null),

            'machine_no' => $in['machine_no'] ?? ($existing->machine_no ?? null),

            'mm_bakunetsu' => $in['mm_bakunetsu'] ?? ($existing->mm_bakunetsu ?? null),
            'mm_ac' => $in['mm_ac'] ?? ($existing->mm_ac ?? null),
            'mm_tc' => $in['mm_tc'] ?? ($existing->mm_tc ?? null),
            'mm_vsd' => $in['mm_vsd'] ?? ($existing->mm_vsd ?? null),
            'mm_ig' => $in['mm_ig'] ?? ($existing->mm_ig ?? null),

            'mm_cb_weight' => $in['mm_cb_weight'] ?? ($existing->mm_cb_weight ?? null),
            'mm_tp50_weight' => $in['mm_tp50_weight'] ?? ($existing->mm_tp50_weight ?? null),
            'mm_tp50_height' => $in['mm_tp50_height'] ?? ($existing->mm_tp50_height ?? null),
            'mm_ssi' => $in['mm_ssi'] ?? ($existing->mm_ssi ?? null),

            'add_m3' => $in['add_m3'] ?? ($existing->add_m3 ?? null),
            'add_vsd' => $in['add_vsd'] ?? ($existing->add_vsd ?? null),
            'add_sc' => $in['add_sc'] ?? ($existing->add_sc ?? null),

            'bc12_cb' => $in['bc12_cb'] ?? ($existing->bc12_cb ?? null),
            'bc12_m' => $in['bc12_m'] ?? ($existing->bc12_m ?? null),
            'bc11_ac' => $in['bc11_ac'] ?? ($existing->bc11_ac ?? null),
            'bc11_vsd' => $in['bc11_vsd'] ?? ($existing->bc11_vsd ?? null),
            'bc16_cb' => $in['bc16_cb'] ?? ($existing->bc16_cb ?? null),
            'bc16_m' => $in['bc16_m'] ?? ($existing->bc16_m ?? null),

            'rs_time' => $in['rs_time'] ?? ($existing->rs_time ?? null),
            'rs_type' => $in['rs_type'] ?? ($existing->rs_type ?? null),

            'bc9_moist' => $in['bc9_moist'] ?? ($existing->bc9_moist ?? null),
            'bc10_moist' => $in['bc10_moist'] ?? ($existing->bc10_moist ?? null),
            'bc11_moist' => $in['bc11_moist'] ?? ($existing->bc11_moist ?? null),
            'bc9_temp' => $in['bc9_temp'] ?? ($existing->bc9_temp ?? null),
            'bc10_temp' => $in['bc10_temp'] ?? ($existing->bc10_temp ?? null),
            'bc11_temp' => $in['bc11_temp'] ?? ($existing->bc11_temp ?? null),

            'add_water_mm' => $in['add_water_mm'] ?? ($existing->add_water_mm ?? null),
            'add_water_mm_2' => $in['add_water_mm_2'] ?? ($existing->add_water_mm_2 ?? null),
            'temp_sand_mm_1' => $in['temp_sand_mm_1'] ?? ($existing->temp_sand_mm_1 ?? null),
            'rcs_pick_up' => $in['rcs_pick_up'] ?? ($existing->rcs_pick_up ?? null),
            'total_flask' => $in['total_flask'] ?? ($existing->total_flask ?? null),
            'rcs_avg' => $in['rcs_avg'] ?? ($existing->rcs_avg ?? null),
            'add_bentonite_ma' => $in['add_bentonite_ma'] ?? ($existing->add_bentonite_ma ?? null),
            'total_sand' => $in['total_sand'] ?? ($existing->total_sand ?? null),

            'add_water_bc10' => $in['add_water_bc10'] ?? ($existing->add_water_bc10 ?? null),
            'lama_bc10_jalan' => $in['lama_bc10_jalan'] ?? ($existing->lama_bc10_jalan ?? null),
            'rating_pasir_es' => $in['rating_pasir_es'] ?? ($existing->rating_pasir_es ?? null),
        ];
    }

    // cek izin
    private function can(string $flag, ?string $url = null): bool
    {
        if (config('app.bypass_auth', env('BYPASS_AUTH', false)))
            return true;

        $user = Auth::user();
        if (!$user)
            return false;

        $userIds = array_filter([$user->id ?? null, $user->kode_user ?? null]);
        $target = $url ?: self::URL_MAIN;

        $urls = [ltrim($target, '/'), '/' . ltrim($target, '/')];

        try {
            return DB::connection('mysql_aicc')
                ->table('v_user_permissions')
                ->whereIn('user_id', $userIds)
                ->whereIn('url', $urls)
                ->where($flag, 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
