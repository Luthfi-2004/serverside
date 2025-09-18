// public/assets/js/serverside.js
$(function () {
    // guard
    if (!window.serversideRoutes) {
        console.error(
            "serversideRoutes tidak ditemukan. Pastikan Blade sudah @push('scripts')."
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
    function detectShiftByNow() {
        const hh = new Date().getHours();
        if (hh >= 6 && hh < 16) return "D";
        if (hh >= 16 && hh < 22) return "S";
        return "N";
    }
    function todayDdMmYyyy() {
        const d = new Date();
        return `${String(d.getDate()).padStart(
            2,
            "0"
        )}-${String(d.getMonth() + 1).padStart(2, "0")}-${d.getFullYear()}`;
    }
    function getActiveTab() {
        const st = (window.__GS_ACTIVE_TAB__ || "").toLowerCase();
        if (["mm1", "mm2", "all"].includes(st)) return st;
        const href = $(".nav-tabs .nav-link.active").attr("href") || "#mm1";
        const id = href.startsWith("#") ? href.slice(1) : href;
        return ["mm1", "mm2", "all"].includes(id) ? id : "mm1";
    }
    function pickTime(val) {
        if (!val) return "";
        const m = String(val).match(/T?(\d{2}:\d{2})(?::\d{2})?/);
        return m ? m[1] : String(val);
    }
    const getKeyword = () => $("#keywordInput").val() || "";

    // ui
    window.__GS_ACTIVE_TAB__ = "mm1";
    $("#filterHeader")
        .off("click")
        .on("click", function () {
            $("#filterCollapse").stop(true, true).slideToggle(180);
            $("#filterIcon").toggleClass("ri-subtract-line ri-add-line");
        });

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
            if ($alert.length)
                $alert.removeClass("d-none").text(general.join(" "));
        }
    }

    // form
    function resetGsForm() {
        $("#gsForm")[0]?.reset();
        $("#gs_id").val("");
        $("#gs_mode").val("create");
        $("#gsModalMode").text("Add");

        const mmDefault = getActiveTab() === "mm2" ? "2" : "1";
        $(`input[name="mm"][value="${mmDefault}"]`).prop("checked", true);
        $("#mm1_btn,#mm2_btn").removeClass("active");
        (mmDefault === "1" ? $("#mm1_btn") : $("#mm2_btn")).addClass("active");

        const curShift = $("#shiftSelect").val() || detectShiftByNow();
        const label =
            curShift === "D" ? "Day" : curShift === "S" ? "Swing" : "Night";
        if ($("#gsShiftInfo").length)
            $("#gsShiftInfo").text(`Shift: ${curShift} (${label})`);

        clearErrors();
    }
    function fillGsForm(data) {
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
        if ($("#gsShiftInfo").length)
            $("#gsShiftInfo").text(`Shift: ${curShift} (${label})`);

        const mmVal = data.mm === "MM2" ? "2" : "1";
        $(`input[name="mm"][value="${mmVal}"]`).prop("checked", true);
        $("#mm1_btn,#mm2_btn").removeClass("active");
        (mmVal === "1" ? $("#mm1_btn") : $("#mm2_btn")).addClass("active");

        $("#mix_ke").val(data.mix_ke || "");
        $("#mix_start").val(pickTime(data.mix_start));
        $("#mix_finish").val(pickTime(data.mix_finish));
        $("#rs_time").val(pickTime(data.rs_time));

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

    // add
    $(document)
        .off("click", ".btn-add-gs")
        .on("click", ".btn-add-gs", function () {
            resetGsForm();
            $("#modal-greensand").modal("show");
        });

    // edit
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

            const shift = $("#shiftSelect").val() || "";
            const date = $("#filterDate").val() || "";
            if (!shift) {
                const $alert = $("#gsFormAlert");
                if ($alert.length)
                    $alert
                        .removeClass("d-none")
                        .text("Shift wajib dipilih dari filter.");
                return;
            }

            const mode = $("#gs_mode").val();
            const id = $("#gs_id").val();
            let formData = $(this).serialize();
            formData += `&shift=${encodeURIComponent(
                shift
            )}&date=${encodeURIComponent(date)}`;

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
                    $("#confirmDeleteModal .modal-body").prepend(
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

    // filter
    $("#shiftSelect")
        .select2()
        .off("change.gs")
        .on("change.gs", function () {
            reloadAll();
        });

    $("#filterDate").datepicker({
        format: "dd-mm-yyyy",
        autoclose: true,
        orientation: "bottom",
    });

    // search
    $("#btnSearch, #btnQuickSearch").off("click").on("click", reloadAll);
    $("#keywordInput")
        .off("keydown")
        .on("keydown", (e) => {
            if (e.key === "Enter") $("#btnSearch").trigger("click");
        });
    $("#btnRefresh")
        .off("click")
        .on("click", function () {
            $("#filterDate").datepicker("setDate", todayDdMmYyyy());
            $("#shiftSelect").val(detectShiftByNow()).trigger("change");
            $("#keywordInput").val("");
            reloadAll();
        });

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

    // footer summary function - extracted to be reusable
    function loadAllFooterSummary() {
        if (!window.serversideRoutes.summary) return;
        
        $.get(window.serversideRoutes.summary, {
            date: $("#filterDate").val() || "",
            shift: $("#shiftSelect").val() || "",
            keyword: $("#keywordInput").val() || "",
        })
        .done((res) => renderAllFooterSummary(res && res.summary ? res.summary : []))
        .fail(() => renderAllFooterSummary([]));
    }

    // footer
    function renderAllFooterSummary(summary) {
        const $tfoot = $("#dt-all tfoot");
        if (!$tfoot.length) return;

        const colCount = baseColumns.length;

        function makeRow(label, valuesMap) {
            let tds = "";
            for (let i = 0; i < colCount; i++) {
                if (i === 0) {
                    tds += `<td>${label}</td>`;
                    continue;
                }
                const val =
                    valuesMap && valuesMap[i] != null ? valuesMap[i] : "";
                tds += `<td>${val}</td>`;
            }
            return `<tr class="gs-summary-row">${tds}</tr>`;
        }

        const colIndex = {
            mm_p: 7,
            mm_c: 8,
            mm_gt: 9,
            mm_cb_mm: 10,
            mm_cb_lab: 11,
            mm_m: 12,
            mm_bakunetsu: 13,
            mm_ac: 14,
            mm_tc: 15,
            mm_vsd: 16,
            mm_ig: 17,
            mm_cb_weight: 18,
            mm_tp50_weight: 19,
            mm_ssi: 20,
            add_m3: 21,
            add_vsd: 22,
            add_sc: 23,
            bc12_cb: 24,
            bc12_m: 25,
            bc11_ac: 26,
            bc11_vsd: 27,
            bc16_cb: 28,
            bc16_m: 29,
            bc9_moist: 32,
            bc10_moist: 33,
            bc11_moist: 34,
            bc9_temp: 35,
            bc10_temp: 36,
            bc11_temp: 37,
        };

        const rowMin = {},
            rowMax = {},
            rowAvg = {},
            rowJudge = {};
        (summary || []).forEach((s) => {
            const idx = colIndex[s.field];
            if (idx == null) return;
            rowMin[idx] = s.min != null ? s.min : "";
            rowMax[idx] = s.max != null ? s.max : "";
            rowAvg[idx] = s.avg != null ? s.avg : "";
            rowJudge[idx] = s.judge
                ? `<span class="${
                      s.judge === "NG"
                          ? "text-danger font-weight-bold"
                          : "text-success font-weight-bold"
                  }">${s.judge}</span>`
                : "";
        });

        const html =
            makeRow("MIN", rowMin) +
            makeRow("MAX", rowMax) +
            makeRow("AVG", rowAvg) +
            makeRow("JUDGE", rowJudge);
        $tfoot.html(html);

        return html;
    }

    // table
    function makeDt($el, url) {
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
                data: function (d) {
                    d.date = $("#filterDate").val();
                    d.shift = $("#shiftSelect").val() || "";
                    d.keyword = getKeyword();
                },
            },
            order: isAll
                ? [
                      [3, "asc"],
                      [1, "desc"],
                  ]
                : [[1, "desc"]],
            columns: baseColumns,
            stateSave: false,
            drawCallback: function () {
                const api = this.api();

                if (isAll) {
                    const mmColIdx = 3;
                    const $tbody = $(api.table().body());
                    $tbody.find("tr.mm-spacer").remove();
                    let prevMM = null;
                    $tbody.find("tr").each(function () {
                        const $tr = $(this);
                        const cells = $tr.find("td");
                        if (!cells.length) return;
                        const mmText = (cells.eq(mmColIdx).text() || "").trim();
                        if (prevMM !== null && prevMM !== mmText) {
                            for (let i = 0; i < 3; i++) {
                                $tr.before(
                                    `<tr class="mm-spacer"><td colspan="${cells.length}" style="height:10px;border:none;padding:0;"></td></tr>`
                                );
                            }
                        }
                        prevMM = mmText;
                    });

                    // Load footer summary after table is drawn
                    loadAllFooterSummary();
                }
            },
        });
    }

    // instances
    const instances = { mm1: null, mm2: null, all: null };
    instances.mm1 = makeDt($("#dt-mm1"), serversideRoutes.mm1);

    // seed
    (function seedActiveFromDom() {
        const href = (
            $(".nav-tabs .nav-link.active").attr("href") || "#mm1"
        ).toLowerCase();
        if (href === "#mm2") window.__GS_ACTIVE_TAB__ = "mm2";
        else if (href === "#all") window.__GS_ACTIVE_TAB__ = "all";
        else window.__GS_ACTIVE_TAB__ = "mm1";
    })();

    // tabs
    $('a[data-toggle="tab"]')
        .off("shown.bs.tab")
        .on("shown.bs.tab", function (e) {
            const href = ($(e.target).attr("href") || "").toLowerCase();
            if (href === "#mm2") window.__GS_ACTIVE_TAB__ = "mm2";
            else if (href === "#all") window.__GS_ACTIVE_TAB__ = "all";
            else window.__GS_ACTIVE_TAB__ = "mm1";

            if (href === "#mm2" && !instances.mm2)
                instances.mm2 = makeDt($("#dt-mm2"), serversideRoutes.mm2);
            if (href === "#all" && !instances.all)
                instances.all = makeDt($("#dt-all"), serversideRoutes.all);

            $.fn.dataTable
                .tables({ visible: true, api: true })
                .columns.adjust();

            // Load summary when switching to "all" tab
            if (href === "#all") {
                // Small delay to ensure table is fully rendered
                setTimeout(() => {
                    loadAllFooterSummary();
                }, 100);
            }
        });

    // reload
    function reloadAll() {
        $.fn.dataTable
            .tables({ visible: false, api: true })
            .ajax.reload(null, false);
        
        // If currently on "all" tab, reload the summary as well
        const currentTab = getActiveTab();
        if (currentTab === "all") {
            setTimeout(() => {
                loadAllFooterSummary();
            }, 500); // Wait a bit for the table to reload
        }
    }
    window.reloadAll = reloadAll;

    // export
    $(document)
        .off("click", "#btnExport")
        .on("click", "#btnExport", function (e) {
            e.preventDefault();
            const tab = getActiveTab();
            const mm = tab === "mm1" ? "MM1" : tab === "mm2" ? "MM2" : "";
            if (!window.serversideRoutes?.export) {
                console.error(
                    'Export route missing. Pastikan di Blade: route("greensand.export")'
                );
                return;
            }
            const u = new URL(
                window.serversideRoutes.export,
                window.location.origin
            );
            u.searchParams.set("date", $("#filterDate").val() || "");
            u.searchParams.set("shift", $("#shiftSelect").val() || "");
            u.searchParams.set("keyword", $("#keywordInput").val() || "");
            if (mm) u.searchParams.set("mm", mm);
            window.location.href = u.toString();
        });

    // init
    $("#filterDate").datepicker("setDate", todayDdMmYyyy());
    $("#shiftSelect").val(detectShiftByNow()).trigger("change");
    reloadAll();
});