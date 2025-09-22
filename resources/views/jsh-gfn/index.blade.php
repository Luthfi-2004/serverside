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
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Greensand</a></li>
                                <li class="breadcrumb-item active">Green Sand GFN</li>
                            </ol>
                        </div>
                    </div>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger mb-2">{{ $errors->first() }}</div>
                    @endif

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
                                    <div class="col-xl-6 col-lg-4">
                                        <div class="form-group mb-2">
                                            <label for="fDate" class="form-label mb-1">Date</label>
                                            <div class="input-group">
                                                <input id="fDate" type="text" name="date" class="form-control gs-input"
                                                    value="{{ $filters['date'] ?? '' }}" autocomplete="off"
                                                    placeholder="YYYY-MM-DD" data-provide="datepicker"
                                                    data-date-format="yyyy-mm-dd" data-date-autoclose="true">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-4 mb-2">
                                        <label class="form-label mb-1">Shift</label>
                                        <select class="form-control select2" name="shift"
                                            data-placeholder="-- Select Shift --" autocomplete="off">
                                            <option value=""></option>
                                            <option value="D" @selected(($filters['shift'] ?? '') === 'D')>D</option>
                                            <option value="S" @selected(($filters['shift'] ?? '') === 'S')>S</option>
                                            <option value="N" @selected(($filters['shift'] ?? '') === 'N')>N</option>
                                        </select>
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
                                                href="{{ route('jshgfn.export', request()->only('date', 'shift')) }}">
                                                <i class="ri-file-excel-2-line mr-1"></i> Export Excel
                                            </a>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>


                    <div class="card shadow-sm">
                        <div class="card-body">

                            {{-- Tombol aksi --}}
                            <div class="d-flex align-items-center flex-wrap">
                                <button id="btn-add-greensand" type="button"
                                    class="btn btn-success btn-sm btn-add-gs mr-2 mb-2" data-toggle="modal"
                                    data-target="#modal-greensand">
                                    <i class="ri-add-line"></i> Add Data
                                </button>

                                @if(!empty($displayRecap))
                                    <button type="button" class="btn btn-outline-danger btn-sm mb-2 btn-delete-gs"
                                        data-toggle="modal" data-target="#confirmDeleteModal"
                                        data-gfn-date="{{ $displayRecap['gfn_date'] }}"
                                        data-shift="{{ $displayRecap['shift'] }}">
                                        <i class="fas fa-trash"></i> Delete Data
                                    </button>
                                @endif
                            </div>

                            {{-- Tabel detail mesh --}}
                            <div class="table-responsive">
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
                                                <td colspan="6" class="text-center text-muted">Belum ada data dalam 24 jam
                                                    terakhir.</td>
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
                                                    {{ number_format($displayRecap['total_percentage_index'] ?? 0, 1, ',', '.') }}
                                                </th>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            {{-- === Nilai GFN + Line Chart sejajar (tinggi sama + text center) === --}}
                            <div class="row mt-4">
                                {{-- Kiri: Tabel Nilai GFN --}}
                                <div class="col-lg-6 d-flex flex-column">
                                    <div class="table-responsive flex-grow-1" style="height:300px; overflow:auto;">
                                        <table class="table table-bordered table-striped mb-0 w-100 h-100 text-center">
                                            <thead class="bg-dark text-white">
                                                <tr>
                                                    <td>Nilai GFN (Σ %Index / 100)</td>
                                                    <td>
                                                        <b>{{ isset($displayRecap) ? number_format($displayRecap['nilai_gfn'], 2, ',', '.') : '-' }}</b>
                                                    </td>
                                                    <th>JUDGE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>% MESH 140 (STD : 3.5 ~ 8.0 %)</td>
                                                    <td>
                                                        <b>{{ isset($displayRecap) ? number_format($displayRecap['mesh_total140'], 2, ',', '.') : '-' }}</b>
                                                    </td>
                                                    <td>{{ $displayRecap['judge_mesh_140'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Σ MESH 50, 70 & 100 (Min 64 %)</td>
                                                    <td>
                                                        <b>{{ isset($displayRecap) ? number_format($displayRecap['mesh_total70'], 2, ',', '.') : '-' }}</b>
                                                    </td>
                                                    <td>{{ $displayRecap['judge_mesh_70'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td>% MESH 280 + PAN (STD : 0.00 ~ 1.40 %)</td>
                                                    <td>
                                                        <b>{{ isset($displayRecap) ? number_format($displayRecap['meshpan'], 2, ',', '.') : '-' }}</b>
                                                    </td>
                                                    <td>{{ $displayRecap['judge_meshpan'] ?? '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-body d-flex flex-column">
                                            <h4 class="card-title mb-3 text-center">Grafik GFN Green Sand</h4>
                                            <div id="gfn-line" class="flot-charts flot-charts-height"
                                                style="height:300px; flex:1 1 auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                    <button type="button" class="btn btn-light mr-2" data-dismiss="modal">Cancel</button>

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
        @php
            $__rows = [];
            if (!empty($displayRows)) {
                foreach ($displayRows as $r) {
                    $__rows[] = [
                        'mesh' => $r->mesh,
                        'percentage' => round(($r->percentage ?? 0), 2),
                        'percentage_index' => round(($r->percentage_index ?? 0), 1),
                        'index' => ($r->index ?? 0),
                    ];
                }
            }
            $__recap = $displayRecap ?? null;
        @endphp

        <script>
            window.gfnChartData = {
                rows: {!! json_encode($__rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                recap: {!! json_encode($__recap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            };
        </script>

        @if(session('open_modal'))
            <script>window.openModalGFN = true;</script>
        @endif
        <script src="{{ asset('assets/js/jshgfn.js') }}"></script>
    @endpush

@endsection