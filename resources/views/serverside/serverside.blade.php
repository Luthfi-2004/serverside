@extends('layouts.app')
@section('title', 'Green Sand Check')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <!-- title -->
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Green Sand Check</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">SandLab</a></li>
                                <li class="breadcrumb-item active">Green Sand Check</li>
                            </ol>
                        </div>
                    </div>

                    <!-- filter -->
                    <div class="card mb-3">
                        <div id="filterHeader"
                            class="card-header bg-light d-flex align-items-center justify-content-between">
                            <h5 class="font-size-14 mb-0"><i class="ri-filter-2-line mr-1"></i> Filter Data</h5>
                            <i id="filterIcon" class="ri-subtract-line"></i>
                        </div>

                        <div id="filterCollapse" class="show">
                            <div class="card-body">
                                <div class="row align-items-end">

                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label class="form-label mb-1">Start Date</label>
                                        <input id="startDate" type="text" class="form-control datepicker"
                                            placeholder="Start Date" autocomplete="off">
                                    </div>

                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label class="form-label mb-1">End Date</label>
                                        <input id="endDate" type="text" class="form-control datepicker"
                                            placeholder="End Date" autocomplete="off">
                                    </div>

                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label class="form-label mb-1">Shift</label>
                                        <select id="shiftSelect" class="form-control select2"
                                            data-placeholder="Select shift">
                                            <option value=""></option>
                                            <option value="D">Day</option>
                                            <option value="S">Swing</option>
                                            <option value="N">Night</option>
                                        </select>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 mb-2">
                                        <label class="form-label mb-1">Search (mix/model)</label>
                                        <div class="input-group">
                                            <input id="keywordInput" type="text" class="form-control"
                                                placeholder="keyword..." autocomplete="off">
                                            <div class="input-group-append">
                                                <button id="btnQuickSearch" type="button" class="btn btn-primary btn-sm">
                                                    <i class="ri-search-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-12 mt-2">
                                        <div class="d-flex flex-wrap">
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

                    <!-- table -->
                    <div class="card mb-4">
                        <div class="card-body shadow-lg">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-success btn-sm btn-add-gs">
                                    <i class="ri-add-line"></i> Add Data
                                </button>
                            </div>

                            @include('serverside.modal')

                            <!-- tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#mm1" role="tab">MM
                                        1</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#mm2" role="tab">MM 2</a>
                                </li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#all" role="tab">All</a>
                                </li>
                            </ul>

                            <div class="tab-content p-3 border border-top-0">
                                <!-- mm1 -->
                                <div class="tab-pane fade show active" id="mm1" role="tabpanel">
                                    <table id="dt-mm1" class="table table-bordered w-100 text-center">
                                        @includeWhen(true, 'serverside._thead')
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <!-- mm2 -->
                                <div class="tab-pane fade" id="mm2" role="tabpanel">
                                    <table id="dt-mm2" class="table table-bordered w-100 text-center">
                                        @includeWhen(true, 'serverside._thead')
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <!-- all -->
                                <div class="tab-pane fade" id="all" role="tabpanel">
                                    <table id="dt-all" class="table table-bordered w-100 text-center">
                                        @includeWhen(true, 'serverside._thead')
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- modal -->
                    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
                        aria-labelledby="confirmDeleteTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content border-0">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="confirmDeleteTitle">Confirm Delete</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal"
                                        aria-label="Close"><span aria-hidden="true">×</span></button>
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
        /* style */
        .select2-container--bootstrap4 .select2-selection {
            height: calc(1.5em + .75rem + 2px) !important;
            padding: .375rem .75rem !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
            border: 1px solid #ced4da !important;
            border-radius: .25rem !important;
        }

        .select2-container--bootstrap4 .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0 !important;
            color: #495057 !important;
        }

        .select2-container--bootstrap4 .select2-selection__arrow {
            height: 100% !important;
            right: .75rem !important;
        }

        .select2-container--bootstrap4.select2-container--focus .select2-selection,
        .select2-container--bootstrap4.select2-container--open .select2-selection {
            border-color: #80bdff !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 .25rem rgba(0, 123, 255, .35) !important;
            outline: 0 !important;
        }

        .input-group .select2-container--bootstrap4 .select2-selection {
            height: calc(1.5em + .75rem + 2px) !important;
        }

        .input-group .select2-container--bootstrap4.select2-container--focus .select2-selection,
        .input-group .select2-container--bootstrap4.select2-container--open .select2-selection {
            border-color: #80bdff !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 .25rem rgba(0, 123, 255, .35) !important;
        }

        .datepicker-input,
        input.datepicker,
        input.form-control.datepicker {
            height: calc(1.5em + .75rem + 2px) !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
            border: 1.5px solid #ced4da !important;
            border-radius: .25rem !important;
        }

        input.form-control.datepicker:focus,
        input.datepicker:focus {
            border-color: #80bdff !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 .25rem rgba(0, 123, 255, .35) !important;
            outline: 0 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // routes
        window.serversideRoutes = {
            mm1: "{{ route('serverside.data.mm1') }}",
            mm2: "{{ route('serverside.data.mm2') }}",
            all: "{{ route('serverside.data.all') }}",
            store: "{{ route('serverside.processes.store') }}",
            base: "{{ url('serverside/processes') }}",
            export: "{{ route('greensand.export') }}",
        };
    </script>
    <script src="{{ asset('assets/js/serverside.js') }}" defer></script>
@endpush