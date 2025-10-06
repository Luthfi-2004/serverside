<div class="modal fade" id="modal-confirm" tabindex="-1" role="dialog" aria-labelledby="confirmModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalTitle">Konfirmasi</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="confirmModalDesc">Masukkan password Anda untuk melanjutkan.</p>
        <div class="form-group mb-2">
          <input type="password" id="confirm_password_input" class="form-control" placeholder="Password Anda">
        </div>
        <div id="confirmModalAlert" class="alert alert-danger d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
        <button type="button" id="confirmModalOk" class="btn btn-success">Konfirmasi</button>
      </div>
    </div>
  </div>
</div>
