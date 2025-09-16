<?php

namespace App\Exports;

use App\Models\Process;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GreensandExportFull implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    /** ====== EDIT DI SINI JIKA PERLU (nilai referensi) ====== */
    private array $mmRef = [
        //               P         C         GT         CB MM      CB Lab    M       Bakunetsu   AC        TC       VSD      IG       CB w     TP50 w    SSI
        'standard' => ['220~260', '85~115', '450~550', '40~45', '32.0~42.6', '2.65', '25~35', '5.7~7.3', '8.7~10.3', '8.7~11.3', '3.7~4.8', '85~110', '141~144', '85~95'],
        'target' => ['240', '15,5', '550', '41,0', '37', '2,65', '65,0', '7,00', '10,0', '10,0', '3,50', '-', '30', '150'],
        'satuan' => ['g/Cm²', 'Mpa', 'g/Cm²', '%', '%', '%', '%', '%', '%', '%', '%', 'g', 'g', '%'],
        'ct' => ['52', '75', '62', '', '', '', '', '', '', '', '', '', '30', '150'],
        'freq' => ['min 6x/shift/MM', 'min 2x/shift/MM', 'min 2x/shift/MM', 'Every mixing', 'min 1x/shift/MM', 'min 1x/shift/MM', 'min 1x/shift/MM', 'min 2x/shift', 'min 1x/shift', 'min 2x/shift', 'min 1x/shift', 'min 2x/shift/MM', 'min 2x/shift/MM', 'min 1x/shift/MM'],
    ];

    private array $bc12Ref = [
        'standard' => ['3~8', '1.5~2.0'],
        'target' => ['12', '1,5'],
        'satuan' => ['%', '%'],
        'ct' => ['', ''],
        'freq' => ['min 2x/shift/MM', ''],
    ];
    private array $bc11Ref = [
        'standard' => ['6.4~7.0', '0.4~1.0'],
        'target' => ['6,8', '0,7'],
        'satuan' => ['%', '%'],
        'ct' => ['', ''],
        'freq' => ['min 1x/shift', ''],
    ];
    private array $bc16Ref = [
        'standard' => ['3.7~4.8', '2.2~2.3'],
        'target' => ['36', '2,6'],
        'satuan' => ['%', '%'],
        'ct' => ['', ''],
        'freq' => ['min 2x/shift', ''],
    ];
    /** ======================================================== */

    public function __construct(
        protected ?string $start = null,
        protected ?string $end = null,
        protected ?string $shift = null,
        protected ?string $q = null,
        protected ?string $mm = null
    ) {
    }

    public function query()
    {
        $q = Process::query()->select([
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

        if ($this->mm)
            $q->where('mm', $this->mm);
        if ($this->start)
            $q->whereDate('date', '>=', $this->start);
        if ($this->end)
            $q->whereDate('date', '<=', $this->end);
        if ($this->shift)
            $q->where('shift', $this->shift);
        if ($this->q) {
            $q->where(function ($w) {
                $w->where('rs_type', 'like', '%' . $this->q . '%')
                    ->orWhere('mm', 'like', '%' . $this->q . '%')
                    ->orWhere('mix_ke', 'like', '%' . $this->q . '%');
            });
        }

        return $q->orderBy('date')->orderBy('mix_start');
    }

    public function headings(): array
    {
        // 9 baris header; data mulai baris-10
        return [
            // Row1: Title
            array_merge(['MIX MULLER :   1 & 2'], array_fill(0, 36, '')),

            // Row2: Groups (persis template)
            [
                'Item Check',
                '',
                '',                   // A..C
                'TIME',
                '',
                '',                         // D..F
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
                '',
                '', // G..T
                'ADDITIVE ADDITIONAL',
                '',
                '',          // U..W
                'BC 12 Sample',
                '',                    // X..Y
                'BC 11 Sample',
                '',                    // Z..AA
                'BC 16 Sample',
                '',                    // AB..AC
                'Return Sand Check (2x/type/shift)',
                '',
                '',
                '',
                '',
                '',
                '',
                '', // AD..AK
            ],

            // Row3: Leaves (nama parameter) + RS level-1
            [
                'Process Date',
                'Shift',
                'MM No',
                'Mix No',
                'Mix Start',
                'Mix Finish',
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
                'CB',
                'M',
                'AC',
                'Vsd',
                'CB',
                'M',
                'Time',
                'Type',
                'Moisture',
                '',
                '',
                'Temperature',
                '',
                '',
            ],

            // Row4: RS BC9/10/11 (yang lain di-merge vertikal)
            [
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
                '',
                '',
                '',
                '',
                'BC9',
                'BC10',
                'BC11',
                'BC9',
                'BC10',
                'BC11',
            ],

            // Row5..Row9: tabel referensi (Standard/Target/Satuan/C/T/Freq)
            ['Standard', '', '', '', '', '', ...array_fill(0, 20, ''), ...array_fill(0, 3, ''), ...array_fill(0, 2, ''), ...array_fill(0, 2, ''), ...array_fill(0, 8, '')],
            ['Target', '', '', '', '', '', ...array_fill(0, 20, ''), ...array_fill(0, 3, ''), ...array_fill(0, 2, ''), ...array_fill(0, 2, ''), ...array_fill(0, 8, '')],
            ['Satuan', '', '', '', '', '', ...array_fill(0, 20, ''), ...array_fill(0, 3, ''), ...array_fill(0, 2, ''), ...array_fill(0, 2, ''), ...array_fill(0, 8, '')],
            ['C/T (detik)', '', '', '', '', '', ...array_fill(0, 20, ''), ...array_fill(0, 3, ''), ...array_fill(0, 2, ''), ...array_fill(0, 2, ''), ...array_fill(0, 8, '')],
            ['Freq. Check', '', '', '', '', '', ...array_fill(0, 20, ''), ...array_fill(0, 3, ''), ...array_fill(0, 2, ''), ...array_fill(0, 2, ''), ...array_fill(0, 8, '')],
        ];
    }

    public function map($row): array
    {
        $fmtDate = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('Y-m-d') : '';
        $fmtTime = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('H:i') : '';
        $val = fn($v) => $v ?? '';

        return [
            $fmtDate($row->date),
            $val($row->shift),
            $val($row->mm),

            $val($row->mix_ke),
            $fmtTime($row->mix_start),
            $fmtTime($row->mix_finish),

            $val($row->mm_p),
            $val($row->mm_c),
            $val($row->mm_gt),
            $val($row->mm_cb_mm),
            $val($row->mm_cb_lab),
            $val($row->mm_m),
            $val($row->mm_bakunetsu),
            $val($row->mm_ac),
            $val($row->mm_tc),
            $val($row->mm_vsd),
            $val($row->mm_ig),
            $val($row->mm_cb_weight),
            $val($row->mm_tp50_weight),
            $val($row->mm_ssi),

            $val($row->add_m3),
            $val($row->add_vsd),
            $val($row->add_sc),

            $val($row->bc12_cb),
            $val($row->bc12_m),
            $val($row->bc11_ac),
            $val($row->bc11_vsd),
            $val($row->bc16_cb),
            $val($row->bc16_m),

            $fmtTime($row->rs_time),
            $val($row->rs_type),
            $val($row->bc9_moist),
            $val($row->bc10_moist),
            $val($row->bc11_moist),
            $val($row->bc9_temp),
            $val($row->bc10_temp),
            $val($row->bc11_temp),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // ======= MERGES =======
        $sheet->mergeCells('A1:AK1');              // title

        // groups row2
        $sheet->mergeCells('A2:C2'); // Item Check
        $sheet->mergeCells('D2:F2'); // TIME
        $sheet->mergeCells('G2:T2'); // MM Sample
        $sheet->mergeCells('U2:W2'); // Additive
        $sheet->mergeCells('X2:Y2'); // BC12
        $sheet->mergeCells('Z2:AA2'); // BC11
        $sheet->mergeCells('AB2:AC2'); // BC16
        $sheet->mergeCells('AD2:AK2'); // RS

        // leaf vertical (row3:row4) — selain RS
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC'] as $col) {
            $sheet->mergeCells("{$col}3:{$col}4");
        }

        // RS subgroups
        $sheet->mergeCells('AD3:AD4'); // Time
        $sheet->mergeCells('AE3:AE4'); // Type
        $sheet->mergeCells('AF3:AH3'); // Moisture
        $sheet->mergeCells('AI3:AK3'); // Temperature

        // blok label referensi (kolom A..F vertikal)
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->mergeCells("{$col}5:{$col}9");
        }

        // ======= STYLING =======
        $sheet->getStyle('A1:AK1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // header (row2..row4)
        $sheet->getStyle('A2:AK4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        // warna mendekati template
        $sheet->getStyle('A2:C4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA'); // Item Check
        $sheet->getStyle('D2:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA'); // TIME

        $sheet->getStyle('G2:T2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC'); // MM grp
        $sheet->getStyle('G3:T4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9E5');

        $sheet->getStyle('U2:W2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC'); // Add
        $sheet->getStyle('U3:W4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9E5');

        $sheet->getStyle('X2:AC2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7'); // BC
        $sheet->getStyle('X3:AC4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF5FD');

        $sheet->getStyle('AD2:AK2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699'); // RS
        $sheet->getStyle('AD3:AK4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');

        // garis besar semua
        $highest = max(9, $sheet->getHighestRow());
        $sheet->getStyle("A1:AK{$highest}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000']]],
        ]);

        // tinggi baris header
        foreach ([2, 3, 4] as $r)
            $sheet->getRowDimension($r)->setRowHeight(22);
        foreach ([5, 6, 7, 8, 9] as $r)
            $sheet->getRowDimension($r)->setRowHeight(20);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ==== isi nilai referensi (baris 5..9) ====
                // mapping kolom parameter agar gampang taruh teks
                $mmCols = ['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T']; // 14 kolom MM
                $bc12Cols = ['X', 'Y'];
                $bc11Cols = ['Z', 'AA'];
                $bc16Cols = ['AB', 'AC'];

                // MM Sample
                foreach (['standard' => 5, 'target' => 6, 'satuan' => 7, 'ct' => 8, 'freq' => 9] as $key => $row) {
                    $vals = $this->mmRef[$key] ?? [];
                    foreach ($mmCols as $i => $col) {
                        $sheet->setCellValue("{$col}{$row}", $vals[$i] ?? '');
                    }
                }

                // BC 12/11/16
                foreach (['standard' => 5, 'target' => 6, 'satuan' => 7, 'ct' => 8, 'freq' => 9] as $key => $row) {
                    foreach ($bc12Cols as $i => $col)
                        $sheet->setCellValue("{$col}{$row}", $this->bc12Ref[$key][$i] ?? '');
                    foreach ($bc11Cols as $i => $col)
                        $sheet->setCellValue("{$col}{$row}", $this->bc11Ref[$key][$i] ?? '');
                    foreach ($bc16Cols as $i => $col)
                        $sheet->setCellValue("{$col}{$row}", $this->bc16Ref[$key][$i] ?? '');
                }

                // label kiri (kol A) untuk baris 5..9 sudah di headings()
    
                // freeze & filter untuk area data
                $sheet->freezePane('A10');
                $sheet->setAutoFilter('A9:AK9');
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 8,
            'C' => 8,
            'D' => 10,
            'E' => 10,
            'F' => 10,
            'G' => 8,
            'H' => 8,
            'I' => 8,
            'J' => 10,
            'K' => 10,
            'L' => 8,
            'M' => 10,
            'N' => 8,
            'O' => 8,
            'P' => 10,
            'Q' => 8,
            'R' => 10,
            'S' => 10,
            'T' => 8,
            'U' => 8,
            'V' => 8,
            'W' => 8,
            'X' => 10,
            'Y' => 8,
            'Z' => 10,
            'AA' => 10,
            'AB' => 10,
            'AC' => 10,
            'AD' => 10,
            'AE' => 8,
            'AF' => 10,
            'AG' => 10,
            'AH' => 10,
            'AI' => 10,
            'AJ' => 10,
            'AK' => 10,
        ];
    }
}
