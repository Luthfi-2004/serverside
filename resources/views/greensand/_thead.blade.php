<thead class="table-dark">
    <tr>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Action</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Date</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Shift</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MM</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MIX KE</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MIX START</th>
        <th class="text-center align-middle" rowspan="5" style="min-width:120px;">MIX FINISH</th>

        {{-- Group headers --}}
        <th colspan="15" class="text-center">MM Sample</th>
        <th colspan="3" class="text-center">Additive</th>
        <th colspan="6" class="text-center">BC Sample</th>
        <th colspan="8" class="text-center">Return Sand</th>
        <th colspan="8" class="text-center">Moulding Data</th>
    </tr>

    @php
        // Kolom per-bagian
        $COL_MM = [
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
            'TP 50 Height',
            'SSI',
        ];
        $COL_ADDITIVE = ['M3', 'VSD', 'SC'];
        $COL_BC = ['BC12 CB', 'BC12 M', 'BC11 AC', 'BC11 VSD', 'BC16 CB', 'BC16 M'];
        $COL_RS = ['RS Time', 'Type', 'Moist BC9', 'Moist BC10', 'Moist BC11', 'Temp BC9', 'Temp BC10', 'Temp BC11'];
        $COL_MD = ['Add Water MM1', 'Add Water MM2', 'Temp Sand MM1', 'RCS Pick Up', 'Total Flask', 'RCS Avg', 'Add Bentonite MA', 'Total Sand'];

        // STD hanya untuk MM Sample & BC Sample
        $STD = [
            'P' => ['range' => '220 ~ 260', 'unit' => 'g / Cm²', 'freq' => 'min 6x/shift/MM'],
            'C' => ['range' => '13.5 ~ 17.5', 'unit' => 'Mpa', 'freq' => 'min 6x/shift/MM'],
            'G.T' => ['range' => '450 ~ 650', 'unit' => 'g / Cm²', 'freq' => 'min 2x/shift/MM'],
            'CB MM' => ['range' => '40 ~ 43', 'unit' => '%', 'freq' => 'Every mixing'],
            'CB Lab' => ['range' => '32.0 ~ 42.0', 'unit' => '%', 'freq' => 'min 6x/shift/MM'],
            'M' => ['range' => '2,45 ~ 2,85', 'unit' => '%', 'freq' => 'min 6x/shift/MM'],
            'Bakunetsu' => ['range' => '20 ~ 85', 'unit' => '%', 'freq' => 'min 1x/shift/MM'],
            'AC' => ['range' => '6,7 ~ 7,3', 'unit' => '%', 'freq' => 'min 2x/shift'],
            'TC' => ['range' => '9,0 ~ 11,0', 'unit' => '%', 'freq' => 'min 1x/shift'],
            'Vsd' => ['range' => '0,7 ~ 1,3', 'unit' => '%', 'freq' => 'min 2x/shift'],
            'IG' => ['range' => '3,0 ~ 4,0', 'unit' => '%', 'freq' => 'min 2x/shift/MM'],
            'CB weight' => ['range' => '163 ~ 170', 'unit' => 'g', 'freq' => 'min 2x/shift/MM'],
            'TP 50 weight' => ['range' => '141 ~ 144', 'unit' => 'g', 'freq' => 'min 2x/shift/MM'],
            'TP 50 Height' => ['range' => '48 ~ 52', 'unit' => '-', 'freq' => 'min 1x/shift/MM'],
            'SSI' => ['range' => '89 ~ 95', 'unit' => '%', 'freq' => 'min 2x/shift/MM'],

            // BC Sample
            'BC12 CB' => ['range' => '9 ~ 20', 'unit' => '%', 'freq' => 'min 2x/shift'],
            'BC12 M' => ['range' => '1 ~ 2', 'unit' => '%', 'freq' => 'min 2x/shift'],
            'BC11 AC' => ['range' => '6,4 ~ 7,0', 'unit' => '%', 'freq' => 'min 1x/shift'],
            'BC11 VSD' => ['range' => '0,4 ~ 1,0', 'unit' => '%', 'freq' => 'min 1x/shift'],
            'BC16 CB' => ['range' => '31,0 ~ 41,0', 'unit' => '%', 'freq' => 'min 2x/shift'],
            'BC16 M' => ['range' => '2,4 ~ 2,8', 'unit' => '%', 'freq' => 'min 2x/shift'],
        ];
        $v = fn($k, $f) => $STD[$k][$f] ?? '-';
    @endphp

    {{-- Baris 2: Header per-kolom --}}
    <tr>
        {{-- MM Sample (pakai standar) --}}
        @foreach($COL_MM as $c)
            <th class="text-center" style="min-width:120px;">{{ $c }}</th>
        @endforeach

        {{-- Additive (rowspan 4) --}}
        @foreach($COL_ADDITIVE as $c)
            <th class="text-center align-middle" style="min-width:120px;" rowspan="4">{{ $c }}</th>
        @endforeach

        {{-- BC Sample (pakai standar) --}}
        @foreach($COL_BC as $c)
            <th class="text-center" style="min-width:120px;">{{ $c }}</th>
        @endforeach

        {{-- Return Sand (rowspan 4) --}}
        @foreach($COL_RS as $c)
            <th class="text-center align-middle" style="min-width:120px;" rowspan="4">{{ $c }}</th>
        @endforeach

        {{-- Moulding Data (rowspan 4) --}}
        @foreach($COL_MD as $c)
            <th class="text-center align-middle" style="min-width:120px;" rowspan="4">{{ $c }}</th>
        @endforeach
    </tr>

    {{-- Baris 3: Standard / Range (MM Sample + BC Sample saja) --}}
    <tr>
        @foreach($COL_MM as $c)
            <th class="text-center">{{ $v($c, 'range') }}</th>
        @endforeach
        @foreach($COL_BC as $c)
            <th class="text-center">{{ $v($c, 'range') }}</th>
        @endforeach
    </tr>

    {{-- Baris 4: Satuan / Unit --}}
    <tr>
        @foreach($COL_MM as $c)
            <th class="text-center">{{ $v($c, 'unit') }}</th>
        @endforeach
        @foreach($COL_BC as $c)
            <th class="text-center">{{ $v($c, 'unit') }}</th>
        @endforeach
    </tr>

    {{-- Baris 5: Freq.Check --}}
    <tr>
        @foreach($COL_MM as $c)
            <th class="text-center">{{ $v($c, 'freq') }}</th>
        @endforeach
        @foreach($COL_BC as $c)
            <th class="text-center">{{ $v($c, 'freq') }}</th>
        @endforeach
    </tr>
</thead>