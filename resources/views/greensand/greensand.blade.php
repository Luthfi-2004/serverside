@extends('layouts.app')
@section('title', 'Green Sand Check')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Green Sand Check</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">SandLab</a></li>
                                <li class="breadcrumb-item active">Green Sand Check</li>
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
                                                    data-provide="datepicker" data-date-format="dd-mm-yyyy"
                                                    data-date-autoclose="true">
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
                                            <label class="form-label mb-1">Search (mix/model)</label>
                                            <div class="input-group">
                                                <input id="keywordInput" type="text" class="form-control"
                                                    placeholder="keyword..." autocomplete="off">
                                            </div>
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
                                <button type="button" class="btn btn-success btn-sm btn-add-gs">
                                    <i class="ri-add-line"></i> Add Data
                                </button>
                            </div>

                            @include('greensand.modal')

                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#mm1" role="tab">MM
                                        1</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mm2" role="tab">MM 2</a>
                                </li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#all" role="tab">All</a>
                                </li>
                            </ul>

                            <div class="tab-content p-3 border border-top-0">
                                <div class="tab-pane fade show active" id="mm1" role="tabpanel">
                                    <table id="dt-mm1" class="table table-bordered w-100 text-center">
                                        @includeWhen(true, 'greensand._thead')
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="tab-pane fade" id="mm2" role="tabpanel">
                                    <table id="dt-mm2" class="table table-bordered w-100 text-center">
                                        @includeWhen(true, 'greensand._thead')
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="tab-pane fade" id="all" role="tabpanel">
                                    <table id="dt-all" class="table table-bordered w-100 text-center">
                                        @includeWhen(true, 'greensand._thead')
                                        <tbody></tbody>
                                        <tfoot>
                                            <tr class="gs-summary-row">
                                                @for ($i = 0; $i < 38; $i++)
                                                    <th></th>
                                                @endfor
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
                        aria-labelledby="confirmDeleteTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content border-0">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="confirmDeleteTitle">Confirm Delete</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p id="confirmDeleteText" class="mb-0">Are you sure you want to delete this data?</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="confirmDeleteYes">Yes, Delete</button>
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
        #dt-all tfoot .gs-summary-row:first-child td {
            border-top: 2px solid #333 !important;
        }

        #dt-all tfoot .gs-summary-row td {
            background: #fff;
            font-size: .95rem;
            text-align: center;
        }

        #dt-all tfoot .gs-summary-row td:first-child {
            text-align: left;
            white-space: nowrap;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function () {
            $('#shiftSelect').select2();
            $('#filterDate').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                orientation: 'bottom'
            });
        });
    </script>
    <script>
        window.greensandRoutes = {
            mm1: "{{ route('greensand.data.mm1') }}",
            mm2: "{{ route('greensand.data.mm2') }}",
            all: "{{ route('greensand.data.all') }}",
            store: "{{ route('greensand.processes.store') }}",
            base: "{{ url('greensand/processes') }}",
            export: "{{ route('greensand.export') }}",
            summary: "{{ route('greensand.summary') }}",
        };
    </script>
    <script src="{{ asset('assets/js/serverside.js') }}" defer></script>
@endpush