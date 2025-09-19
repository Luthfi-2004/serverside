<?php

namespace App\Exports;

use App\Models\JshGfn;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class JshGfnExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithStyles,
    WithEvents,
    ShouldAutoSize,
    WithTitle
{
    /** urutan mesh fix sama kaya di page */
    private array $meshes  = ['18,5', '26', '36', '50', '70', '100', '140', '200', '280', 'PAN'];

    public function __construct(
        protected ?string $date = null,      // yyyy-mm-dd
        protected ?string $shift = null      // D|S|N
    ) {}

    public function title(): string
    {
        return 'JSH GFN';
    }

    public function collection(): Collection
    {
        $q = JshGfn::query()
            ->when($this->date,  fn($qq) => $qq->whereDate('gfn_date', $this->date))
            ->when($this->shift, fn($qq) => $qq->where('shift', $this->shift));

        // urutkan: tanggal, shift (D,S,N), lalu urutan mesh custom, created_at terbaru di bawah group
        $q->orderBy('gfn_date')
          ->orderByRaw("FIELD(shift,'D','S','N')")
          ->orderByRaw("FIELD(mesh,'" . implode("','", $this->meshes) . "')")
          ->orderBy('created_at', 'desc');

        return $q->get();
    }

    public function headings(): array
    {
        return [
            'Batch Code',
            'GFN Date',
            'Shift',
            'Mesh',
            'Gram',
            'Percentage',
            'Index',
            'Percentage Index',
            'Total Gram',
            'Total PI',
            'Nilai GFN',
            'Created At',
            'Updated At',
        ];
    }

    public function map($row): array
    {
        // Nilai GFN: kalau di tabel detail tidak ada, hitung dari total PI
        $nilaiGfn = null;
        if (!is_null($row->total_percentage_index)) {
            $nilaiGfn = round(((float)$row->total_percentage_index) / 100, 2);
        } elseif (property_exists($row, 'nilai_gfn') && !is_null($row->nilai_gfn)) {
            $nilaiGfn = (float)$row->nilai_gfn;
        }

        return [
            $row->batch_code,
            optional($row->gfn_date)->format('Y-m-d'),
            $row->shift,
            $row->mesh,
            (float) $row->gram,
            (float) $row->percentage,
            (int)   $row->index,
            (float) $row->percentage_index,
            (float) $row->total_gram,
            (float) $row->total_percentage_index,
            $nilaiGfn,
            optional($row->created_at)?->format('Y-m-d H:i:s'),
            optional($row->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }

    public function columnFormats(): array
    {
        // A=1, B=2, ... biar gampang: Gram(E), Percentage(F), Index(G), PI(H), TotalGram(I), TotalPI(J), NilaiGFN(K)
        return [
            'E' => NumberFormat::FORMAT_NUMBER_00,   // Gram
            'F' => NumberFormat::FORMAT_NUMBER_00,   // Percentage
            'G' => NumberFormat::FORMAT_NUMBER,      // Index (integer)
            'H' => NumberFormat::FORMAT_NUMBER_0,    // Percentage Index (1 desimal cukup? -> heading kita 1 desi, tapi ini format 0.0 bisa diubah bawah)
            'I' => NumberFormat::FORMAT_NUMBER_00,   // Total Gram
            'J' => NumberFormat::FORMAT_NUMBER_0,    // Total PI
            'K' => NumberFormat::FORMAT_NUMBER_00,   // Nilai GFN
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // header tebal + center
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal('center');

        // body align: angka rata kanan biar enak dibaca
        $lastRow = max(1, $sheet->getHighestRow());
        $sheet->getStyle("E2:K{$lastRow}")->getAlignment()->setHorizontal('right');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // freeze header
                $event->sheet->freezePane('A2');

                // set format H (PI) jadi 1 desimal
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $event->sheet->getDelegate()->getStyle("H2:H{$highestRow}")
                      ->getNumberFormat()->setFormatCode('0.0');
            },
        ];
    }
}
