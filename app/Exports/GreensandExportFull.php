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
    public function __construct(
        protected ?string $start = null,
        protected ?string $end = null,
        protected ?string $shift = null,
        protected ?string $q = null,
        protected ?string $mm = null, // contoh: 'MM1' / 'MM2'
    ) {
    }

    public function query()
    {
        $q = Process::query()->select([
            // identitas
            'date',
            'shift',
            'mm',
            'mix_ke',
            'mix_start',
            'mix_finish',
            // mm sample
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
            // additive
            'add_m3',
            'add_vsd',
            'add_sc',
            // bc sample
            'bc12_cb',
            'bc12_m',
            'bc11_ac',
            'bc11_vsd',
            'bc16_cb',
            'bc16_m',
            // return sand
            'rs_time',
            'rs_type',
            'bc9_moist',
            'bc10_moist',
            'bc11_moist',
            'bc9_temp',
            'bc10_temp',
            'bc11_temp',
        ]);

        // filter optional biar sama dengan UI
        if ($this->mm)
            $q->where('mm', $this->mm);     // DB kamu simpan 'MM1'/'MM2'. Kalau angka, mapping dulu.

        if ($this->start)
            $q->whereDate('date', '>=', $this->start);

        if ($this->end)
            $q->whereDate('date', '<=', $this->end);

        if ($this->shift)
            $q->where('shift', $this->shift);

        // PENTING: pakai $this->q (di-controller diisi dari 'keyword')
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
        // Baris 1: judul group; Baris 2: subheader
        return [
            [
                'Process Date',
                'Shift',
                'MM No',
                'Mix No',
                'Mix Start',
                'Mix Finish',
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
                'GT',
                'CB (MM)',
                'CB (Lab)',
                'M',
                'Bakunetsu',
                'AC',
                'TC',
                'VSD (MM)',
                'IG',
                'CB Weight',
                'TP50 Weight',
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
            ],
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
        // Merge blok judul
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');
        $sheet->mergeCells('D1:D2');
        $sheet->mergeCells('E1:E2');
        $sheet->mergeCells('F1:F2');
        $sheet->mergeCells('G1:T1');   // MM
        $sheet->mergeCells('U1:W1');   // Additive
        $sheet->mergeCells('X1:AC1');  // BC
        $sheet->mergeCells('AD1:AK1'); // Return Sand

        $lastCol = 'AK';
        $highest = $sheet->getHighestRow();

        // Header styling
        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Warna grup (sekilas menyerupai form)
        $sheet->getStyle('A1:F2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2EFDA');
        $sheet->getStyle('G1:T1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        $sheet->getStyle('G2:T2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9E5');
        $sheet->getStyle('U1:W1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        $sheet->getStyle('U2:W2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9E5');
        $sheet->getStyle('X1:AC1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
        $sheet->getStyle('X2:AC2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF5FD');
        $sheet->getStyle('AD1:AK1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE699');
        $sheet->getStyle('AD2:AK2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');

        // Border seluruh area
        $sheet->getStyle("A1:{$lastCol}{$highest}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Tinggi header
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(24);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A3');          // kunci header
                $sheet->setAutoFilter('A2:AK2');   // filter di baris 2
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
