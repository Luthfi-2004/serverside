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
    /** Meshes */
    private array $meshes = ['18,5', '26', '36', '50', '70', '100', '140', '200', '280', 'PAN'];

    /** Indeks */
    private array $indices = [10, 20, 30, 40, 50, 70, 100, 140, 200, 300];

    /** Index */
    public function index(Request $req)
    {
        // Param
        $date = $req->query('date');
        $shift = $req->query('shift');
        $q = $req->query('q');

        // Infer
        if (!$date || !$shift) {
            $now = now('Asia/Jakarta');
            $h = (int) $now->format('H');

            // Shift
            $autoShift = ($h >= 6 && $h < 17) ? 'D' : (($h >= 22 || $h < 6) ? 'N' : 'S');

            // Default
            $date = $date ?: $now->toDateString();
            $shift = $shift ?: $autoShift;
        }

        // Terbaru
        $latestRecap = TotalGfn::query()
            ->whereDate('gfn_date', $date)
            ->when($shift, fn($qq) => $qq->where('shift', $shift))
            ->orderByDesc('created_at')
            ->first();

        // Kosong
        if (!$latestRecap) {
            return view('jsh-gfn.index', [
                'meshes' => $this->meshes,
                'indices' => $this->indices,
                'displayRows' => collect(),
                'displayRecap' => null,
                'filters' => ['date' => $date, 'shift' => $shift, 'q' => $q],
            ]);
        }

        // Susun
        [$displayRows, $displayRecap] = $this->setDisplayForLatest($latestRecap->gfn_date, $latestRecap->shift);

        // View
        return view('jsh-gfn.index', [
            'meshes' => $this->meshes,
            'indices' => $this->indices,
            'displayRows' => $displayRows,
            'displayRecap' => $displayRecap,
            'filters' => ['date' => $date, 'shift' => $shift, 'q' => $q],
        ]);
    }

    /** Simpan */
    public function store(Request $request)
    {
        // Validasi
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

        // Gagal
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('open_modal', true);
        }

        // Normalisasi
        $grams = array_map(
            fn($v) => ($v === null || $v === '') ? 0.0 : (float) $v,
            $request->input('grams', [])
        );

        // Total
        $totalGram = array_sum($grams);
        if ($totalGram <= 0) {
            return back()
                ->withErrors(['grams' => 'Isikan minimal satu nilai GRAM > 0.'])
                ->withInput()
                ->with('open_modal', true);
        }

        // Hitung
        $percentages = [];
        $percentageIndices = [];
        $sumPI = 0.0;

        // Loop
        for ($i = 0; $i < 10; $i++) {
            $g = $grams[$i];
            $pct = $totalGram > 0 ? ($g / $totalGram) * 100 : 0.0;
            $pctIdx = $pct * $this->indices[$i];

            $percentages[$i] = round($pct, 2);
            $percentageIndices[$i] = round($pctIdx, 1);
            $sumPI += $percentageIndices[$i];
        }

        // Transaksi
        DB::transaction(function () use ($request, $grams, $percentages, $percentageIndices, $totalGram, $sumPI) {
            // Detail
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

            // Rekap
            TotalGfn::create([
                'gfn_date' => $request->gfn_date,
                'shift' => $request->shift,
                'nilai_gfn' => round($sumPI / 100, 2),
                'mesh_total140' => round($percentages[6] ?? 0, 2),
                'mesh_total70' => round(($percentages[3] ?? 0) + ($percentages[4] ?? 0) + ($percentages[5] ?? 0), 2),
                'meshpan' => round(($percentages[8] ?? 0) + ($percentages[9] ?? 0), 2),
                'judge_mesh_140' => ($percentages[6] >= 3.50 && $percentages[6] <= 8.00) ? 'OK' : 'NG',
                'judge_mesh_70' => ((($percentages[3] ?? 0) + ($percentages[4] ?? 0) + ($percentages[5] ?? 0)) >= 64.00) ? 'OK' : 'NG',
                'judge_meshpan' => ((($percentages[8] ?? 0) + ($percentages[9] ?? 0)) <= 1.40) ? 'OK' : 'NG',
                'total_gram' => $totalGram,
                'total_percentage_index' => $sumPI,
            ]);
        });

        // Redirect
        return redirect()->route('jshgfn.index')->with('status', 'Data GFN berhasil disimpan.');
    }

    /** Hapus */
    public function deleteTodaySet(Request $request)
    {
        // Input
        $gfnDate = $request->input('gfn_date');
        $shift = $request->input('shift');

        // Cek
        if (!$gfnDate || !$shift) {
            return back()->withErrors(['delete' => 'gfn_date dan shift wajib diisi.']);
        }

        // Hari
        $sinceToday = now()->startOfDay();

        // Ada
        $existsToday = JshGfn::whereDate('gfn_date', $gfnDate)
            ->where('shift', $shift)
            ->where('created_at', '>=', $sinceToday)
            ->exists();

        // Tidak
        if (!$existsToday) {
            return back()->withErrors(['delete' => 'Tidak ada data untuk kombinasi tanggal/shift ini hari ini.']);
        }

        // Transaksi
        DB::transaction(function () use ($gfnDate, $shift, $sinceToday) {
            // HapusDetail
            JshGfn::whereDate('gfn_date', $gfnDate)
                ->where('shift', $shift)
                ->where('created_at', '>=', $sinceToday)
                ->delete();

            // HapusRekap
            TotalGfn::whereDate('gfn_date', $gfnDate)
                ->where('shift', $shift)
                ->where('created_at', '>=', $sinceToday)
                ->delete();
        });

        // Sukses
        return redirect()->route('jshgfn.index')->with('status', 'Data hari ini untuk tanggal/shift tersebut berhasil dihapus.');
    }

    /** Export */
    public function export(Request $request)
    {
        // Param
        $date = $request->query('date');
        $shift = $request->query('shift');

        // Nama
        $filename = sprintf('jsh_gfn_%s.xlsx', now()->format('Ymd_His'));

        // Unduh
        return Excel::download(new JshGfnExport(date: $date, shift: $shift), $filename);
    }

    /** Susun */
    private function setDisplayForLatest($gfnDate, $shift): array
    {
        // Detail
        $rows = JshGfn::query()
            ->whereDate('gfn_date', $gfnDate)
            ->where('shift', $shift)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->keyBy('mesh');

        // Urut
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

        // Rekap
        $recap = TotalGfn::query()
            ->whereDate('gfn_date', $gfnDate)
            ->where('shift', $shift)
            ->orderByDesc('created_at')
            ->first();

        // Tampil
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

        // Kembali
        return [$ordered, $displayRecap];
    }
}
