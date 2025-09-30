<?php

namespace App\Exports;

use App\Models\GreensandJsh;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GreensandExportFull implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithEvents
{
    public function __construct(
        protected ?string $date = null,
        protected ?string $shift = null,
        protected ?string $keyword = null,
        protected ?string $mm = null // "MM1" | "MM2" | null (null = All)
    ) {
    }

    protected array $select = [
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
        'add_water_mm',
        'add_water_mm_2',
        'temp_sand_mm_1',
        'rcs_pick_up',
        'total_flask',
        'rcs_avg',
        'add_bentonite_ma',
        'total_sand',
    ];

    public function headings(): array
    {
        return [
            [
                'Date',
                'Shift',
                'MM',
                'MIX KE',
                'MIX START',
                'MIX FINISH',
                'MM Sample',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Additive',
                '',
                '',
                'BC Sample',
                '',
                '',
                '',
                '',
                '',
                'Return Sand',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Moulding Data',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                'P',
                'C',
                'G.T',
                'CB MM',
                'CB Lab',
                'M',
                'Bakunetsu',
                'AC',
                'TC',
                'Vsd',
                'IG',
                'CB weight',
                'TP 50 weight',
                'SSI',
                'M3',
                'VSD',
                'SC',
                'BC12 CB',
                'BC12 M',
                'BC11 AC',
                'BC11 VSD',
                'BC16 CB',
                'BC16 M',
                'RS Time',
                'Type',
                'Moist BC9',
                'Moist BC10',
                'Moist BC11',
                'Temp BC9',
                'Temp BC10',
                'Temp BC11',
                'Add Water MM1',
                'Add Water MM2',
                'Temp Sand MM1',
                'RCS Pick Up',
                'Total Flask',
                'RCS Avg',
                'Add Bentonite MA',
                'Total Sand',
            ],
        ];
    }

    public function query()
    {
        $q = GreensandJsh::query();

        if ($this->mm)
            $q->where('mm', $this->mm);
        if ($this->date) {
            try {
                $q->whereDate('date', Carbon::parse($this->date)->toDateString());
            } catch (\Throwable $e) {
            }
        }
        if ($this->shift)
            $q->where('shift', $this->shift);
        if ($this->keyword) {
            $kw = $this->keyword;
            $q->where(function (Builder $x) use ($kw) {
                $x->where('mix_ke', 'like', "%{$kw}%")->orWhere('rs_type', 'like', "%{$kw}%");
            });
        }

        $q->select($this->select);
        $this->mm
            ? $q->orderBy('date', 'desc')
            : $q->orderBy('mm', 'asc')->orderBy('date', 'desc');

        return $q;
    }

    public function map($row): array
    {
        $date = '';
        if ($row->date) {
            try {
                $date = $row->date instanceof \DateTimeInterface
                    ? $row->date->format('d-m-Y H:i:s')
                    : Carbon::parse($row->date)->format('d-m-Y H:i:s');
            } catch (\Throwable $e) {
                $date = (string) $row->date;
            }
        }

        $mmVal = ($row->mm === 'MM2') ? 2 : (($row->mm === 'MM1') ? 1 : $row->mm);

        return [
            $date,
            $row->shift,
            $mmVal,
            $row->mix_ke,
            $row->mix_start,
            $row->mix_finish,

            $row->mm_p,
            $row->mm_c,
            $row->mm_gt,
            $row->mm_cb_mm,
            $row->mm_cb_lab,
            $row->mm_m,
            $row->mm_bakunetsu,
            $row->mm_ac,
            $row->mm_tc,
            $row->mm_vsd,
            $row->mm_ig,
            $row->mm_cb_weight,
            $row->mm_tp50_weight,
            $row->mm_ssi,

            $row->add_m3,
            $row->add_vsd,
            $row->add_sc,

            $row->bc12_cb,
            $row->bc12_m,
            $row->bc11_ac,
            $row->bc11_vsd,
            $row->bc16_cb,
            $row->bc16_m,

            $row->rs_time,
            $row->rs_type,
            $row->bc9_moist,
            $row->bc10_moist,
            $row->bc11_moist,
            $row->bc9_temp,
            $row->bc10_temp,
            $row->bc11_temp,

            $row->add_water_mm,
            $row->add_water_mm_2,
            $row->temp_sand_mm_1,
            $row->rcs_pick_up,
            $row->total_flask,
            $row->rcs_avg,
            $row->add_bentonite_ma,
            $row->total_sand,
        ];
    }

    protected function fieldColIndex(): array
    {
        return [
            'mm_p' => 7,
            'mm_c' => 8,
            'mm_gt' => 9,
            'mm_cb_mm' => 10,
            'mm_cb_lab' => 11,
            'mm_m' => 12,
            'mm_bakunetsu' => 13,
            'mm_ac' => 14,
            'mm_tc' => 15,
            'mm_vsd' => 16,
            'mm_ig' => 17,
            'mm_cb_weight' => 18,
            'mm_tp50_weight' => 19,
            'mm_ssi' => 20,
            'add_m3' => 21,
            'add_vsd' => 22,
            'add_sc' => 23,
            'bc12_cb' => 24,
            'bc12_m' => 25,
            'bc11_ac' => 26,
            'bc11_vsd' => 27,
            'bc16_cb' => 28,
            'bc16_m' => 29,
            'rs_time' => 30,
            'rs_type' => 31,
            'bc9_moist' => 32,
            'bc10_moist' => 33,
            'bc11_moist' => 34,
            'bc9_temp' => 35,
            'bc10_temp' => 36,
            'bc11_temp' => 37,
            'add_water_mm' => 38,
            'add_water_mm_2' => 39,
            'temp_sand_mm_1' => 40,
            'rcs_pick_up' => 41,
            'total_flask' => 42,
            'rcs_avg' => 43,
            'add_bentonite_ma' => 44,
            'total_sand' => 45,
        ];
    }

    protected function metricFields(): array
    {
        return [
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
            'bc9_moist',
            'bc10_moist',
            'bc11_moist',
            'bc9_temp',
            'bc10_temp',
            'bc11_temp',
        ];
    }

    protected function specs(): array
    {
        return [
            'mm_p' => ['min' => 220, 'max' => 260],
            'mm_c' => ['min' => 13.5, 'max' => 17.5],
            'mm_gt' => ['min' => 450, 'max' => 650],
            'mm_cb_mm' => ['min' => 40, 'max' => 43],
            'mm_cb_lab' => ['min' => 32, 'max' => 42],
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
    }

    protected function computeSummary(): array
    {
        $q = GreensandJsh::query();

        if ($this->mm)
            $q->where('mm', $this->mm);
        if ($this->date) {
            try {
                $q->whereDate('date', Carbon::parse($this->date)->toDateString());
            } catch (\Throwable $e) {
            }
        }
        if ($this->shift)
            $q->where('shift', $this->shift);
        if ($this->keyword) {
            $kw = $this->keyword;
            $q->where(function (Builder $x) use ($kw) {
                $x->where('mix_ke', 'like', "%{$kw}%")->orWhere('rs_type', 'like', "%{$kw}%");
            });
        }

        $fields = $this->metricFields();
        $agg = [];
        foreach ($fields as $f) {
            $agg[] = \DB::raw("MIN($f) as min_$f");
            $agg[] = \DB::raw("MAX($f) as max_$f");
            $agg[] = \DB::raw("AVG($f) as avg_$f");
        }
        $row = $q->select($agg)->first();

        $spec = $this->specs();
        $res = [];
        foreach ($fields as $f) {
            $min = $row?->{"min_$f"} ?? null;
            $max = $row?->{"max_$f"} ?? null;
            $avg = $row?->{"avg_$f"} ?? null;
            $judge = null;
            if ($avg !== null && isset($spec[$f])) {
                $judge = ($avg >= $spec[$f]['min'] && $avg <= $spec[$f]['max']) ? 'OK' : 'NG';
            }
            $res[$f] = ['min' => $min, 'max' => $max, 'avg' => $avg !== null ? round($avg, 2) : null, 'judge' => $judge];
        }
        return $res;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                /** @var Worksheet $s */
                $s = $e->sheet->getDelegate();

                // Merge header A..F vertikal (row 1–2)
                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                    $s->mergeCells("{$col}1:{$col}2");
                }
                // Grup horizontal (row-1)
                $s->mergeCells('G1:T1');   // MM Sample (14)
                $s->mergeCells('U1:W1');   // Additive (3)
                $s->mergeCells('X1:AC1');  // BC Sample (6)
                $s->mergeCells('AD1:AK1'); // Return Sand (8)
                $s->mergeCells('AL1:AS1'); // Moulding Data (8)
    
                // Header style
                $s->getStyle('A1:AS2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '343A40'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
                    ]
                ]);
                $s->getRowDimension(1)->setRowHeight(26);
                $s->getRowDimension(2)->setRowHeight(26);

                // Width helper (supports AA..AS)
                $setWidthSpan = function (string $startCol, string $endCol, float $width) use ($s) {
                    $start = Coordinate::columnIndexFromString($startCol);
                    $end = Coordinate::columnIndexFromString($endCol);
                    for ($i = $start; $i <= $end; $i++) {
                        $col = Coordinate::stringFromColumnIndex($i);
                        $s->getColumnDimension($col)->setWidth($width);
                    }
                };

                // Widths
                $s->getDefaultColumnDimension()->setWidth(24);
                $s->getColumnDimension('A')->setWidth(26);
                $s->getColumnDimension('B')->setWidth(18);
                $s->getColumnDimension('C')->setWidth(12);
                $s->getColumnDimension('D')->setWidth(14);
                $s->getColumnDimension('E')->setWidth(18);
                $s->getColumnDimension('F')->setWidth(18);
                $setWidthSpan('G', 'T', 16);
                $setWidthSpan('U', 'W', 16);
                $setWidthSpan('X', 'AC', 16);
                $setWidthSpan('AD', 'AK', 16);
                $setWidthSpan('AL', 'AS', 20);

                // Zoom + Freeze
                $s->getSheetView()->setZoomScale(120);
                $s->freezePane('A3');

                // Spacer antar MM (hanya di ALL)
                if ($this->mm === null) {
                    $dataLastBeforeSpacer = $s->getHighestRow();
                    $gapBetweenMM = 3;
                    for ($r = $dataLastBeforeSpacer; $r >= 4; $r--) {
                        $curr = (string) $s->getCell("C{$r}")->getValue();
                        $prev = (string) $s->getCell("C" . ($r - 1))->getValue();
                        if ($curr !== '' && $prev !== '' && $curr !== $prev) {
                            $s->insertNewRowBefore($r, $gapBetweenMM);
                        }
                    }
                }

                // Recalculate last data row after spacer
                $dataLast = $s->getHighestRow();

                // ===== SUMMARY: ONLY IN ALL (mm == null) =====
                $summaryStart = null;
                if ($this->mm === null) {
                    $summary = $this->computeSummary();
                    $map = $this->fieldColIndex();

                    $gapBeforeSummary = 3;
                    $summaryStart = $dataLast + $gapBeforeSummary + 1;

                    $rows = ['MIN' => [], 'MAX' => [], 'AVG' => [], 'JUDGE' => []];
                    foreach ($summary as $field => $vals) {
                        if (!isset($map[$field]))
                            continue;
                        $col = Coordinate::stringFromColumnIndex($map[$field]);
                        $rows['MIN'][$col] = $vals['min'] ?? '';
                        $rows['MAX'][$col] = $vals['max'] ?? '';
                        $rows['AVG'][$col] = $vals['avg'] ?? '';
                        $rows['JUDGE'][$col] = $vals['judge'] ?? '';
                    }

                    $writeRow = function (string $label, array $valueMap, int $rowNum) use ($s) {
                        $s->mergeCells("A{$rowNum}:F{$rowNum}");
                        $s->setCellValue("A{$rowNum}", $label);
                        foreach ($valueMap as $col => $val) {
                            $s->setCellValue("{$col}{$rowNum}", $val);
                        }
                        $s->getStyle("A{$rowNum}:AS{$rowNum}")->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                            ],
                            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                        ]);
                    };

                    $writeRow('MIN', $rows['MIN'], $summaryStart);
                    $writeRow('MAX', $rows['MAX'], $summaryStart + 1);
                    $writeRow('AVG', $rows['AVG'], $summaryStart + 2);
                    $writeRow('JUDGE', $rows['JUDGE'], $summaryStart + 3);

                    // OK/NG colors
                    $judgeRow = $summaryStart + 3;
                    for ($i = 7; $i <= 45; $i++) {
                        $col = Coordinate::stringFromColumnIndex($i);
                        $val = (string) $s->getCell("{$col}{$judgeRow}")->getValue();
                        if ($val === 'OK' || $val === 'NG') {
                            $s->getStyle("{$col}{$judgeRow}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => $val === 'OK' ? '2E7D32' : 'C62828']]
                            ]);
                        }
                    }
                }

                // ===== Alignment: header, data, (summary only in ALL) =====
                $s->getStyle('A1:AS2')->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Data center: A3..last data row
                $s->getStyle("A3:AS{$dataLast}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Summary center (only if exists)
                if ($summaryStart !== null) {
                    $s->getStyle("A{$summaryStart}:AS" . ($summaryStart + 3))->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                }

                // Borders full area
                $endRow = $s->getHighestRow();
                $lastCol = $s->getHighestColumn();
                $s->getStyle("A1:{$lastCol}{$endRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ]);
            }
        ];
    }
}
