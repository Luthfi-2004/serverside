<?php

namespace App\Exports;

use App\Models\AceLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AceLineExportFull implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    public function __construct(
        protected ?string $date = null,
        protected ?string $shift = null,
        protected ?string $productTypeId = null
    ) {}

    protected array $select = [
        'number',
        'date',
        'shift',
        'product_type_name',
        'sample_start',
        'sample_finish',

        'p',
        'c',
        'gt',
        'cb_lab',
        'moisture',
        'machine_no',
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

        'no_mix',
        'bc13_cb',
        'bc13_c',
        'bc13_m',
    ];

    public function headings(): array
    {
        return [
            [
                'No',
                'Date',
                'Shift',
                'Type Product',
                'Sample Start',
                'Sample Finish',

                'MM Sample', '', '', '', '', '', '', '', '', '', '', '', '', '', '',

                'Additive Additional', '', '', '', '', '',

                'Pengecekan BC13', '', '', '',
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
                'Cb Lab',
                'Moisture',
                'Nomor Mesin',
                'Bakunetsu',
                'AC',
                'TC',
                'VSD',
                'IG',
                'CB Weight',
                'TP 50 Weight',
                'SSI',
                'MOST',

                'DW29 VAS',
                'DW29 Debu',
                'DW31 VAS',
                'DW31 ID',
                'DW31 Moldex',
                'DW31 SC',

                'NO Mix',
                'BC13 CB',
                'BC13 C',
                'BC13 M',
            ],
        ];
    }

    public function query()
    {
        $q = AceLine::query();

        if ($this->date) {
            try {
                $q->whereRaw('DATE(`date`) = ?', [Carbon::parse($this->date)->format('Y-m-d')]);
            } catch (\Throwable $e) {}
        }
        if ($this->shift) {
            $q->where('shift', $this->shift);
        }
        if ($this->productTypeId) {
            $q->where('product_type_id', $this->productTypeId);
        }

        $q->select($this->select)->orderBy('date', 'desc');
        return $q;
    }

    public function map($row): array
    {
        $date = '';
        if ($row->date) {
            try {
                $date = $row->date instanceof \DateTimeInterface
                    ? $row->date->format('Y-m-d')
                    : Carbon::parse($row->date)->format('Y-m-d');
            } catch (\Throwable $e) {
                $date = (string) $row->date;
            }
        }

        $toHm = function ($s) {
            if (!$s) return '';
            $m = preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', (string)$s, $mm);
            return $m ? ($mm[1] . ':' . $mm[2]) : (string)$s;
        };

        return [
            (int) $row->number,
            $date,
            $row->shift,
            $row->product_type_name,
            $toHm($row->sample_start),
            $toHm($row->sample_finish),

            $row->p,
            $row->c,
            $row->gt,
            $row->cb_lab,
            $row->moisture,
            $row->machine_no,
            $row->bakunetsu,
            $row->ac,
            $row->tc,
            $row->vsd,
            $row->ig,
            $row->cb_weight,
            $row->tp50_weight,
            $row->ssi,
            $row->most,

            $row->dw29_vas,
            $row->dw29_debu,
            $row->dw31_vas,
            $row->dw31_id,
            $row->dw31_moldex,
            $row->dw31_sc,

            $row->no_mix,
            $row->bc13_cb,
            $row->bc13_c,
            $row->bc13_m,
        ];
    }

    protected function fieldColIndex(): array
    {
        return [
            'p' => 7,
            'c' => 8,
            'gt' => 9,
            'cb_lab' => 10,
            'moisture' => 11,
            'machine_no' => 12,
            'bakunetsu' => 13,
            'ac' => 14,
            'tc' => 15,
            'vsd' => 16,
            'ig' => 17,
            'cb_weight' => 18,
            'tp50_weight' => 19,
            'ssi' => 20,
            // 'most' => 21, // tidak ikut summary

            'dw29_vas' => 22,
            'dw29_debu' => 23,
            'dw31_vas' => 24,
            'dw31_id' => 25,
            'dw31_moldex' => 26,
            'dw31_sc' => 27,

            'no_mix' => 28,
            'bc13_cb' => 29,
            'bc13_c' => 30,
            'bc13_m' => 31,
        ];
    }

    protected function metricFields(): array
    {
        return [
            'p','c','gt','cb_lab','moisture','bakunetsu','ac','tc','vsd','ig',
            'cb_weight','tp50_weight','ssi',
            'dw29_vas','dw29_debu','dw31_vas','dw31_id','dw31_moldex','dw31_sc',
            'no_mix','bc13_cb','bc13_c','bc13_m',
        ];
    }

    protected function specs(): array
    {
        return [
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
    }

    protected function computeSummary(): array
    {
        $q = AceLine::query();
        if ($this->date) {
            try {
                $q->whereRaw('DATE(`date`) = ?', [Carbon::parse($this->date)->format('Y-m-d')]);
            } catch (\Throwable $e) {}
        }
        if ($this->shift) $q->where('shift', $this->shift);
        if ($this->productTypeId) $q->where('product_type_id', $this->productTypeId);

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
                $judge = ($avg >= ($spec[$f]['min'] ?? -INF) && $avg <= ($spec[$f]['max'] ?? INF)) ? 'OK' : 'NG';
            }
            $res[$f] = [
                'min' => $min,
                'max' => $max,
                'avg' => $avg !== null ? round($avg, 2) : null,
                'judge' => $judge,
            ];
        }
        return $res;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $s = $e->sheet->getDelegate();

                foreach (['A','B','C','D','E','F'] as $col) {
                    $s->mergeCells("{$col}1:{$col}2");
                }
                $s->mergeCells('G1:U1');
                $s->mergeCells('V1:AA1');
                $s->mergeCells('AB1:AE1');

                $s->getStyle('A1:AE2')->applyFromArray([
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
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
                $s->getRowDimension(1)->setRowHeight(26);
                $s->getRowDimension(2)->setRowHeight(26);

                $setWidthSpan = function (string $startCol, string $endCol, float $width) use ($s) {
                    $start = Coordinate::columnIndexFromString($startCol);
                    $end = Coordinate::columnIndexFromString($endCol);
                    for ($i = $start; $i <= $end; $i++) {
                        $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($width);
                    }
                };

                $s->getDefaultColumnDimension()->setWidth(20);
                $s->getColumnDimension('A')->setWidth(10);
                $s->getColumnDimension('B')->setWidth(14);
                $s->getColumnDimension('C')->setWidth(10);
                $s->getColumnDimension('D')->setWidth(30);
                $s->getColumnDimension('E')->setWidth(16);
                $s->getColumnDimension('F')->setWidth(16);
                $setWidthSpan('G', 'U', 16);
                $setWidthSpan('V', 'AA', 16);
                $setWidthSpan('AB', 'AE', 16);

                $s->getSheetView()->setZoomScale(120);
                $s->freezePane('A3');

                $dataLast = $s->getHighestRow();

                $summary = $this->computeSummary();
                $map = $this->fieldColIndex();

                $start = $dataLast + 4;

                $rows = ['MIN' => [], 'MAX' => [], 'AVG' => [], 'JUDGE' => []];
                foreach ($summary as $field => $vals) {
                    if (!isset($map[$field])) continue;
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
                    $s->getStyle("A{$rowNum}:AE{$rowNum}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                        ],
                    ]);
                };

                $writeRow('MIN', $rows['MIN'], $start);
                $writeRow('MAX', $rows['MAX'], $start + 1);
                $writeRow('AVG', $rows['AVG'], $start + 2);
                $writeRow('JUDGE', $rows['JUDGE'], $start + 3);

                $judgeRow = $start + 3;
                foreach ($map as $idx) {
                    $col = Coordinate::stringFromColumnIndex($idx);
                    $val = (string) $s->getCell("{$col}{$judgeRow}")->getValue();
                    if ($val === 'OK' || $val === 'NG') {
                        $s->getStyle("{$col}{$judgeRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => $val === 'OK' ? '2E7D32' : 'C62828']],
                        ]);
                    }
                }

                $endRow = $s->getHighestRow();

                $s->getStyle('A1:AE2')->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $s->getStyle("A3:AE" . ($start - 1))->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $s->getStyle("A{$start}:AE" . ($start + 3))->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                $s->getStyle("A1:AE{$endRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
            },
        ];
    }
}
