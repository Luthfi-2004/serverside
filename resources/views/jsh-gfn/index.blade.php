@extends('layouts.app')
@section('title', 'JSH GFN GREEN SAND')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">JSH GFN GREEN SAND</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">SandLab</a></li>
                                <li class="breadcrumb-item active">JSH GFN GREEN SAND</li>
                            </ol>
                        </div>
                    </div>

                    @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div> @endif
                    @if($errors->any())
                    <div class="alert alert-danger mb-2">{{ $errors->first() }}</div> @endif

                    @php $isOpen = true; @endphp
                    <div class="card mb-3">
                        <div id="filterHeader"
                            class="card-header bg-light d-flex justify-content-between align-items-center cursor-pointer"
                            data-toggle="collapse" data-target="#filterCollapse"
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="filterCollapse">
                            <h5 class="font-size-14 mb-0"><i class="ri-filter-2-line align-middle mr-1"></i> Filter Data
                            </h5>
                            <i id="filterIcon" class="{{ $isOpen ? 'ri-subtract-line' : 'ri-add-line' }}"></i>
                        </div>
                        <div id="filterCollapse" class="collapse {{ $isOpen ? 'show' : '' }}">
                            <div class="card-body">
                                <form id="filterForm" class="row align-items-end" method="GET"
                                    action="{{ route('jshgfn.index') }}">
                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label for="startDate" class="form-label mb-1">Start Date</label>
                                        <input id="startDate" type="date" name="start_date" class="form-control gs-input"
                                            value="{{ $filters['start'] ?? '' }}" autocomplete="off">
                                    </div>
                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label for="endDate" class="form-label mb-1">End Date</label>
                                        <input id="endDate" type="date" name="end_date" class="form-control gs-input"
                                            value="{{ $filters['end'] ?? '' }}" autocomplete="off"
                                            min="{{ $filters['start'] ?? '' }}">
                                    </div>
                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label class="form-label mb-1">Shift</label>
                                        <select class="form-control" name="shift" autocomplete="off">
                                            <option value="">-- Select Shift --</option>
                                            <option value="D" @selected(($filters['shift'] ?? '') === 'D')>Day</option>
                                            <option value="S" @selected(($filters['shift'] ?? '') === 'S')>Swing</option>
                                            <option value="N" @selected(($filters['shift'] ?? '') === 'N')>Night</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label class="form-label mb-1">Search (mix/model)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="q"
                                                value="{{ $filters['q'] ?? '' }}" placeholder="keyword..."
                                                autocomplete="off">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary btn-sm"><i
                                                        class="ri-search-line"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-12 mt-2">
                                        <div class="d-flex flex-wrap">
                                            <button type="submit" class="btn btn-primary btn-sm mr-2 mb-2">
                                                <i class="ri-search-line mr-1"></i> Search
                                            </button>
                                            <a href="{{ route('jshgfn.index') }}"
                                                class="btn btn-outline-primary btn-sm mr-2 mb-2">
                                                <i class="ri-refresh-line mr-1"></i> Refresh Filter
                                            </a>
                                            <a class="btn btn-outline-success btn-sm mb-2"
                                                href="{{ route('jshgfn.export', request()->only('start_date', 'end_date', 'shift')) }}">
                                                <i class="ri-file-excel-2-line mr-1"></i> Export Excel
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap mb-3">
                        <button id="btn-add-greensand" type="button" class="btn btn-success btn-sm btn-add-gs mr-2 mb-2"
                            data-toggle="modal" data-target="#modal-greensand">
                            <i class="ri-add-line"></i> Add Data
                        </button>

                        @if(!empty($displayRecap))
                            <button type="button" class="btn btn-outline-danger btn-sm mb-2 btn-delete-gs" data-toggle="modal"
                                data-target="#confirmDeleteModal" data-gfn-date="{{ $displayRecap['gfn_date'] }}"
                                data-shift="{{ $displayRecap['shift'] }}">
                                <i class="fas fa-trash"></i> Delete Data
                            </button>
                        @endif
                    </div>


                    {{-- TABEL DETAIL --}}
                    <table id="datatable1" class="table table-bordered table-striped nowrap w-100 mt-2">
                        <thead class="bg-dark text-white text-center">
                            <tr>
                                <th>No</th>
                                <th>Mesh</th>
                                <th>Gram</th>
                                <th>%</th>
                                <th>Index</th>
                                <th>%Index</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @forelse(($displayRows ?? collect()) as $idx => $row)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $row->mesh }}</td>
                                    <td><b>{{ number_format($row->gram ?? 0, 2, ',', '.') }}</b></td>
                                    <td>{{ number_format($row->percentage ?? 0, 2, ',', '.') }}</td>
                                    <td>{{ $row->index ?? 0 }}</td>
                                    <td>{{ number_format($row->percentage_index ?? 0, 1, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada data dalam 24 jam terakhir.</td>
                                </tr>
                            @endforelse

                            @if(!empty($displayRecap))
                                <tr>
                                    <th colspan="2" class="bg-dark text-white">TOTAL</th>
                                    <th class="bg-secondary text-white">
                                        <b>{{ number_format($displayRecap['total_gram'] ?? 0, 2, ',', '.') }}</b>
                                    </th>
                                    <th colspan="2">{{ $displayRecap['judge_overall'] ?? '' }}</th>
                                    <th class="bg-secondary text-white">
                                        {{ number_format($displayRecap['total_pi'] ?? 0, 1, ',', '.') }}
                                    </th>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    {{-- TABEL REKAP --}}
                    <table class="table table-bordered table-striped nowrap mt-4 w-auto">
                        <thead class="bg-dark text-white text-center">
                            <tr>
                                <td>Nilai GFN (Σ %Index / 100)</td>
                                <td><b>{{ isset($displayRecap) ? number_format($displayRecap['nilai_gfn'], 2, ',', '.') : '-' }}</b>
                                </td>
                                <th>JUDGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>% MESH 140 (STD : 3.5 ~ 8.0 %)</td>
                                <td><b>{{ isset($displayRecap) ? number_format($displayRecap['mesh_total140'], 2, ',', '.') : '-' }}</b>
                                </td>
                                <td>{{ $displayRecap['judge_mesh_140'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Σ MESH 50, 70 & 100 (Min 64 %)</td>
                                <td><b>{{ isset($displayRecap) ? number_format($displayRecap['mesh_total70'], 2, ',', '.') : '-' }}</b>
                                </td>
                                <td>{{ $displayRecap['judge_mesh_70'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>% MESH 280 + PAN (STD : 0.00 ~ 1.40 %)</td>
                                <td><b>{{ isset($displayRecap) ? number_format($displayRecap['meshpan'], 2, ',', '.') : '-' }}</b>
                                </td>
                                <td>{{ $displayRecap['judge_meshpan'] ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- MODAL FORM --}}
                    @include('jsh-gfn._form', ['meshes' => $meshes, 'indices' => $indices])
                </div>
            </div>

        </div>
    </div>
    </div>
    </div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteTitle">Confirm Delete</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmDeleteText" class="mb-0">
                    Are you sure you want to delete data for <b><span id="delDateText">—</span></b> (Shift <b><span
                            id="delShiftText">—</span></b>) created today?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light " data-dismiss="modal">Cancel</button>

                {{-- Form hidden untuk submit delete --}}
                <form id="deleteForm" action="{{ route('jshgfn.deleteToday') }}" method="POST" class="m-0 p-0">
                    @csrf
                    <input type="hidden" name="gfn_date" id="delDate">
                    <input type="hidden" name="shift" id="delShift">
                    <button type="submit" class="btn btn-danger" id="confirmDeleteYes">Yes, Delete</button>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
  (function () {
    // ====== Sinkronisasi Start/End Date ======
    function syncDates() {
      var s = document.getElementById("startDate");
      var e = document.getElementById("endDate");
      if (!s || !e) return;
      e.min = s.value || "";
      if (s.value && (!e.value || e.value < s.value)) {
        e.value = s.value;
        e.dispatchEvent(new Event("input", { bubbles: true }));
        e.dispatchEvent(new Event("change", { bubbles: true }));
      } else if (!s.value) {
        e.removeAttribute("min");
      }
    }
    document.addEventListener("input", function (ev) {
      if (ev.target && ev.target.id === "startDate") syncDates();
    });
    document.addEventListener("change", function (ev) {
      if (ev.target && ev.target.id === "startDate") syncDates();
    });

    // ====== Inisialisasi Collapse Filter + ikon ======
    var $ = window.jQuery;
    if ($) {
      var $col = $("#filterCollapse");
      var $icon = $("#filterIcon");
      var $header = $("#filterHeader");

      function setIcon(isOpen) {
        if (!$icon.length) return;
        $icon.removeClass("ri-add-line ri-subtract-line")
             .addClass(isOpen ? "ri-subtract-line" : "ri-add-line");
      }

      if ($col.length) {
        // Bootstrapping state
        setIcon($col.hasClass("show"));

        // Saat show/hide, ganti ikon
        $col.on("shown.bs.collapse", function () { setIcon(true); });
        $col.on("hidden.bs.collapse", function () { setIcon(false); });

        // Klik header toggle collapse (Bootstrap sudah handle)
        $header.on("click", function () {
          // nothing, relying on data-toggle attributes
        });
      }
    }

    // ====== Helper format angka ID ======
    function fmt(num, digits) {
      if (digits === void 0) digits = 2;
      if (!isFinite(num)) num = 0;
      return Number(num).toLocaleString('id-ID', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits
      });
    }

    // ====== Kalkulasi GFN realtime di modal ======
    function recalcGFN() {
      var tbody = document.getElementById('gfnBody');
      if (!tbody) return;
      var rows = tbody.querySelectorAll('tr[data-row]');
      var totalGram = 0;

      rows.forEach(function (tr) {
        var g = parseFloat((tr.querySelector('.gfn-gram') && tr.querySelector('.gfn-gram').value) || '0');
        if (!isNaN(g)) totalGram += g;
      });

      var totalPct = 0, totalPI = 0;

      rows.forEach(function (tr) {
        var idx = parseFloat(tr.dataset.index || '0');
        var g = parseFloat((tr.querySelector('.gfn-gram') && tr.querySelector('.gfn-gram').value) || '0');
        var pct = totalGram > 0 ? (g / totalGram) * 100 : 0;
        var pctIdx = pct * idx;

        var cellPct = tr.querySelector('.gfn-percent');
        var cellPI  = tr.querySelector('.gfn-percent-index');
        if (cellPct) cellPct.textContent = fmt(pct, 2);
        if (cellPI)  cellPI.textContent  = fmt(pctIdx, 1);

        totalPct += pct;
        totalPI  += pctIdx;
      });

      var elTG = document.getElementById('gfn-total-gram');
      var elTP = document.getElementById('gfn-total-percent');
      var elTPI= document.getElementById('gfn-total-percent-index');
      if (elTG)  elTG.textContent  = fmt(totalGram, 2);
      if (elTP)  elTP.textContent  = fmt(totalPct, 2);
      if (elTPI) elTPI.textContent = fmt(totalPI, 1);
    }

    // Input listener untuk kolom GRAM
    document.addEventListener('input', function (e) {
      if (e.target && e.target.classList.contains('gfn-gram')) recalcGFN();
    });

    // Tampilkan modal add otomatis kalau ada error validasi
    @if(session('open_modal'))
      $(function () { $('#modal-greensand').modal('show'); recalcGFN(); });
    @endif

    // ====== Fallback buka modal "Add Data" kalau tombol diklik ======
    document.addEventListener("DOMContentLoaded", function () {
      syncDates(); // set min endDate di awal

      var btn = document.getElementById("btn-add-greensand");
      if (btn && window.jQuery) {
        btn.addEventListener("click", function () {
          setTimeout(function () {
            var el = document.getElementById("modal-greensand");
            if (el) jQuery(el).modal("show");
          }, 30);
        });
      }
    });

    // ====== Modal konfirmasi DELETE (pakai data dari tombol) ======
    $(document).on('click', '.btn-delete-gs', function () {
      var date  = $(this).data('gfn-date');
      var shift = $(this).data('shift');

      $('#delDateText').text(date || '—');
      $('#delShiftText').text(shift || '—');

      $('#delDate').val(date || '');
      $('#delShift').val(shift || '');
    });
  })();
</script>
@endpush
@endsection