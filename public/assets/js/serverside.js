// serverside.js
$(function () {
    // guard
    if (!window.serversideRoutes) {
        console.error(
            "serversideRoutes tidak ditemukan. Pastikan Blade sudah @push('scripts') untuk inject routes."
        );
        return;
    }

    // csrf
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    // collapse
    $("#filterHeader")
        .off("click")
        .on("click", function () {
            $("#filterCollapse").stop(true, true).slideToggle(180);
            $("#filterIcon").toggleClass("ri-subtract-line ri-add-line");
        });

    // helpers
    function resetGsForm() {
        $("#gsForm")[0]?.reset();
        $("#gs_id").val("");
        $("#gs_mode").val("create");
        $("#gsModalMode").text("Add");

        // mm (modal)
        $('input[name="mm"][value="1"]').prop("checked", true);
        $("#mm1_btn").addClass("active");
        $("#mm2_btn").removeClass("active");
        clearErrors();
    }

    function pickTime(val) {
        if (!val) return "";
        const m = String(val).match(/T?(\d{2}:\d{2})(?::\d{2})?/);
        return m ? m[1] : String(val);
    }

    // errors
    function clearErrors() {
        $("#gsForm .form-control, #gsForm .custom-select").removeClass(
            "is-invalid"
        );
        $("#mm1_btn,#mm2_btn").removeClass("is-invalid");
        $("#gsForm .invalid-feedback").text("").hide();
        const $alert = $("#gsFormAlert");
        if ($alert.length) $alert.addClass("d-none").text("");
    }

    function applyErrors(errs) {
        const map = {
            mm: {
                type: "group",
                target: "#mm_error",
                groupBtns: ["#mm1_btn", "#mm2_btn"],
            },
            shift: { type: "input", target: "#shift_error", control: "#shift" },
            mix_ke: {
                type: "input",
                target: "#mix_ke_error",
                control: "#mix_ke",
            },
            mix_start: {
                type: "input",
                target: "#mix_start_error",
                control: "#mix_start",
            },
            mix_finish: {
                type: "input",
                target: "#mix_finish_error",
                control: "#mix_finish",
            },
            rs_time: {
                type: "input",
                target: "#rs_time_error",
                control: "#rs_time",
            },
        };
        let general = [];

        Object.entries(errs || {}).forEach(([key, messages]) => {
            const msg = Array.isArray(messages)
                ? messages.join(" ")
                : String(messages);
            const m = map[key];
            if (m) {
                if (m.type === "input" && m.control) {
                    $(m.control).addClass("is-invalid");
                    if ($(m.target).length) $(m.target).text(msg).show();
                } else if (m.type === "group" && m.groupBtns) {
                    m.groupBtns.forEach((sel) => $(sel).addClass("is-invalid"));
                    if ($(m.target).length) $(m.target).text(msg).show();
                }
            } else {
                general.push(msg);
            }
        });

        if (general.length) {
            const $alert = $("#gsFormAlert");
            if ($alert.length) {
                $alert.removeClass("d-none").text(general.join(" "));
            } else {
                console.warn("Validation:", general.join(" "));
            }
        }
    }

    // fill
    function fillGsForm(data) {
        $("#gs_id").val(data.id);
        $("#gs_mode").val("edit");
        $("#gsModalMode").text("Edit");

        const mmVal = data.mm === "MM2" ? "2" : "1";
        $('input[name="mm"][value="' + mmVal + '"]').prop("checked", true);
        $("#mm1_btn,#mm2_btn").removeClass("active");
        (mmVal === "1" ? $("#mm1_btn") : $("#mm2_btn")).addClass("active");

        $("#shift").val(data.shift || "");
        $("#mix_ke").val(data.mix_ke || "");
        $("#mix_start").val(pickTime(data.mix_start));
        $("#mix_finish").val(pickTime(data.mix_finish));
        $("#rs_time").val(pickTime(data.rs_time)); // FIX

        [
            "mm_p",
            "mm_c",
            "mm_gt",
            "mm_cb_mm",
            "mm_cb_lab",
            "mm_m",
            "mm_bakunetsu",
            "mm_ac",
            "mm_tc",
            "mm_vsd",
            "mm_ig",
            "mm_cb_weight",
            "mm_tp50_weight",
            "mm_ssi",
            "add_m3",
            "add_vsd",
            "add_sc",
            "bc12_cb",
            "bc12_m",
            "bc11_ac",
            "bc11_vsd",
            "bc16_cb",
            "bc16_m",
            "rs_type",
            "bc9_moist",
            "bc10_moist",
            "bc11_moist",
            "bc9_temp",
            "bc10_temp",
            "bc11_temp",
        ].forEach((f) => $("#" + f).val(data[f] ?? ""));
    }

    // openadd
    $(document)
        .off("click", ".btn-add-gs")
        .on("click", ".btn-add-gs", function () {
            resetGsForm();
            $("#modal-greensand").modal("show");
        });

    // openedit
    $(document)
        .off("click", ".btn-edit-gs")
        .on("click", ".btn-edit-gs", function () {
            clearErrors();
            const id = $(this).data("id");
            resetGsForm();
            $.get(`${serversideRoutes.base}/${id}`)
                .done((res) => {
                    fillGsForm(res.data);
                    $("#modal-greensand").modal("show");
                })
                .fail((xhr) => {
                    const $alert = $("#gsFormAlert");
                    if ($alert.length)
                        $alert
                            .removeClass("d-none")
                            .text("Gagal mengambil data (edit).");
                    console.error(xhr.responseText || xhr);
                });
        });

    // submit
    $("#gsForm")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();
            clearErrors();
            const mode = $("#gs_mode").val();
            const id = $("#gs_id").val();
            const formData = $(this).serialize();
            $("#gsSubmitBtn").prop("disabled", true);

            const req =
                mode === "edit"
                    ? $.post(
                          `${serversideRoutes.base}/${id}`,
                          formData + "&_method=PUT"
                      )
                    : $.post(serversideRoutes.store, formData);

            req.done(() => {
                $("#modal-greensand").modal("hide");
                reloadAll();
            })
                .fail((xhr) => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        applyErrors(xhr.responseJSON.errors);
                    } else if (xhr.status === 419) {
                        const $alert = $("#gsFormAlert");
                        if ($alert.length)
                            $alert
                                .removeClass("d-none")
                                .text(
                                    "CSRF token invalid (419). Silakan refresh halaman."
                                );
                    } else {
                        const $alert = $("#gsFormAlert");
                        if ($alert.length)
                            $alert
                                .removeClass("d-none")
                                .text("Gagal menyimpan data.");
                    }
                    console.error(xhr.responseText || xhr);
                })
                .always(() => {
                    $("#gsSubmitBtn").prop("disabled", false);
                });
        });

    // delete
    let pendingDeleteId = null;
    $(document)
        .off("click", ".btn-delete-gs")
        .on("click", ".btn-delete-gs", function () {
            pendingDeleteId = $(this).data("id");
            $("#confirmDeleteModal").modal("show");
        });

    $("#confirmDeleteYes")
        .off("click")
        .on("click", function () {
            if (!pendingDeleteId) return;
            $.post(`${serversideRoutes.base}/${pendingDeleteId}`, {
                _method: "DELETE",
            })
                .done(() => {
                    $("#confirmDeleteModal").modal("hide");
                    reloadAll();
                })
                .fail((xhr) => {
                    const msg =
                        xhr.status === 419
                            ? "CSRF token invalid (419). Silakan refresh halaman."
                            : "Gagal menghapus data.";
                    const $body = $("#confirmDeleteModal .modal-body");
                    $body.prepend(
                        '<div class="alert alert-danger mb-2">' + msg + "</div>"
                    );
                    setTimeout(() => {
                        $("#confirmDeleteModal .alert").remove();
                    }, 2500);
                    console.error(xhr.responseText || xhr);
                })
                .always(() => {
                    pendingDeleteId = null;
                });
        });

    // select2
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%",
        allowClear: true,
        placeholder: function () {
            return $(this).data("placeholder") || "Select";
        },
    });

    // datepicker
    $("#startDate, #endDate").datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        clearBtn: true,
        orientation: "bottom",
    });

    // pairing
    $("#startDate").on("changeDate clearDate change", function () {
        const start = $(this).val();
        if (start) {
            $("#endDate").datepicker("setStartDate", start);
            $("#endDate").datepicker("setDate", start);
        } else {
            $("#endDate").datepicker("setStartDate", null);
            $("#endDate").val("");
        }
    });
    $("#endDate").on("changeDate clearDate change", function () {
        const end = $(this).val();
        if (end) $("#startDate").datepicker("setEndDate", end);
        else $("#startDate").datepicker("setEndDate", null);
    });

    // getters
    const getShift = () => $("#shiftSelect").val() || "";
    const getKeyword = () => $("#keywordInput").val() || "";

    // columns
    const baseColumns = [
        { data: "action", orderable: false, searchable: false },
        { data: "date", name: "date" },
        { data: "shift", name: "shift" },
        { data: "mm", name: "mm" },
        { data: "mix_ke", name: "mix_ke" },
        { data: "mix_start", name: "mix_start" },
        { data: "mix_finish", name: "mix_finish" },
        { data: "mm_p", name: "mm_p" },
        { data: "mm_c", name: "mm_c" },
        { data: "mm_gt", name: "mm_gt" },
        { data: "mm_cb_mm", name: "mm_cb_mm" },
        { data: "mm_cb_lab", name: "mm_cb_lab" },
        { data: "mm_m", name: "mm_m" },
        { data: "mm_bakunetsu", name: "mm_bakunetsu" },
        { data: "mm_ac", name: "mm_ac" },
        { data: "mm_tc", name: "mm_tc" },
        { data: "mm_vsd", name: "mm_vsd" },
        { data: "mm_ig", name: "mm_ig" },
        { data: "mm_cb_weight", name: "mm_cb_weight" },
        { data: "mm_tp50_weight", name: "mm_tp50_weight" },
        { data: "mm_ssi", name: "mm_ssi" },
        { data: "add_m3", name: "add_m3" },
        { data: "add_vsd", name: "add_vsd" },
        { data: "add_sc", name: "add_sc" },
        { data: "bc12_cb", name: "bc12_cb" },
        { data: "bc12_m", name: "bc12_m" },
        { data: "bc11_ac", name: "bc11_ac" },
        { data: "bc11_vsd", name: "bc11_vsd" },
        { data: "bc16_cb", name: "bc16_cb" },
        { data: "bc16_m", name: "bc16_m" },
        { data: "rs_time", name: "rs_time" },
        { data: "rs_type", name: "rs_type" },
        { data: "bc9_moist", name: "bc9_moist" },
        { data: "bc10_moist", name: "bc10_moist" },
        { data: "bc11_moist", name: "bc11_moist" },
        { data: "bc9_temp", name: "bc9_temp" },
        { data: "bc10_temp", name: "bc10_temp" },
        { data: "bc11_temp", name: "bc11_temp" },
    ];

    // factory
    function makeDt($el, url) {
        return $el.DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            scrollX: true,
            autoWidth: false,
            searching: true,
            orderMulti: false,
            searchDelay: 350,
            pagingType: "simple_numbers",
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, 500, 1000],
                [10, 25, 50, 100, 500, 1000],
            ],
            ajax: {
                url: url,
                data: function (d) {
                    d.start_date = $("#startDate").val();
                    d.end_date = $("#endDate").val();
                    d.shift = getShift();
                    d.keyword = getKeyword();
                },
            },
            order: [[1, "desc"]],
            columns: baseColumns,
            stateSave: false,
        });
    }

    // instances
    const instances = { mm1: null, mm2: null, all: null };
    window.instances = instances;

    // init
    instances.mm1 = makeDt($("#dt-mm1"), serversideRoutes.mm1);

    // tabs
    $('a[data-toggle="tab"]')
        .off("shown.bs.tab")
        .on("shown.bs.tab", function (e) {
            const target = $(e.target).attr("href");
            if (target === "#mm2" && !instances.mm2)
                instances.mm2 = makeDt($("#dt-mm2"), serversideRoutes.mm2);
            if (target === "#all" && !instances.all)
                instances.all = makeDt($("#dt-all"), serversideRoutes.all);
            $.fn.dataTable
                .tables({ visible: true, api: true })
                .columns.adjust();
        });

    // reload
    function reloadAll() {
        $.fn.dataTable
            .tables({ visible: false, api: true })
            .ajax.reload(null, false);
    }
    window.reloadAll = reloadAll;

    // filters
    $("#btnSearch, #btnQuickSearch").off("click").on("click", reloadAll);

    $("#btnRefresh")
        .off("click")
        .on("click", function () {
            $("#startDate")
                .val("")
                .datepicker("setDate", null)
                .datepicker("setStartDate", null)
                .datepicker("setEndDate", null);
            $("#endDate")
                .val("")
                .datepicker("setDate", null)
                .datepicker("setStartDate", null)
                .datepicker("setEndDate", null);
            $("#keywordInput").val("");
            reloadAll();
        });

    $("#startDate, #endDate").off("changeDate").on("changeDate", reloadAll);
    $("#shiftSelect").off("change").on("change", reloadAll);

    // keyword debounce
    let kwTimer = null;
    $("#keywordInput")
        .off("input keydown")
        .on("input", () => {
            clearTimeout(kwTimer);
            kwTimer = setTimeout(reloadAll, 350);
        })
        .on("keydown", (e) => {
            if (e.key === "Enter") {
                clearTimeout(kwTimer);
                reloadAll();
            }
        });
});
