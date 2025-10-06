<div class="modal fade" id="modal-user" tabindex="-1" role="dialog" aria-labelledby="modalUserLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="userForm" method="POST" class="modal-content">
      @csrf
      <input type="hidden" name="id" id="id">
      <input type="hidden" name="_method" id="_method" value="POST">

      <div class="modal-header">
        <h5 class="modal-title" id="modalUserLabel">Tambah User</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">
        <div id="formAlert" class="alert alert-danger d-none"></div>

        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" id="username" class="form-control">
          <div class="invalid-feedback" id="err_username"></div>
        </div>

        <div class="form-group">
          <label>Email (opsional)</label>
          <input type="email" name="email" id="email" class="form-control">
          <div class="invalid-feedback" id="err_email"></div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" id="password" class="form-control">
          <div class="invalid-feedback" id="err_password"></div>
        </div>

        <div class="form-group">
          <label>Role</label>
          <select name="role" id="role" class="form-control">
            <option value=""></option>
            <option value="pekerja">Pekerja</option>
            <option value="admin">Admin</option>
          </select>
          <div class="invalid-feedback" id="err_role"></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" id="submitBtn" class="btn btn-success">Submit</button>
      </div>
    </form>
  </div>
</div>