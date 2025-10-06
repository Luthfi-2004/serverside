/* global $, usersDataUrl, usersStoreUrl, userShowUrl, userUpdateUrl, userDestroyUrl */

let usersTable = null;

$(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      'X-Requested-With': 'XMLHttpRequest'
    }
  });

  usersTable = $('#usersTable').DataTable({
    processing: true,
    serverSide: true,
    deferRender: true,
    ajax: { url: usersDataUrl, type: 'GET' },
    order: [[1, 'asc']],
    columns: [
      { data: 'action', orderable: false, searchable: false },
      { data: 'username', name: 'username' },
      { data: 'email',    name: 'email' },
      { data: 'role',     name: 'role' }
    ]
  });

  $('.btn-add').on('click', function () {
    resetForm();
    $('#modalUserLabel').text('Tambah User');
    $('#pwdHint').text('(wajib saat tambah)');
    $('#userForm').attr('action', usersStoreUrl);
    $('#_method').val('POST');
    $('#modal-user').modal('show');
  });

  $(document).on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    resetForm();
    $('#modalUserLabel').text('Edit User');
    $('#pwdHint').text('(kosongkan jika tidak ganti)');
    $('#userForm').attr('action', userUpdateUrl(id));
    $('#_method').val('PUT');

    $.get(userShowUrl(id))
      .done(u => {
        $('#id').val(u.id);
        $('#username').val(u.username);
        $('#email').val(u.email || '');
        $('#role').val(u.role).trigger('change');
        $('#modal-user').modal('show');
      })
      .fail(xhr => {
        flash('Gagal mengambil data.', 'danger');
        console.error('GET json error:', xhr.status, xhr.responseText);
      });
  });

  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    if (!confirm('Hapus user ini?')) return;

    $.post(userDestroyUrl(id), { _method: 'DELETE' })
      .done(() => {
        usersTable.ajax.reload(null, false);
        flash('User dihapus.', 'success');
      })
      .fail(xhr => {
        const msg = readServerMessage(xhr) || 'Gagal menghapus user.';
        flash(msg, 'danger');
        console.error('DELETE error:', xhr.status, xhr.responseText);
      });
  });

  $('#userForm').on('submit', function (e) {
    e.preventDefault();
    clearErrors();

    const $form = $(this);
    const url   = $form.attr('action');
    const data  = $form.serialize();
    const $btn  = $('#submitBtn');

    $btn.prop('disabled', true).text('Saving...');

    $.post(url, data)
      .done(() => {
        $('#modal-user').modal('hide');
        usersTable.ajax.reload(null, false);
        flash('Data berhasil disimpan.', 'success');
      })
      .fail(xhr => {
        // tampilkan pesan server yang sebenarnya
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          applyErrors(xhr.responseJSON.errors);
        } else {
          const msg = readServerMessage(xhr) || 'Kesalahan saat menyimpan data.';
          showFormAlert(msg);
        }
        console.error('POST save error:', xhr.status, xhr.responseText);
      })
      .always(() => {
        $btn.prop('disabled', false).text('Submit');
      });
  });

  function readServerMessage(xhr) {
    // 302 -> mungkin redirect ke login/forbidden (HTML)
    if (xhr.status === 0) return 'Request diblokir (network/CORS).';
    if (xhr.status === 419) return 'Sesi kadaluarsa (419). Silakan refresh halaman.';
    if (xhr.status === 403) return 'Tidak memiliki akses (403).';
    if (xhr.responseJSON && xhr.responseJSON.message) return xhr.responseJSON.message;
    if (xhr.responseText && xhr.responseText.startsWith('<')) return 'Server mengembalikan HTML (kemungkinan redirect).';
    return null;
  }

  function resetForm() {
    const f = $('#userForm')[0];
    if (f) f.reset();
    $('#id').val('');
    $('#_method').val('POST');
    $('#role').val('').trigger('change');
    clearErrors();
    $('#formAlert').addClass('d-none').text('');
  }

  function clearErrors() {
    $('#userForm .form-control').removeClass('is-invalid');
    $('#err_username,#err_email,#err_password,#err_role').text('');
  }

  function applyErrors(errs) {
    Object.entries(errs).forEach(([field, messages]) => {
      const msg = Array.isArray(messages) ? messages[0] : messages;
      const $input = $('#' + field);
      const $err = $('#err_' + field);
      if ($input.length) $input.addClass('is-invalid');
      if ($err.length) $err.text(msg);
    });
  }

  function showFormAlert(msg) {
    $('#formAlert').removeClass('d-none').text(msg);
  }

  window.flash = function (text, type) {
    const $area = $('#flashArea');
    if (!$area.length) return;
    const klass = type === 'success' ? 'alert-success' : 'alert-danger';
    $area.html('<div class="alert '+klass+'">'+text+'</div>');
    setTimeout(() => $area.find('.alert').fadeOut(300, function(){ $(this).remove(); }), 2500);
  };
});
