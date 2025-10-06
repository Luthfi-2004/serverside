/* public/assets/js/users.js */
$(function () {
    let usersTable = null;
    let pending = {
        mode: null, // 'create' | 'edit' | 'delete'
        id: null, // user id
        formData: null, // serialized form data for create/edit
        origin: null, // 'edit' | null (darimana kita membuka confirm)
        confirmAction: null, // 'ok' | 'cancel' | null
    };

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    usersTable = $("#usersTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: window.usersDataUrl,
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100],
        ],
        order: [[1, "asc"]],
        columns: [
            { data: "action", orderable: false, searchable: false },
            { data: "username", name: "username" },
            { data: "email", name: "email" },
            { data: "role", name: "role" },
        ],
        drawCallback: function () {
            bindRowButtons();
        },
    });

    function flash(text, type = "success") {
        const $area = $("#flashArea");
        if (!$area.length) return;
        const klass = type === "success" ? "alert-success" : "alert-danger";
        $area.html(`<div class="alert ${klass} mb-0 py-2">${text}</div>`);
        setTimeout(
            () =>
                $area.find(".alert").fadeOut(300, function () {
                    $(this).remove();
                }),
            2200
        );
    }

    function clearFormErrors() {
        $("#userForm .form-control").removeClass("is-invalid");
        $("#err_username,#err_email,#err_password,#err_role").text("");
        $("#formAlert").addClass("d-none").text("");
    }

    function applyFormErrors(errs) {
        Object.entries(errs || {}).forEach(([field, messages]) => {
            const msg = Array.isArray(messages)
                ? messages[0]
                : String(messages);
            const $inp = $("#" + field);
            if ($inp.length) $inp.addClass("is-invalid");
            const $err = $("#err_" + field);
            if ($err.length) $err.text(msg);
        });
    }

    function fillForm(user, meta) {
        $("#id").val(user.id);
        $("#username").val(user.username || "");
        $("#email").val(user.email || "");
        $("#password").val("");
        $("#role")
            .val(user.role || "")
            .trigger("change");

        if (meta && meta.can_edit_role === false) {
            $("#role").prop("disabled", true);
        } else {
            $("#role").prop("disabled", false);
        }
    }

    function resetForm() {
        $("#userForm")[0].reset();
        $("#id").val("");
        $("#_method").val("POST");
        clearFormErrors();
        $("#role").val("").trigger("change");
    }

    function openEdit(id) {
        $.get(window.userShowUrl(id))
            .done((res) => {
                resetForm();
                $("#modalUserLabel").text("Edit User");
                $("#_method").val("PUT");
                fillForm(res.user, res.meta || {});
                $("#modal-user").modal("show");
            })
            .fail((xhr) => {
                flash("Gagal memuat data user.", "danger");
                console.error(xhr.responseText);
            });
    }

    function openConfirm(options) {
        const { title, desc, onOk } = options;

        $("#confirmModalTitle").text(title || "Konfirmasi");
        $("#confirmModalDesc").text(
            desc || "Masukkan password Anda untuk melanjutkan."
        );
        $("#confirm_password_input").val("");
        $("#confirmModalAlert").addClass("d-none").text("");
        pending.confirmAction = null;

        // tentukan asal (origin)
        const fromEdit = $("#modal-user").hasClass("show");
        pending.origin = fromEdit ? "edit" : null;

        // pasang handler untuk tombol close (X)
        $("#confirmModalClose")
            .off("click")
            .on("click", function () {
                pending.confirmAction = "cancel";
                $("#modal-confirm").modal("hide");
            });

        // kalau dari edit, tutup edit dulu baru buka confirm
        if (fromEdit) {
            $("#modal-user").one("hidden.bs.modal", function () {
                $("#modal-confirm").modal("show");
            });
            $("#modal-user").modal("hide");
        } else {
            $("#modal-confirm").modal("show");
        }
        
        // handler tombol OK
        $("#confirmModalOk")
            .off("click")
            .on("click", function () {
                const pwd = $("#confirm_password_input").val() || "";
                if (!pwd) {
                    $("#confirmModalAlert")
                        .removeClass("d-none")
                        .text("Password wajib diisi.");
                    return;
                }
                pending.confirmAction = "ok";
                $("#confirmModalOk")
                    .prop("disabled", true)
                    .text("Processing...");
                onOk && onOk(pwd);
            });

        // saat modal confirm ditutup
        $("#modal-confirm")
            .off("hidden.bs.modal")
            .on("hidden.bs.modal", function () {
                $("#confirmModalOk").prop("disabled", false).text("Konfirmasi");
                $("#confirm_password_input").val("");
                $("#confirmModalAlert").addClass("d-none").text("");

                // HANYA balik ke modal edit jika user CANCEL konfirmasi
                if (
                    pending.confirmAction === "cancel" &&
                    pending.origin === "edit"
                ) {
                    $("#modal-user").modal("show");
                }

                // reset origin & action
                pending.origin = null;
                pending.confirmAction = null;
            });
    }

    // tombol Add
    $(".btn-add").on("click", function () {
        pending.mode = "create";
        pending.id = null;
        pending.formData = null;
        resetForm();
        $("#modalUserLabel").text("Tambah User");
        $("#_method").val("POST");
        $("#modal-user").modal("show");
    });

    // bind tombol edit/hapus pada baris datatable
    function bindRowButtons() {
        $(".btn-edit")
            .off("click")
            .on("click", function () {
                const id = $(this).data("id");
                pending.mode = "edit";
                pending.id = id;
                openEdit(id);
            });

        $(".btn-delete")
            .off("click")
            .on("click", function () {
                const id = $(this).data("id");
                pending.mode = "delete";
                pending.id = id;

                openConfirm({
                    title: "Konfirmasi Hapus",
                    desc: "Masukkan password Anda untuk menghapus user ini.",
                    onOk: function (pwd) {
                        $.ajax({
                            url: window.userDestroyUrl(id),
                            method: "POST",
                            data: { _method: "DELETE", confirm_password: pwd },
                            success: function () {
                                // sukses: cukup tutup confirm, reload tabel, flash
                                $("#modal-confirm").modal("hide");
                                usersTable.ajax.reload(null, false);
                                flash("User berhasil dihapus.", "success");
                            },
                            error: function (xhr) {
                                // tetap DI MODAL KONFIRMASI, jangan balik ke edit
                                $("#confirmModalOk")
                                    .prop("disabled", false)
                                    .text("Konfirmasi");

                                if (xhr.status === 422 && xhr.responseJSON) {
                                    const msg =
                                        xhr.responseJSON?.errors
                                            ?.confirm_password?.[0] ||
                                        xhr.responseJSON.message ||
                                        "Validasi gagal.";
                                    $("#confirmModalAlert")
                                        .removeClass("d-none")
                                        .text(msg);
                                    return;
                                }
                                if (xhr.status === 419) {
                                    $("#confirmModalAlert")
                                        .removeClass("d-none")
                                        .text(
                                            "Sesi kadaluarsa (419). Silakan refresh halaman."
                                        );
                                    return;
                                }
                                $("#confirmModalAlert")
                                    .removeClass("d-none")
                                    .text("Gagal menghapus user.");
                                console.error(xhr.responseText);
                            },
                        });
                    },
                });
            });
    }

    // submit create/update -> minta konfirmasi password admin
    $("#userForm").on("submit", function (e) {
        e.preventDefault();
        clearFormErrors();

        const id = $("#id").val();
        const method = $("#_method").val() || "POST";
        let url =
            method === "PUT" ? window.userUpdateUrl(id) : window.usersStoreUrl;

        pending.mode = method === "PUT" ? "edit" : "create";
        pending.id = method === "PUT" ? id : null;
        pending.formData = $(this).serializeArray();

        openConfirm({
            title: method === "PUT" ? "Konfirmasi Update" : "Konfirmasi Tambah",
            desc: "Masukkan password Anda untuk melanjutkan.",
            onOk: function (pwd) {
                const payload = [
                    ...pending.formData,
                    { name: "confirm_password", value: pwd },
                ];
                $.ajax({
                    url: url,
                    method: "POST",
                    data: $.param(payload),
                    success: function () {
                        // sukses: tutup confirm & edit jika masih terbuka, reload table, flash
                        $("#modal-confirm").modal("hide");
                        $("#modal-user").modal("hide");
                        usersTable.ajax.reload(null, false);
                        flash(
                            method === "PUT"
                                ? "User berhasil diperbarui."
                                : "User berhasil dibuat.",
                            "success"
                        );
                    },
                    error: function (xhr) {
                        // tetap DI MODAL KONFIRMASI, jangan balik ke modal edit
                        $("#confirmModalOk")
                            .prop("disabled", false)
                            .text("Konfirmasi");

                        if (xhr.status === 422 && xhr.responseJSON) {
                            const errs = xhr.responseJSON.errors || {};
                            if (errs.confirm_password) {
                                $("#confirmModalAlert")
                                    .removeClass("d-none")
                                    .text(
                                        errs.confirm_password[0] ||
                                            "Password konfirmasi salah."
                                    );
                                return;
                            }
                            // tampilkan pesan validasi pertama supaya ringkas
                            const firstMsg =
                                Object.values(errs)[0]?.[0] ||
                                "Validasi form gagal.";
                            $("#confirmModalAlert")
                                .removeClass("d-none")
                                .text(firstMsg);
                            return;
                        }
                        if (xhr.status === 419) {
                            $("#confirmModalAlert")
                                .removeClass("d-none")
                                .text(
                                    "Sesi kadaluarsa (419). Silakan refresh halaman."
                                );
                            return;
                        }
                        $("#confirmModalAlert")
                            .removeClass("d-none")
                            .text("Kesalahan saat menyimpan data.");
                        console.error(xhr.responseText);
                    },
                });
            },
        });
    });

    // select2
    if (typeof $.fn.select2 === "function") {
        $("#role").select2({
            width: "100%",
            placeholder: "Pilih Role",
            dropdownParent: $("#modal-user"),
        });
    }
});
