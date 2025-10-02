<thead class="table-dark">
    <tr>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Action</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Date</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Shift</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MM</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MIX KE</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MIX START</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MIX FINISH</th>
        {{-- Tetap 16 kolom untuk grup MM Sample (termasuk Nomor Mesin) --}}
        <th colspan="16" class="text-center">MM Sample</th>
        <th colspan="3" class="text-center">Additive</th>
        <th colspan="6" class="text-center">BC Sample</th>
        <th colspan="8" class="text-center">Return Sand</th>
        <th colspan="8" class="text-center">Moulding Data</th>
    </tr>

    @php
        use Illuminate\Support\Facades\DB;

        $COL_MM = [
            'P' => 'mm_p',
            'C' => 'mm_c',
            'G.T' => 'mm_gt',
            'CB MM' => 'mm_cb_mm',
            'CB Lab' => 'mm_cb_lab',
            'Moisture' => 'mm_m',
            'Nomor Mesin' => 'machine_no',   // <— ini akan di-rowspan
            'Bakunetsu' => 'mm_bakunetsu',
            'AC' => 'mm_ac',
            'TC' => 'mm_tc',
            'Vsd' => 'mm_vsd',
            'IG' => 'mm_ig',
            'CB weight' => 'mm_cb_weight',
            'TP 50 weight' => 'mm_tp50_weight',
            'TP 50 Height' => 'mm_tp50_height',
            'SSI' => 'mm_ssi',
        ];
        $COL_ADDITIVE = ['M3', 'VSD', 'SC'];
        $COL_BC = [
            'BC12 CB' => 'bc12_cb',
            'BC12 M' => 'bc12_m',
            'BC11 AC' => 'bc11_ac',
            'BC11 VSD' => 'bc11_vsd',
            'BC16 CB' => 'bc16_cb',
            'BC16 M' => 'bc16_m',
        ];
        $COL_RS = ['RS Time', 'Type', 'Moist BC9', 'Moist BC10', 'Moist BC11', 'Temp BC9', 'Temp BC10', 'Temp BC11'];
        $COL_MD = ['Add Water MM1', 'Add Water MM2', 'Temp Sand MM1', 'RCS Pick Up', 'Total Flask', 'RCS Avg', 'Add Bentonite MA', 'Total Sand'];

        $__std = DB::table('tb_jsh_standards')->first();

        $fmt = function ($v) {
            if ($v === null || $v === '')
                return null;
            $s = str_replace(',', '.', (string) $v);
            if (is_numeric($s))
                $s = rtrim(rtrim($s, '0'), '.');
            return $s;
        };

        $range = function (?string $field) use ($__std, $fmt) {
            if (!$field || !$__std)
                return '-';
            $min = $fmt($__std->{$field . '_min'} ?? null);
            $max = $fmt($__std->{$field . '_max'} ?? null);
            return ($min !== null || $max !== null) ? (($min ?? '-') . ' ~ ' . ($max ?? '-')) : '-';
        };

        $UNIT_MM = [
            'P' => 'g / Cm²',
            'C' => 'Mpa',
            'G.T' => 'g / Cm²',
            'CB MM' => '%',
            'CB Lab' => '%',
            'M' => '%',
            'Bakunetsu' => '%',
            'AC' => '%',
            'TC' => '%',
            'Vsd' => '%',
            'IG' => '%',
            'CB weight' => 'g',
            'TP 50 weight' => 'g',
            'TP 50 Height' => '-',
            'SSI' => '%',
        ];
        $FREQ_MM = [
            'P' => 'min 6x/shift/MM',
            'C' => 'min 6x/shift/MM',
            'G.T' => 'min 2x/shift/MM',
            'CB MM' => 'Every mixing',
            'CB Lab' => 'min 6x/shift/MM',
            'M' => 'min 6x/shift/MM',
            'Bakunetsu' => 'min 1x/shift/MM',
            'AC' => 'min 2x/shift',
            'TC' => 'min 1x/shift',
            'Vsd' => 'min 2x/shift',
            'IG' => 'min 2x/shift/MM',
            'CB weight' => 'min 2x/shift/MM',
            'TP 50 weight' => 'min 2x/shift/MM',
            'TP 50 Height' => 'min 1x/shift/MM',
            'SSI' => 'min 2x/shift/MM',
        ];
        $UNIT_BC = ['BC12 CB' => '%', 'BC12 M' => '%', 'BC11 AC' => '%', 'BC11 VSD' => '%', 'BC16 CB' => '%', 'BC16 M' => '%'];
        $FREQ_BC = ['BC12 CB' => 'min 2x/shift', 'BC12 M' => 'min 2x/shift', 'BC11 AC' => 'min 1x/shift', 'BC11 VSD' => 'min 1x/shift', 'BC16 CB' => 'min 2x/shift', 'BC16 M' => 'min 2x/shift'];
    @endphp

    {{-- Baris LABEL: “Nomor Mesin” dibikin rowspan=4, yang lain normal --}}
    <tr>
        @foreach($COL_MM as $label => $field)
            @if($label === 'Nomor Mesin')
                <th class="text-center align-middle" style="min-width:120px;" rowspan="4">Nomor Mesin</th>
            @else
                <th class="text-center" style="min-width:120px;">{{ $label }}</th>
            @endif
        @endforeach

        @foreach($COL_ADDITIVE as $c)
            <th class="text-center align-middle" style="min-width:120px;" rowspan="4">{{ $c }}</th>
        @endforeach

        @foreach($COL_BC as $label => $field)
            <th class="text-center" style="min-width:120px;">{{ $label }}</th>
        @endforeach

        @foreach($COL_RS as $c)
            <th class="text-center align-middle" style="min-width:120px;" rowspan="4">{{ $c }}</th>
        @endforeach

        @foreach($COL_MD as $c)
            <th class="text-center align-middle" style="min-width:120px;" rowspan="4">{{ $c }}</th>
        @endforeach
    </tr>

    {{-- Baris RANGE: skip “Nomor Mesin” --}}
    <tr>
        @foreach($COL_MM as $label => $field)
            @if($label !== 'Nomor Mesin')
                <th class="text-center">{{ $range($field) }}</th>
            @endif
        @endforeach
        @foreach($COL_BC as $label => $field)
            <th class="text-center">{{ $range($field) }}</th>
        @endforeach
    </tr>

    {{-- Baris UNIT: skip “Nomor Mesin” --}}
    <tr>
        @foreach($COL_MM as $label => $field)
            @if($label !== 'Nomor Mesin')
                <th class="text-center">{{ $UNIT_MM[$label] ?? '-' }}</th>
            @endif
        @endforeach
        @foreach($COL_BC as $label => $field)
            <th class="text-center">{{ $UNIT_BC[$label] ?? '-' }}</th>
        @endforeach
    </tr>

    {{-- Baris FREKUENSI: skip “Nomor Mesin” --}}
    <tr>
        @foreach($COL_MM as $label => $field)
            @if($label !== 'Nomor Mesin')
                <th class="text-center">{{ $FREQ_MM[$label] ?? '-' }}</th>
            @endif
        @endforeach
        @foreach($COL_BC as $label => $field)
            <th class="text-center">{{ $FREQ_BC[$label] ?? '-' }}</th>
        @endforeach
    </tr>
</thead>