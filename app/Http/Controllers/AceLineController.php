<?php

namespace App\Http\Controllers;

use App\Models\AceLine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AceLineController extends Controller
{
    /* ========= Helpers ========= */

    // Parse tanggal bebas jadi Y-m-d
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

    // Shift berdasarkan jam Asia/Jakarta
    private function detectShift(?Carbon $now = null): string
    {
        $now = $now ? $now->copy() : Carbon::now('Asia/Jakarta');
        $h = (int) $now->format('H');
        if ($h >= 6 && $h < 16)
            return 'D';   // 06-16
        if ($h >= 16 && $h < 22)
            return 'S';  // 16-22
        return 'N';                            // 22-06
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

        foreach ($numeric as $f) {
            $rules[$f] = ['nullable', 'numeric'];
        }
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
            // MM Sample
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
            // Additive Additional
            'dw29_vas',
            'dw29_debu',
            'dw31_vas',
            'dw31_id',
            'dw31_moldex',
            'dw31_sc',
            // BC13
            'bc13_cb',
            'bc13_c',
            'bc13_m',
        ]);

        if (!empty($data['date'])) {
            $data['date'] = $this->ymd($data['date']);
        }
        return $data;
    }

    /* ========= DataTables ========= */

    public function data(Request $request)
    {
        $q = AceLine::query();

        if ($d = $this->ymd($request->get('date'))) {
            $q->whereDate('date', $d);
        }
        if ($s = $request->get('shift')) {
            $q->where('shift', $s);
        }
        if ($pt = $request->get('product_type_id')) {
            $q->where('product_type_id', $pt);
        }

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
                return $r->date ? Carbon::parse($r->date)->format('Y-m-d') : null;
            })
            ->make(true);
    }

    /* ========= CRUD ========= */

    public function store(Request $request)
    {
        $request->validate($this->rules());

        // Default date/shift kalau kosong
        $data = $this->fillable($request);
        if (empty($data['date'])) {
            $data['date'] = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        }
        if (empty($data['shift'])) {
            $data['shift'] = $this->detectShift();
        }

        // Auto numbering per tanggal (reset kalau semua data tanggal tsb sudah terhapus)
        if (empty($data['number'])) {
            $max = AceLine::whereDate('date', $data['date'])->max('number');
            $data['number'] = ($max ?? 0) + 1;
        }

        $row = AceLine::create($data);

        return response()->json([
            'ok' => true,
            'id' => $row->id,
            'message' => 'Saved',
        ]);
    }

    public function show($id)
    {
        $row = AceLine::findOrFail($id);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules());
        $row = AceLine::findOrFail($id);

        $data = $this->fillable($request);

        // Jangan ubah number/date/shift kalau tidak dikirim (biar edit beneran edit)
        if (!array_key_exists('number', $data))
            unset($data['number']);
        if (!array_key_exists('date', $data))
            unset($data['date']);
        if (!array_key_exists('shift', $data))
            unset($data['shift']);

        $row->update($data);

        return response()->json([
            'ok' => true,
            'id' => $row->id,
            'message' => 'Updated',
        ]);
    }

    public function destroy($id)
    {
        AceLine::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'message' => 'Deleted']);
    }

    /* ========= Summary Footer (sesuai kolom table saat ini) =========
     * Urutan kolom (tanpa "Action"):
     * No, Date, Shift, Type, Start, Finish,
     * P,C,G.T,Cb Lab,Moisture,Nomor Mesin,Bakunetsu,AC,TC,VSD,IG,CB Weight,TP 50 Weight,SSI,
     * DW29_VAS,DW29_Debu,DW31_VAS,DW31_ID,DW31_Moldex,DW31_SC,
     * NO Mix,BC13_CB,BC13_C,BC13_M
     * Hanya numeric di-AVG; text/waktu dikosongkan.
     */
    public function summary(Request $request)
    {
        $q = AceLine::query();

        if ($d = $this->ymd($request->get('date'))) {
            $q->whereDate('date', $d);
        }
        if ($s = $request->get('shift')) {
            $q->where('shift', $s);
        }
        if ($pt = $request->get('product_type_id')) {
            $q->where('product_type_id', $pt);
        }

        $agg = $q->selectRaw("
            COUNT(*) as cnt,
            AVG(number) as number,
            -- times/text dibiarkan kosong di footer
            AVG(p) as p, AVG(c) as c, AVG(gt) as gt,
            AVG(cb_lab) as cb_lab, AVG(moisture) as moisture,
            AVG(bakunetsu) as bakunetsu, AVG(ac) as ac, AVG(tc) as tc,
            AVG(vsd) as vsd, AVG(ig) as ig,
            AVG(cb_weight) as cb_weight, AVG(tp50_weight) as tp50_weight,
            AVG(ssi) as ssi,
            AVG(dw29_vas) as dw29_vas, AVG(dw29_debu) as dw29_debu,
            AVG(dw31_vas) as dw31_vas, AVG(dw31_id) as dw31_id,
            AVG(dw31_moldex) as dw31_moldex, AVG(dw31_sc) as dw31_sc,
            AVG(no_mix) as no_mix,
            AVG(bc13_cb) as bc13_cb, AVG(bc13_c) as bc13_c, AVG(bc13_m) as bc13_m
        ")->first();

        $fmt = function ($v) {
            return is_null($v) ? '' : number_format((float) $v, 2, '.', '');
        };

        $values = [
            // No
            $fmt($agg->number),
            // Date, Shift, Type
            '',
            '',
            '',
            // Start, Finish
            '',
            '',
            // MM Sample (numeric; machine_no text → kosong)
            $fmt($agg->p),
            $fmt($agg->c),
            $fmt($agg->gt),
            $fmt($agg->cb_lab),
            $fmt($agg->moisture),
            '', // machine_no
            $fmt($agg->bakunetsu),
            $fmt($agg->ac),
            $fmt($agg->tc),
            $fmt($agg->vsd),
            $fmt($agg->ig),
            $fmt($agg->cb_weight),
            $fmt($agg->tp50_weight),
            $fmt($agg->ssi),
            // Additive Additional
            $fmt($agg->dw29_vas),
            $fmt($agg->dw29_debu),
            $fmt($agg->dw31_vas),
            $fmt($agg->dw31_id),
            $fmt($agg->dw31_moldex),
            $fmt($agg->dw31_sc),
            // BC13
            $fmt($agg->no_mix),
            $fmt($agg->bc13_cb),
            $fmt($agg->bc13_c),
            $fmt($agg->bc13_m),
        ];

        return response()->json([
            'label' => 'TOTAL (' . $agg->cnt . ' rows)',
            'values' => $values,
            'count' => (int) ($agg->cnt ?? 0),
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
