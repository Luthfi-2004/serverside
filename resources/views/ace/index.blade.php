@extends('layouts.app')
@section('title', 'ACE LINE')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Daily Check</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">ACE LINE</a></li>
                                <li class="breadcrumb-item active">Daily Check</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div id="filterHeader"
                            class="card-header bg-light d-flex align-items-center justify-content-between">
                            <h5 class="font-size-14 mb-0"><i class="ri-filter-2-line mr-1"></i> Filter Data</h5>
                            <i id="filterIcon" class="ri-subtract-line"></i>
                        </div>

                        <div id="filterCollapse" class="show">
                            <div class="card-body">
                                <div class="row align-items-end">
                                    <div class="col-xl-4 col-lg-4">
                                        <div class="form-group mb-2">
                                            <label>Process Date</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="filterDate"
                                                    data-provide="datepicker" data-date-format="yyyy-mm-dd"
                                                    data-date-autoclose="true" autocomplete="off" placeholder="YYYY-MM-DD">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-4 col-lg-4">
                                        <div class="form-group mb-2">
                                            <label class="form-label mb-1">Shift</label>
                                            <select id="shiftSelect" class="form-control select2"
                                                data-placeholder="Select shift" style="width:100%;">
                                                <option value=""></option>
                                                <option value="D">D</option>
                                                <option value="S">S</option>
                                                <option value="N">N</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xl-4 col-lg-4">
                                        <div class="form-group mb-2">
                                            <label class="form-label mb-1">Type Product</label>
                                            <select id="productSelect" class="form-control select2"
                                                data-placeholder="All type" style="width:100%;">
                                                <option value=""></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap mt-1">
                                            <button id="btnSearch" type="button" class="btn btn-primary btn-sm mr-2 mb-2">
                                                <i class="ri-search-line mr-1"></i> Search
                                            </button>
                                            <button id="btnRefresh" type="button"
                                                class="btn btn-outline-primary btn-sm mr-2 mb-2">
                                                <i class="ri-refresh-line mr-1"></i> Refresh Filter
                                            </button>
                                            <button id="btnExport" type="button"
                                                class="btn btn-outline-success btn-sm mb-2">
                                                <i class="ri-file-excel-2-line mr-1"></i> Export Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body shadow-lg">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal"
                                    data-target="#modal-ace">
                                    <i class="ri-add-line"></i> Add Data
                                </button>
                            </div>

                            @include('ace.modal')

                            <div class="tab-content p-0 border-0">
                                <div class="tab-pane fade show active" id="ace" role="tabpanel">
                                    <table id="dt-ace" class="table table-bordered w-100 text-center">
                                        @include('ace._thead')
                                        <tbody></tbody>

                                        {{-- FOOTER dibangun dinamis via JS supaya jumlah kolom selalu pas --}}
                                        <tfoot class="d-none" id="ace-foot"></tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Confirm Delete --}}
                        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
                            aria-labelledby="confirmDeleteTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content border-0">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title" id="confirmDeleteTitle">Confirm Delete</h5>
                                        <button type="button" class="close text-white" data-dismiss="modal"
                                            aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p id="confirmDeleteText" class="mb-0">Are you sure you want to delete this data?
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-danger" id="confirmDeleteYes">Yes,
                                            Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
      /* HEADER ACE */
#dt-ace thead th {
  white-space: nowrap;
  text-align: center;
  vertical-align: middle;
  min-width: 120px;
}

/* FOOTER ACE (pakai class existing: .ace-summary-row) */
#dt-ace tfoot .ace-summary-row:first-child td {
  border-top: 2px solid #333 !important;   /* samain tebalnya dgn greensand */
}

#dt-ace tfoot .ace-summary-row td {
  background: #fff;
  font-size: .95rem;        /* samain ukuran font */
  line-height: 1.25;        /* samain line-height */
  padding: .5rem .75rem;    /* samain padding */
  height: auto !important;  /* override tinggi fixed yg lama */
  text-align: center;
  vertical-align: middle;
  border-top: 1px solid #dee2e6 !important;
}

/* warna judge */
#dt-ace tfoot td.j-ok { color: #2e7d32; font-weight: 600; }
#dt-ace tfoot td.j-ng { color: #c62828; font-weight: 600; }

    </style>
@endpush

@push('scripts')
    <script>
        $(function () {
            $('#shiftSelect,#productSelect').select2();
            $('#filterDate').datepicker({ format: 'yyyy-mm-dd', autoclose: true, orientation: 'bottom' });
            $('#filterHeader').on('click', function () {
                $('#filterCollapse').slideToggle(120);
                $('#filterIcon').toggleClass('ri-subtract-line ri-add-line');
            });
        });
    </script>

    <script>
        window.aceRoutes = {
            data: "{{ route('ace.data') }}",
            store: "{{ route('ace.store') }}",
            base: "{{ url('ace') }}",
            export: "{{ route('ace.export') }}",
            summary: "{{ route('ace.summary') }}",
        };
    </script>

    <script src="{{ asset('assets/js/ace.js') }}" defer></script>
@endpush