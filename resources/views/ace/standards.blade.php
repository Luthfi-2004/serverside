@extends('layouts.app')
@section('title', 'ACE Standards')

@push('styles')
  <base href="{{ url('/') }}/">
@endpush

@section('content')
<div class="page-content">
  <div class="container-fluid">

    <div class="page-title-box d-flex align-items-center justify-content-between">
      <h4 class="mb-0">ACE Standards</h4>
      <div class="page-title-right">
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="javascript:void(0);">ACE LINE</a></li>
          <li class="breadcrumb-item active">Standards</li>
        </ol>
      </div>
    </div>

    @if(session('status'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    @php
      /**
       * Formatter untuk VALUE di <input type="number">
       * - 12.000  -> "12"
       * - 12.300  -> "12.3"
       * - 12.345  -> "12.35"  (dibulatkan 2 desimal)
       * - Tidak ada pemisah ribuan, desimal pakai titik.
       */
      $__in = function($v) {
        if ($v === null || $v === '') return '';
        if (!is_numeric($v)) return (string)$v;
        $n = (float)$v;
        // bulatkan 2 desimal dulu
        $s = number_format($n, 2, '.', '');   // contoh: "12.00" / "12.30" / "12.35"
        // hapus trailing nol dan titik jika perlu
        $s = rtrim(rtrim($s, '0'), '.');      // "12" / "12.3" / "12.35"
        return $s;
      };
    @endphp

    <form method="POST" action="{{ route('ace.standards.update') }}">
      @csrf

      <div class="card mb-3">
        <div class="card-header"><strong>MM Sample</strong></div>
        <div class="card-body">
          <div class="row">
            @php
              $fields = [
                'p'=>'P','c'=>'C','gt'=>'G.T','cb_lab'=>'Cb Lab','moisture'=>'Moisture',
                'bakunetsu'=>'Bakunetsu','ac'=>'AC','tc'=>'TC','vsd'=>'VSD','ig'=>'IG',
                'cb_weight'=>'CB Weight','tp50_weight'=>'TP 50 Weight','ssi'=>'SSI',
              ];
            @endphp
            @foreach($fields as $key=>$label)
              <div class="col-md-4 mb-3">
                <label class="mb-1">{{ $label }}</label>
                <div class="form-row">
                  <div class="col">
                    <input
                      type="number"
                      step="0.01"
                      inputmode="decimal"
                      name="{{ $key }}_min"
                      class="form-control"
                      value="{{ old($key.'_min', $__in($std->{$key.'_min'})) }}"
                      placeholder="Min">
                  </div>
                  <div class="col">
                    <input
                      type="number"
                      step="0.01"
                      inputmode="decimal"
                      name="{{ $key }}_max"
                      class="form-control"
                      value="{{ old($key.'_max', $__in($std->{$key.'_max'})) }}"
                      placeholder="Max">
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><strong>BC13</strong></div>
        <div class="card-body">
          <div class="row">
            @php
              $bc = ['bc13_cb'=>'BC13 CB','bc13_c'=>'BC13 C','bc13_m'=>'BC13 M'];
            @endphp
            @foreach($bc as $key=>$label)
              <div class="col-md-4 mb-3">
                <label class="mb-1">{{ $label }}</label>
                <div class="form-row">
                  <div class="col">
                    <input
                      type="number"
                      step="0.01"
                      inputmode="decimal"
                      name="{{ $key }}_min"
                      class="form-control"
                      value="{{ old($key.'_min', $__in($std->{$key.'_min'})) }}"
                      placeholder="Min">
                  </div>
                  <div class="col">
                    <input
                      type="number"
                      step="0.01"
                      inputmode="decimal"
                      name="{{ $key }}_max"
                      class="form-control"
                      value="{{ old($key.'_max', $__in($std->{$key.'_max'})) }}"
                      placeholder="Max">
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="text-right">
        <button class="btn btn-primary"><i class="ri-save-2-line mr-1"></i> Save</button>
      </div>
    </form>

  </div>
</div>
@endsection
