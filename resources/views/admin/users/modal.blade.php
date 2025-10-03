<div class="modal fade" id="modal-user" tabindex="-1" role="dialog" aria-labelledby="modalUserLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="{{ route('admin.users.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="modalUserLabel">Tambah User</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">

        @if ($errors->any())
          <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="form-group">
          <label for="name">Name (opsional)</label>
          <input type="text" name="name" id="name" class="form-control" placeholder="Nama lengkap">
        </div>

        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="email">Email (opsional)</label>
          <input type="email" name="email" id="email" class="form-control">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="role">Role</label>
          <select name="role" id="role" class="form-control" required>
            <option></option>
            <option value="pekerja">Pekerja</option>
            <option value="admin">Admin</option>
          </select>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary mr-2" data-dismiss="modal"><i class="ri-close-line me-1"></i> Cancel</button>
        <button type="submit" class="btn btn-success"><i class="ri-checkbox-circle-line me-1"></i> Submit</button>
    </form>
  </div>
</div>
