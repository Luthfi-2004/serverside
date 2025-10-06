@extends('layouts.app')

@push('styles')
    <base href="{{ url('/') }}/">
    <style>
        /* keep first col neat */
        #usersTable th:first-child, #usersTable td:first-child { white-space: nowrap; }
        /* help avoid header jitter */
        #usersTable thead th { vertical-align: middle; }
    </style>
@endpush

@section('content')
<div class="page-content">
  <div class="container-fluid">
    <div class="row"><div class="col-12">

      <div class="page-title-box d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Kelola User</h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Kelola User</li>
          </ol>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">

          <div class="mb-3 d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-success btn-sm btn-add">
              <i class="ri-add-line"></i> Add Data
            </button>
            <div id="flashArea"></div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered" id="usersTable" style="width:100%;">
              <thead class="table-dark">
                <tr>
                  <th class="text-center align-middle" style="min-width:120px;">Action</th>
                  <th class="text-center align-middle" style="min-width:150px;">Username</th>
                  <th class="text-center align-middle" style="min-width:200px;">Email</th>
                  <th class="text-center align-middle" style="min-width:120px;">Role</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>

      @include('admin.users.modal')

    </div></div>
  </div>
</div>
@endsection

@push('scripts')
    <script>
      window.usersDataUrl    = "{{ route('admin.users.data') }}";
      window.usersStoreUrl   = "{{ route('admin.users.store') }}";
      window.usersJsonUrl    = "{{ route('admin.users.json', ':id') }}";
      window.usersUpdateUrl  = "{{ route('admin.users.update', ':id') }}";
      window.usersDestroyUrl = "{{ route('admin.users.destroy', ':id') }}";
    </script>

    <script src="{{ asset('assets/js/users.js') }}"></script>
@endpush
