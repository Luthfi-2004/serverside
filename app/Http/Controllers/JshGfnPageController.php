<?php

namespace App\Http\Controllers;

use App\Exports\JshGfnExport;
use App\Models\JshGfn;
use App\Models\TotalGfn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class JshGfnPageController extends Controller
{
    /** Urutan mesh & index harus konsisten */
    private array $meshes = ['18,5', '26', '36', '50', '70', '100', '140', '200', '280', 'PAN'];
    private array $indices = [10, 20, 30, 40, 50, 70, 100, 140, 200, 300];

    /**
     * Index: filter pakai 1 tanggal (date) + shift (opsional).
     * Jika tidak ada filter, ambil rekap TERBARU secara global.
     */
    public function index(Request $req)
    {
        $date = $req->query('date');
        $shift = $req->query('shift');
        $q = $req->query('q');

        // infer
        if (!$date || !$shift) {
            $now = now('Asia/Jakarta');
            $h = (int) $now->format('H');

            // default shift by jam
            $autoShift = ($h >= 6 && $h < 17) ? 'D' : (($h >= 22 || $h < 6) ? 'N' : 'S');

            // kalau user nggak isi date/shift, pakai default
            $date = $date ?: $now->toDateString(); // yyyy-mm-dd (selalu "hari ini")
            $shift = $shift ?: $autoShift;
        }

        $latestRecap = TotalGfn::query()
            ->whereDate('gfn_date', $date)
            ->when($shift, fn($qq) => $qq->where('shift', $shift))
            ->orderByDesc('created_at')
            ->first();

        if (!$latestRecap) {
            return view('jsh-gfn.index', [
                'meshes' => $this->meshes,
                'indices' => $this->indices,
                'displayRows' => collect(),
                'displayRecap' => null,
                'filters' => ['date' => $date, 'shift' => $shift, 'q' => $q],
            ]);
        }

        [$displayRows, $displayRecap] = $this->setDisplayForLatest($latestRecap->gfn_date, $latestRecap->shift);

        return view('jsh-gfn.index', [
            'meshes' => $this->meshes,
            'indices' => $this->indices,
            'displayRows' => $displayRows,
            'displayRecap' => $displayRecap,
            'filters' => ['date' => $date, 'shift' => $shift, 'q' => $q],
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gfn_date' => ['required', 'date'],
            'shift' => ['required', 'in:D,S,N'],
            'grams' => ['required', 'array', 'size:10'],
            'grams.*' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'gfn_date' => 'Tanggal',
            'shift' => 'Shift',
            'grams' => 'Gram',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('open_modal', true);
        }

        // Normalisasi grams
        $grams = array_map(
            fn($v) => ($v === null || $v === '') ? 0.0 : (float) $v,
            $request->input('grams', [])
        );

        $totalGram = array_sum($grams);
        if ($totalGram <= 0) {
            return back()
                ->withErrors(['grams' => 'Isikan minimal satu nilai GRAM > 0.'])
                ->withInput()
                ->with('open_modal', true);
        }

        // Hitung % dan %Index
        $percentages = [];
        $percentageIndices = [];
        $sumPI = 0.0;

        for ($i = 0; $i < 10; $i++) {
            $g = $grams[$i];
            $pct = $totalGram > 0 ? ($g / $totalGram) * 100 : 0.0;
            $pctIdx = $pct * $this->indices[$i];

            $percentages[$i] = round($pct, 2);
            $percentageIndices[$i] = round($pctIdx, 1);
            $sumPI += $percentageIndices[$i];
        }

        DB::transaction(function () use ($request, $grams, $percentages, $percentageIndices, $totalGram, $sumPI) {
            // Simpan 10 baris detail
            for ($i = 0; $i < 10; $i++) {
                JshGfn::create([
                    'gfn_date' => $request->gfn_date,
                    'shift' => $request->shift,
                    'mesh' => $this->meshes[$i],
                    'gram' => $grams[$i],
                    'percentage' => $percentages[$i],
                    'index' => $this->indices[$i],
                    'percentage_index' => $percentageIndices[$i],
                    'total_gram' => $totalGram,
                    'total_percentage_index' => $sumPI,
                ]);
            }

            // Simpan total rekap (tanpa batch_code)
            TotalGfn::create([
                'gfn_date' => $request->gfn_date,
                'shift' => $request->shift,
                'nilai_gfn' => round($sumPI / 100, 2),
                'mesh_total140' => round($percentages[6] ?? 0, 2),  // mesh 140 = index ke-6
                'mesh_total70' => round(($percentages[3] ?? 0) + ($percentages[4] ?? 0) + ($percentages[5] ?? 0), 2),
                'meshpan' => round(($percentages[8] ?? 0) + ($percentages[9] ?? 0), 2),
                'judge_mesh_140' => ($percentages[6] >= 3.50 && $percentages[6] <= 8.00) ? 'OK' : 'NG',
                'judge_mesh_70' => ((($percentages[3] ?? 0) + ($percentages[4] ?? 0) + ($percentages[5] ?? 0)) >= 64.00) ? 'OK' : 'NG',
                'judge_meshpan' => ((($percentages[8] ?? 0) + ($percentages[9] ?? 0)) <= 1.40) ? 'OK' : 'NG',
                'total_gram' => $totalGram,
                'total_percentage_index' => $sumPI,
            ]);
        });

        return redirect()->route('jshgfn.index')
            ->with('status', 'Data GFN berhasil disimpan.');
    }

    /**
     * Hapus SET hari ini berdasarkan gfn_date + shift (tanpa batch_code)
     * Kirimkan gfn_date & shift via form (POST).
     */
    public function deleteTodaySet(Request $request)
    {
        $gfnDate = $request->input('gfn_date');
        $shift = $request->input('shift');

        if (!$gfnDate || !$shift) {
            return back()->withErrors(['delete' => 'gfn_date dan shift wajib diisi.']);
        }

        $sinceToday = now()->startOfDay();

        $existsToday = JshGfn::whereDate('gfn_date', $gfnDate)
            ->where('shift', $shift)
            ->where('created_at', '>=', $sinceToday)
            ->exists();

        if (!$existsToday) {
            return back()->withErrors(['delete' => 'Tidak ada data untuk kombinasi tanggal/shift ini hari ini.']);
        }

        DB::transaction(function () use ($gfnDate, $shift, $sinceToday) {
            JshGfn::whereDate('gfn_date', $gfnDate)
                ->where('shift', $shift)
                ->where('created_at', '>=', $sinceToday)
                ->delete();

            TotalGfn::whereDate('gfn_date', $gfnDate)
                ->where('shift', $shift)
                ->where('created_at', '>=', $sinceToday)
                ->delete();
        });

        return redirect()->route('jshgfn.index')->with('status', 'Data hari ini untuk tanggal/shift tersebut berhasil dihapus.');
    }

    /**
     * Export: pakai 1 tanggal (date) + shift (opsional).
     * Pastikan JshGfnExport disesuaikan untuk menerima param 'date'.
     */
    public function export(Request $request)
    {
        $date = $request->query('date');
        $shift = $request->query('shift');

        $filename = sprintf('jsh_gfn_%s.xlsx', now()->format('Ymd_His'));

        return Excel::download(new JshGfnExport(date: $request->query('date'), shift: $request->query('shift')), $filename);

    }

    /**
     * Susun tampilan untuk kombinasi (gfn_date, shift) tertentu.
     */
    private function setDisplayForLatest($gfnDate, $shift): array
    {
        // Ambil detail terbaru utk kombinasi ini
        $rows = JshGfn::query()
            ->whereDate('gfn_date', $gfnDate)
            ->where('shift', $shift)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->keyBy('mesh');

        // Susun sesuai urutan mesh & isi default jika kosong
        $ordered = collect();
        foreach ($this->meshes as $i => $mesh) {
            $r = $rows->get($mesh);
            if ($r) {
                $ordered->push($r);
            } else {
                $ordered->push((object) [
                    'mesh' => $mesh,
                    'gram' => 0,
                    'percentage' => 0,
                    'index' => $this->indices[$i],
                    'percentage_index' => 0,
                ]);
            }
        }

        // Ambil rekap terbaru untuk kombinasi yang sama
        $recap = TotalGfn::query()
            ->whereDate('gfn_date', $gfnDate)
            ->where('shift', $shift)
            ->orderByDesc('created_at')
            ->first();

        $displayRecap = $recap ? [
            'gfn_date' => $recap->gfn_date,
            'shift' => $recap->shift,
            'nilai_gfn' => (float) $recap->nilai_gfn,
            'mesh_total140' => (float) $recap->mesh_total140,
            'mesh_total70' => (float) $recap->mesh_total70,
            'meshpan' => (float) $recap->meshpan,
            'judge_mesh_140' => $recap->judge_mesh_140,
            'judge_mesh_70' => $recap->judge_mesh_70,
            'judge_meshpan' => $recap->judge_meshpan,
            'total_gram' => (float) ($recap->total_gram ?? $ordered->sum('gram')),
            'total_percentage_index' => (float) ($recap->total_percentage_index ?? $ordered->sum('percentage_index')),
        ] : null;

        return [$ordered, $displayRecap];
    }
}
