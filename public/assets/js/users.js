/* public/assets/js/users.js */
(function ($) {
  'use strict';

  // --- Guards: make sure required globals exist (set these in your Blade) ---
  // window.usersDataUrl, usersStoreUrl, usersJsonUrl, usersUpdateUrl, usersDestroyUrl

  // CSRF for all AJAX
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // -------- Helpers --------
  function flash(text, type) {
    const $area = $('#flashArea');
    if (!$area.length) return;
    const klass = type === 'success' ? 'alert-success' : 'alert-danger';
    $area.html(`<div class="alert ${klass} mb-2">${text}</div>`);
    setTimeout(() => {
      $area.find('.alert').fadeOut(250, function () { $(this).remove(); });
    }, 2200);
  }

  function resetForm() {
    const $f = $('#userForm');
    $f[0]?.reset();
    $('#id').val('');
    $('#_method').val('POST');
    clearErrors();
    $('#formAlert').addClass('d-none').text('');
    // select2
    $('#role').prop('disabled', false);
    $('#role').val('').trigger('change');
  }

  function clearErrors() {
    $('#userForm .form-control').removeClass('is-invalid');
    $('#err_username,#err_email,#err_password,#err_role').text('');
  }

  function applyErrors(errs) {
    Object.entries(errs || {}).forEach(([field, messages]) => {
      const msg = Array.isArray(messages) ? messages[0] : String(messages);
      const $input = $('#' + field);
      const $err   = $('#err_' + field);
      if ($input.length) $input.addClass('is-invalid');
      if ($err.length) $err.text(msg);
    });
  }

  function showFormAlert(msg) {
    $('#formAlert').removeClass('d-none').text(msg);
  }

  // Replace :id placeholders in routes
  function routeWithId(tpl, id) {
    return String(tpl).replace(':id', id);
  }

  // -------- Modal + Select2 --------
  function initSelect2() {
    $('#role').select2({
      width: '100%',
      placeholder: 'Pilih Role',
      dropdownParent: $('#modal-user'),
    });
  }

  // -------- DataTable (server-side) --------
  let usersTable = null;
  function initDataTable() {
    if (!window.usersDataUrl) {
      console.error('usersDataUrl is missing. Define it in Blade.');
      return;
    }
    if ($.fn.dataTable.isDataTable('#usersTable')) {
      usersTable = $('#usersTable').DataTable();
      return;
    }

    usersTable = $('#usersTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: window.usersDataUrl,
        type: 'GET'
      },
      columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false },
        { data: 'username', name: 'username' },
        { data: 'email', name: 'email' },
        { data: 'role', name: 'role' }
      ],
      order: [[1, 'asc']],
      deferRender: true,
      autoWidth: false,
      scrollX: true,
      orderCellsTop: true,
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      drawCallback: function () {
        // optional: fix header align on redraw
        this.api().columns.adjust();
      }
    });

    // Fix header misalignment after fonts/icons load
    setTimeout(() => usersTable.columns.adjust(), 150);
    $(window).on('resize', function () {
      if (usersTable) usersTable.columns.adjust();
    });
  }

  // -------- Events --------
  $(function () {
    initSelect2();
    initDataTable();

    // ADD
    $(document).on('click', '.btn-add', function () {
      resetForm();
      $('#modalUserLabel').text('Tambah User');
      $('#userForm').attr('action', window.usersStoreUrl || '#');
      $('#_method').val('POST');
      $('#modal-user').modal('show');
    });

    // EDIT
    $(document).on('click', '.btn-edit', function () {
      const id = $(this).data('id');
      if (!id) return;

      resetForm();

      const url = routeWithId(window.usersJsonUrl || '', id);
      $.get(url)
        .done(function (res) {
          const u = res && res.user ? res.user : res;
          const canRole = !!(res.meta && res.meta.can_edit_role);

          $('#modalUserLabel').text('Edit User');
          $('#userForm').attr('action', routeWithId(window.usersUpdateUrl || '', u.id));
          $('#_method').val('PUT');

          $('#id').val(u.id);
          $('#username').val(u.username || '');
          $('#email').val(u.email || '');
          $('#password').val('');

          $('#role').val(u.role || '').trigger('change');
          $('#role').prop('disabled', !canRole);
          $('#err_role').text(canRole ? '' : 'Role tidak bisa diubah untuk akun ini.');

          $('#modal-user').modal('show');
        })
        .fail(function () {
          flash('Gagal mengambil data user.', 'danger');
        });
    });

    // DELETE
    $(document).on('click', '.btn-delete', function () {
      const id = $(this).data('id');
      if (!id) return;
      if ($(this).is(':disabled')) return;

      if (!confirm('Hapus user ini?')) return;

      $.ajax({
        url: routeWithId(window.usersDestroyUrl || '', id),
        method: 'POST',
        data: { _method: 'DELETE' }
      })
        .done(function () {
          if (usersTable) usersTable.ajax.reload(null, false);
          flash('User dihapus.', 'success');
        })
        .fail(function (xhr) {
          const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal hapus user.';
          flash(msg, 'danger');
        });
    });

    // SUBMIT (Create/Update)
    $('#userForm').on('submit', function (e) {
      e.preventDefault();
      clearErrors();
      $('#formAlert').addClass('d-none').text('');

      const $form = $(this);
      const url = $form.attr('action');
      const data = $form.serialize();
      const $btn = $('#submitBtn');

      $btn.prop('disabled', true).text('Saving...');

      $.ajax({
        url: url,
        method: 'POST', // spoofed by _method for PUT
        data: data
      })
        .done(function () {
          $('#modal-user').modal('hide');
          if (usersTable) usersTable.ajax.reload(null, false);
          flash('Data berhasil disimpan.', 'success');
        })
        .fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            applyErrors(xhr.responseJSON.errors);
          } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
            showFormAlert(xhr.responseJSON.message);
          } else if (xhr.status === 419) {
            showFormAlert('Sesi kadaluarsa (419). Silakan refresh halaman.');
          } else {
            showFormAlert('Kesalahan saat menyimpan data.');
            console.error(xhr.responseText || xhr);
          }
        })
        .always(function () {
          $btn.prop('disabled', false).text('Submit');
        });
    });

    // When modal hidden, reset select2 disabled state for next open (safety)
    $('#modal-user').on('hidden.bs.modal', function () {
      $('#role').prop('disabled', false);
    });
  });

})(jQuery);
