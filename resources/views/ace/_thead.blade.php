<thead class="table-dark">
  {{-- ROW 1: GROUP HEADERS --}}
  <tr>
    <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Action</th>
    <th class="text-center align-middle" rowspan="5" style="min-width:80px;">No</th>
    <th class="text-center align-middle" rowspan="5" style="min-width:100px;">Date</th>
    <th class="text-center align-middle" rowspan="5" style="min-width:100px;">Shift</th>
    <th class="text-center align-middle" rowspan="5" style="min-width:140px;">Type Product</th>
    <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Sample Start</th>
    <th class="text-center align-middle" rowspan="5" style="min-width:120px;">Sample Finish</th>

    <th colspan="15" class="text-center">MM Sample</th>
    <th colspan="6"  class="text-center">Additive Additional</th>
    <th colspan="4"  class="text-center">Pengecekan BC13</th>
  </tr>

  @php
    $COL_MM   = ['P','C','G.T','Cb Lab','Moisture','Nomor Mesin','Bakunetsu','AC','TC','VSD','IG','CB Weight','TP 50 Weight','SSI','MOIST'];
    $COL_ADD  = ['DW29 VAS','DW29 Debu','DW31 VAS','DW31 ID','DW31 Moldex','DW31 SC'];
    $COL_BC13 = ['NO Mix','BC13 CB','BC13 C','BC13 M'];

    // semua kolom yang di-merge rowspan=4
    $ROWSPAN4 = [
      'Nomor Mesin','MOIST',
      'DW29 VAS','DW29 Debu',
      'DW31 VAS','DW31 ID','DW31 Moldex','DW31 SC',
      'NO Mix'
    ];

    $STD = [
      'P' => ['range'=>'150 ~ 240','unit'=>'g / Cm²','freq'=>'min 4x/shift'],
      'C' => ['range'=>'16.0 ~ 21.0','unit'=>'Mpa','freq'=>'min 4x/shift'],
      'G.T' => ['range'=>'400 ~ 700','unit'=>'g / Cm²','freq'=>'min 2x/shift'],
      'Cb Lab' => ['range'=>'33 ~ 43','unit'=>'%','freq'=>'min 4x/shift'],
      'Moisture' => ['range'=>'3.0 ~ 4.0','unit'=>'%','freq'=>'min 4x/shift'],
      'Bakunetsu' => ['range'=>'Max 80','unit'=>'%','freq'=>'min 1x/shift'],
      'AC' => ['range'=>'8.0 ~ 11.0','unit'=>'%','freq'=>'min 1x/shift'],
      'TC' => ['range'=>'10.0 ~ 16.0','unit'=>'%','freq'=>'min 1x/shift'],
      'VSD' => ['range'=>'0.2 ~ 0.7','unit'=>'%','freq'=>'min 1x/shift'],
      'IG' => ['range'=>'2.0 ~ 3.0','unit'=>'%','freq'=>'min 1x/shift'],
      'CB Weight' => ['range'=>'-','unit'=>'g','freq'=>'min 4x/shift'],
      'TP 50 Weight' => ['range'=>'-','unit'=>'g','freq'=>'min 4x/shift'],
      'SSI' => ['range'=>'Min 90','unit'=>'%','freq'=>'min 1x/shift'],

      // BC13
      'BC13 CB' => ['range'=>'33 ~ 43','unit'=>'Lab','freq'=>'min 6x/shift'],
      'BC13 C'  => ['range'=>'16.0 ~ 21.0','unit'=>'Mpa','freq'=>'min 6x/shift'],
      'BC13 M'  => ['range'=>'3.3 ~ 4.0','unit'=>'%','freq'=>'min 6x/shift'],
    ];
    $v = fn($k,$f) => $STD[$k][$f] ?? '-';
  @endphp

  {{-- ROW 2: COLUMN HEADERS --}}
  <tr>
    @foreach($COL_MM as $c)
      @if(in_array($c,$ROWSPAN4))
        <th class="text-center align-middle" rowspan="4" style="min-width:120px;">{{ $c }}</th>
      @else
        <th class="text-center" style="min-width:120px;">{{ $c }}</th>
      @endif
    @endforeach
    @foreach($COL_ADD as $c)
      @if(in_array($c,$ROWSPAN4))
        <th class="text-center align-middle" rowspan="4" style="min-width:120px;">{{ $c }}</th>
      @else
        <th class="text-center" style="min-width:120px;">{{ $c }}</th>
      @endif
    @endforeach
    @foreach($COL_BC13 as $c)
      @if(in_array($c,$ROWSPAN4))
        <th class="text-center align-middle" rowspan="4" style="min-width:120px;">{{ $c }}</th>
      @else
        <th class="text-center" style="min-width:120px;">{{ $c }}</th>
      @endif
    @endforeach
  </tr>

  {{-- ROW 3: RANGE --}}
  <tr class="std-row">
    @foreach($COL_MM as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="text-center">{{ $v($c,'range') }}</th> @endif
    @endforeach
    @foreach($COL_ADD as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="std-spacer"></th> @endif
    @endforeach
    @foreach($COL_BC13 as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="text-center">{{ $v($c,'range') }}</th> @endif
    @endforeach
  </tr>

  {{-- ROW 4: UNIT --}}
  <tr class="std-row">
    @foreach($COL_MM as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="text-center">{{ $v($c,'unit') }}</th> @endif
    @endforeach
    @foreach($COL_ADD as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="std-spacer"></th> @endif
    @endforeach
    @foreach($COL_BC13 as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="text-center">{{ $v($c,'unit') }}</th> @endif
    @endforeach
  </tr>

  {{-- ROW 5: FREQ --}}
  <tr class="std-row">
    @foreach($COL_MM as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="text-center">{{ $v($c,'freq') }}</th> @endif
    @endforeach
    @foreach($COL_ADD as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="std-spacer"></th> @endif
    @endforeach
    @foreach($COL_BC13 as $c)
      @if(!in_array($c,$ROWSPAN4)) <th class="text-center">{{ $v($c,'freq') }}</th> @endif
    @endforeach
  </tr>
</thead>
