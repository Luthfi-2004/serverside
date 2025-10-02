$(function () {
    // guard
    if (!window.greensandRoutes) {
        console.error(
            "greensandRoutes tidak ditemukan. Pastikan Blade sudah @push('scripts')."
        );
        return;
    }

    // csrf
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    // helpers
    const helpers = {
        detectShiftByNow() {
            const hh = new Date().getHours();
            return hh >= 6 && hh < 16 ? "D" : hh >= 16 && hh < 22 ? "S" : "N";
        },
        todayDdMmYyyy() {
            const d = new Date();
            return `${String(d.getDate()).padStart(2, "0")}-${String(
                d.getMonth() + 1
            ).padStart(2, "0")}-${d.getFullYear()}`;
        },
        getActiveTab() {
            const st = (window.__GS_ACTIVE_TAB__ || "").toLowerCase();
            if (["mm1", "mm2", "all"].includes(st)) return st;
            const href = $(".nav-tabs .nav-link.active").attr("href") || "#mm1";
            const id = href.startsWith("#") ? href.slice(1) : href;
            return ["mm1", "mm2", "all"].includes(id) ? id : "mm1";
        },
        pickTime(val) {
            if (!val) return "";
            const m = String(val).match(/T?(\d{2}:\d{2})(?::\d{2})?/);
            return m ? m[1] : String(val);
        },
        getKeyword: () => $("#keywordInput").val() || "",
    };

    // globals
    window.__GS_ACTIVE_TAB__ = "mm1";
    let pendingDeleteId = null;
    const instances = { mm1: null, mm2: null, all: null };

    // ui
    $("#filterHeader")
        .off("click")
        .on("click", function () {
            $("#filterCollapse").stop(true, true).slideToggle(180);
            $("#filterIcon").toggleClass("ri-subtract-line ri-add-line");
        });

    // error
    const errorHandler = {
        clear() {
            $("#gsForm .form-control, #gsForm .custom-select").removeClass(
                "is-invalid"
            );
            $("#mm1_btn,#mm2_btn").removeClass("is-invalid");
            $("#gsForm .invalid-feedback").text("").hide();
            const $alert = $("#gsFormAlert");
            if ($alert.length) $alert.addClass("d-none").text("");
        },
        apply(errs) {
            const map = {
                mm: {
                    type: "group",
                    target: "#mm_error",
                    groupBtns: ["#mm1_btn", "#mm2_btn"],
                },
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
                        m.groupBtns.forEach((sel) =>
                            $(sel).addClass("is-invalid")
                        );
                        if ($(m.target).length) $(m.target).text(msg).show();
                    }
                } else {
                    general.push(msg);
                }
            });
            if (general.length) {
                const $alert = $("#gsFormAlert");
                if ($alert.length)
                    $alert.removeClass("d-none").text(general.join(" "));
            }
        },
    };

    // form
    const formManager = {
        reset() {
            $("#gsForm")[0]?.reset();
            $("#gs_id").val("");
            $("#gs_mode").val("create");
            $("#gsModalMode").text("Add");

            const mmDefault = helpers.getActiveTab() === "mm2" ? "2" : "1";
            $(`input[name="mm"][value="${mmDefault}"]`).prop("checked", true);
            $("#mm1_btn,#mm2_btn").removeClass("active");
            (mmDefault === "1" ? $("#mm1_btn") : $("#mm2_btn")).addClass(
                "active"
            );

            const curShift =
                $("#shiftSelect").val() || helpers.detectShiftByNow();
            const label =
                curShift === "D" ? "Day" : curShift === "S" ? "Swing" : "Night";
            $("#gsShiftInfo").text(`Shift: ${curShift} (${label})`);

            errorHandler.clear();
        },
        fill(data) {
            $("#gs_id").val(data.id);
            $("#gs_mode").val("edit");
            $("#gsModalMode").text("Edit");

            const curShift = data.shift || "-";
            const label =
                curShift === "D"
                    ? "Day"
                    : curShift === "S"
                    ? "Swing"
                    : curShift === "N"
                    ? "Night"
                    : "-";
            $("#gsShiftInfo").text(`Shift: ${curShift} (${label})`);

            const mmVal = data.mm === "MM2" ? "2" : "1";
            $(`input[name="mm"][value="${mmVal}"]`).prop("checked", true);
            $("#mm1_btn,#mm2_btn").removeClass("active");
            (mmVal === "1" ? $("#mm1_btn") : $("#mm2_btn")).addClass("active");

            $("#mix_ke").val(data.mix_ke || "");
            $("#mix_start").val(helpers.pickTime(data.mix_start));
            $("#mix_finish").val(helpers.pickTime(data.mix_finish));
            $("#rs_time").val(helpers.pickTime(data.rs_time));

            const fields = [
                "mm_p",
                "mm_c",
                "mm_gt",
                "mm_cb_mm",
                "mm_cb_lab",
                "mm_m",
                "machine_no",
                "mm_bakunetsu",
                "mm_ac",
                "mm_tc",
                "mm_vsd",
                "mm_ig",
                "mm_cb_weight",
                "mm_tp50_weight",
                "mm_tp50_height",
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
                "add_water_mm",
                "add_water_mm_2",
                "temp_sand_mm_1",
                "rcs_pick_up",
                "total_flask",
                "rcs_avg",
                "add_bentonite_ma",
                "total_sand",
            ];
            fields.forEach((f) => $("#" + f).val(data[f] ?? ""));
        },
    };

    // crud
    $(document)
        .off("click", ".btn-add-gs")
        .on("click", ".btn-add-gs", function () {
            formManager.reset();
            $("#modal-greensand").modal("show");
        });

    $(document)
        .off("click", ".btn-edit-gs")
        .on("click", ".btn-edit-gs", function () {
            errorHandler.clear();
            const id = $(this).data("id");
            formManager.reset();
            $.get(`${greensandRoutes.base}/${id}`)
                .done((res) => {
                    formManager.fill(res.data);
                    $("#modal-greensand").modal("show");
                })
                .fail((xhr) => {
                    $("#gsFormAlert")
                        .removeClass("d-none")
                        .text("Gagal mengambil data (edit).");
                    console.error(xhr.responseText || xhr);
                });
        });

    $("#gsForm")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();
            errorHandler.clear();

            const shift = $("#shiftSelect").val() || "";
            if (!shift) {
                $("#gsFormAlert")
                    .removeClass("d-none")
                    .text("Shift wajib dipilih dari filter.");
                return;
            }

            const mode = $("#gs_mode").val();
            const id = $("#gs_id").val();
            const date = $("#filterDate").val() || "";
            let formData =
                $(this).serialize() +
                `&shift=${encodeURIComponent(shift)}&date=${encodeURIComponent(
                    date
                )}`;

            $("#gsSubmitBtn").prop("disabled", true);
            const req =
                mode === "edit"
                    ? $.post(
                          `${greensandRoutes.base}/${id}`,
                          formData + "&_method=PUT"
                      )
                    : $.post(greensandRoutes.store, formData);

            req.done(() => {
                $("#modal-greensand").modal("hide");
                reloadAll();
                if (window.gsFlash)
                    gsFlash("Data berhasil disimpan.", "success");
            })
                .fail((xhr) => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        errorHandler.apply(xhr.responseJSON.errors);
                    } else if (xhr.status === 419) {
                        $("#gsFormAlert")
                            .removeClass("d-none")
                            .text(
                                "CSRF token invalid (419). Silakan refresh halaman."
                            );
                    } else {
                        $("#gsFormAlert")
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
            $.post(`${greensandRoutes.base}/${pendingDeleteId}`, {
                _method: "DELETE",
            })
                .done(() => {
                    $("#confirmDeleteModal").modal("hide");
                    reloadAll();
                    if (window.gsFlash)
                        gsFlash("Data berhasil dihapus.", "success");
                })
                .fail((xhr) => {
                    const msg =
                        xhr.status === 419
                            ? "CSRF token invalid (419). Silakan refresh halaman."
                            : "Gagal menghapus data.";
                    $("#confirmDeleteModal .modal-body").prepend(
                        `<div class="alert alert-danger mb-2">${msg}</div>`
                    );
                    setTimeout(
                        () => $("#confirmDeleteModal .alert").remove(),
                        2500
                    );
                    console.error(xhr.responseText || xhr);
                })
                .always(() => {
                    pendingDeleteId = null;
                });
        });

    // select2
    $(function () {
        $("#shiftSelect").select2({
            width: "100%",
            placeholder:
                $("#shiftSelect").data("placeholder") || "Select shift",
        });
    });

    // filter
    $("#filterDate").datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        orientation: "bottom",
    });
    $("#btnSearch, #btnQuickSearch").off("click").on("click", reloadAll);
    $("#keywordInput")
        .off("keydown")
        .on("keydown", (e) => {
            if (e.key === "Enter") $("#btnSearch").trigger("click");
        });
    $("#btnRefresh")
        .off("click")
        .on("click", function () {
            $("#filterDate").datepicker("setDate", new Date());
            $("#shiftSelect").val(helpers.detectShiftByNow()).trigger("change");
            $("#keywordInput").val("");
            reloadAll();
            if (window.gsFlash) gsFlash("Filter direset.", "secondary");
        });

    // columns (Tambahkan mm_tp50_height setelah mm_tp50_weight)
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
        { data: "machine_no", name: "machine_no" },
        { data: "mm_bakunetsu", name: "mm_bakunetsu" },
        { data: "mm_ac", name: "mm_ac" },
        { data: "mm_tc", name: "mm_tc" },
        { data: "mm_vsd", name: "mm_vsd" },
        { data: "mm_ig", name: "mm_ig" },
        { data: "mm_cb_weight", name: "mm_cb_weight" },
        { data: "mm_tp50_weight", name: "mm_tp50_weight" },
        { data: "mm_tp50_height", name: "mm_tp50_height" },
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
        { data: "add_water_mm", name: "add_water_mm" },
        { data: "add_water_mm_2", name: "add_water_mm_2" },
        { data: "temp_sand_mm_1", name: "temp_sand_mm_1" },
        { data: "rcs_pick_up", name: "rcs_pick_up" },
        { data: "total_flask", name: "total_flask" },
        { data: "rcs_avg", name: "rcs_avg" },
        { data: "add_bentonite_ma", name: "add_bentonite_ma" },
        { data: "total_sand", name: "total_sand" },
    ];

    const baseColumnsWithDefaults = baseColumns.map((c) => ({
        ...c,
        defaultContent: "",
    }));

    // summary
    const summaryManager = {
        load() {
            if (!window.greensandRoutes.summary) return;
            $.get(window.greensandRoutes.summary, {
                date: $("#filterDate").val() || "",
                shift: $("#shiftSelect").val() || "",
                keyword: helpers.getKeyword(),
            })
                .done((res) => this.render(res?.summary || []))
                .fail(() => this.render([]));
        },
        ensureTfoot() {
            const $table = $("#dt-all");
            let $tfoot = $table.find("tfoot");
            if (!$tfoot.length) $tfoot = $("<tfoot/>").appendTo($table);
            return $tfoot;
        },
        render(summary) {
            const $tfoot = this.ensureTfoot();

            // index setelah penambahan mm_tp50_height
            const colIndex = {
                mm_p: 7,
                mm_c: 8,
                mm_gt: 9,
                mm_cb_mm: 10,
                mm_cb_lab: 11,
                mm_m: 12,
                // machine_no: 13,          // <-- JANGAN MASUKKAN DI SINI
                mm_bakunetsu: 14, // <-- sebelumnya 13, geser +1
                mm_ac: 15,
                mm_tc: 16,
                mm_vsd: 17,
                mm_ig: 18,
                mm_cb_weight: 19,
                mm_tp50_weight: 20,
                mm_tp50_height: 21,
                mm_ssi: 22,
                add_m3: 23,
                add_vsd: 24,
                add_sc: 25,
                bc12_cb: 26,
                bc12_m: 27,
                bc11_ac: 28,
                bc11_vsd: 29,
                bc16_cb: 30,
                bc16_m: 31,
                // rs_time (32) & rs_type (33) tidak di-summary
                bc9_moist: 34,
                bc10_moist: 35,
                bc11_moist: 36,
                bc9_temp: 37,
                bc10_temp: 38,
                bc11_temp: 39,
            };

            const makeRow = (label, valuesMap) => {
                let tds = `<td class="text-center font-weight-bold" colspan="7">${label}</td>`;
                for (let i = 7; i < baseColumns.length; i++) {
                    const val = valuesMap?.[i] ?? "";
                    tds += `<td class="text-center">${val}</td>`;
                }
                return `<tr class="gs-summary-row">${tds}</tr>`;
            };

            const rows = { min: {}, max: {}, avg: {}, judge: {} };
            summary.forEach((s) => {
                const idx = colIndex[s.field];
                if (idx == null) return;
                rows.min[idx] = s.min ?? "";
                rows.max[idx] = s.max ?? "";
                rows.avg[idx] = s.avg ?? "";
                rows.judge[idx] = s.judge
                    ? `<span class="${
                          s.judge === "NG"
                              ? "text-danger font-weight-bold"
                              : "text-success font-weight-bold"
                      }">${s.judge}</span>`
                    : "";
            });

            const html =
                makeRow("MIN", rows.min) +
                makeRow("MAX", rows.max) +
                makeRow("AVG", rows.avg) +
                makeRow("JUDGE", rows.judge);
            $tfoot.html(html);
            $tfoot.find("td").addClass("text-center");
        },
    };

    // datatable
    function makeDt($el, url) {
        // ---- guard: hindari re-init yang bikin header dobel
        if ($.fn.dataTable.isDataTable($el)) return $el.DataTable();

        const isAll = $el.attr("id") === "dt-all";
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
                data: (d) => {
                    d.date = $("#filterDate").val();
                    d.shift = $("#shiftSelect").val() || "";
                    d.keyword = helpers.getKeyword();
                },
            },
            order: isAll
                ? [
                      [3, "asc"],
                      [1, "desc"],
                  ]
                : [[1, "desc"]],
            columns: baseColumnsWithDefaults,
            stateSave: false,
            orderCellsTop: true, // <<< penting untuk multi-row thead
            drawCallback: function () {
                if (isAll) {
                    const $tbody = $(this.api().table().body());
                    $tbody.find("tr.mm-spacer").remove();
                    let prevMM = null;

                    $tbody.find("tr").each(function () {
                        const $tr = $(this);
                        const cells = $tr.find("td");
                        if (!cells.length) return;
                        const mmText = (cells.eq(3).text() || "").trim();
                        if (prevMM !== null && prevMM !== mmText) {
                            for (let i = 0; i < 3; i++) {
                                $tr.before(
                                    `<tr class="mm-spacer"><td colspan="${cells.length}" style="height:10px;border:none;padding:0;"></td></tr>`
                                );
                            }
                        }
                        prevMM = mmText;
                    });
                    summaryManager.load(); // tfoot
                }
            },
        });
    }

    // init-dt
    instances.mm1 = makeDt($("#dt-mm1"), greensandRoutes.mm1);

    // seed
    const href = (
        $(".nav-tabs .nav-link.active").attr("href") || "#mm1"
    ).toLowerCase();
    window.__GS_ACTIVE_TAB__ =
        href === "#mm2" ? "mm2" : href === "#all" ? "all" : "mm1";

    // tabs
    $('a[data-toggle="tab"]')
        .off("shown.bs.tab")
        .on("shown.bs.tab", function (e) {
            const href = ($(e.target).attr("href") || "").toLowerCase();
            window.__GS_ACTIVE_TAB__ =
                href === "#mm2" ? "mm2" : href === "#all" ? "all" : "mm1";

            if (href === "#mm2" && !instances.mm2)
                instances.mm2 = makeDt($("#dt-mm2"), greensandRoutes.mm2);
            if (href === "#all" && !instances.all)
                instances.all = makeDt($("#dt-all"), greensandRoutes.all);

            $.fn.dataTable
                .tables({ visible: true, api: true })
                .columns.adjust();

            if (href === "#all") setTimeout(() => summaryManager.load(), 100);
        });

    // reload
    function reloadAll() {
        $.fn.dataTable
            .tables({ visible: false, api: true })
            .ajax.reload(null, false);
        if (helpers.getActiveTab() === "all")
            setTimeout(() => summaryManager.load(), 500);
    }
    window.reloadAll = reloadAll;

    // export
    $("#btnExport")
        .off("click")
        .on("click", function () {
            if (!window.greensandRoutes || !greensandRoutes.export) return;
            const tab = helpers.getActiveTab();
            const mm = tab === "mm1" ? "MM1" : tab === "mm2" ? "MM2" : ""; // "" = All
            const params = {
                date: $("#filterDate").val() || "",
                shift: $("#shiftSelect").val() || "",
                keyword: helpers.getKeyword(),
            };
            if (mm) params.mm = mm;
            const q = $.param(params);
            window.location.href = greensandRoutes.export + (q ? "?" + q : "");
            if (window.gsFlash) gsFlash("Menyiapkan file Excel…", "info");
        });

    // init
    $("#filterDate").datepicker("setDate", new Date());
    $("#shiftSelect").val(helpers.detectShiftByNow()).trigger("change");
    reloadAll();
});
