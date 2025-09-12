$(function () {
    // --- Native datepicker UX ---
    function openNativeDatepicker(input) {
        if (input.type !== "date") input.type = "date";
        if (typeof input.showPicker === "function") {
            setTimeout(() => input.showPicker(), 0);
        } else {
            input.focus();
        }
    }

    // StartDate: buka picker otomatis & kunci EndDate >= StartDate
    $("#startDate")
        .on("focus", function () {
            openNativeDatepicker(this);
        })
        .on("click", function () {
            openNativeDatepicker(this);
        })
        .on("change", function () {
            const start = $(this).val();
            const $end = $("#endDate");
            if (start) {
                $end.attr("min", start);
                if (!$end.val() || $end.val() < start) $end.val(start);
            } else {
                $end.removeAttr("min");
            }
        })
        .on("blur", function () {
            if (!this.value) this.type = "text";
        });

    // EndDate: buka picker otomatis, hormati min
    $("#endDate")
        .on("focus", function () {
            openNativeDatepicker(this);
        })
        .on("click", function () {
            openNativeDatepicker(this);
        })
        .on("blur", function () {
            if (!this.value) this.type = "text";
        });

    // --- Smooth collapse filter ---
    $("#filterHeader")
        .off("click")
        .on("click", function () {
            $("#filterCollapse").stop(true, true).slideToggle(180);
            $("#filterIcon").toggleClass("ri-subtract-line ri-add-line");
        });

    // ====== Helpers modal ======
    function resetGsForm() {
        $("#gsForm")[0].reset();
        $("#gs_id").val("");
        $("#gs_mode").val("create");
        $("#gsModalMode").text("Add");

        // MM default 1
        $('input[name="mm"][value="1"]').prop("checked", true);
        $("#mm1_btn").addClass("active");
        $("#mm2_btn").removeClass("active");

        const today = new Date().toISOString().slice(0, 10);
        $("#process_date").val(today);
    }

    function fillGsForm(data) {
        $("#gs_id").val(data.id);
        $("#gs_mode").val("edit");
        $("#gsModalMode").text("Edit");

        // MM → 1/2
        const mmVal = data.mm === "MM2" ? "2" : "1";
        $('input[name="mm"][value="' + mmVal + '"]').prop("checked", true);
        $("#mm1_btn,#mm2_btn").removeClass("active");
        (mmVal === "1" ? $("#mm1_btn") : $("#mm2_btn")).addClass("active");

        $("#shift").val(data.shift || "");
        $("#mix_ke").val(data.mix_ke || "");
        $("#mix_finish").val(data.mix_finish || "");

        // pecah date dari server (bisa "2025-09-12 08:15:00" atau ISO)
        const raw = (data.date || "").toString();
        const ymd = raw.slice(0, 10); // 0..9
        const his = raw.slice(11, 16); // 11..15
        $("#process_date").val(ymd || "");
        $("#mix_start").val(his || "");

        // … isi field lain sesuai nama id (mm_p, mm_c, dst)

        const fields = [
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
            "rs_time",
            "rs_type",
            "bc9_moist",
            "bc10_moist",
            "bc11_moist",
            "bc9_temp",
            "bc10_temp",
            "bc11_temp",
        ];
        fields.forEach((f) => $("#" + f).val(data[f] ?? ""));
    }

    // ====== CSRF utk semua AJAX ======
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    // ====== Open Add modal ======
    $(document).on("click", ".btn-add-gs", function () {
        resetGsForm();
        $("#modal-greensand").modal("show");
    });

    // ====== Open Edit modal ======
    $(document).on("click", ".btn-edit-gs", function () {
        const id = $(this).data("id");
        resetGsForm();
        // GET detail by base URL (tanpa replace placeholder)
        $.get(`${serversideRoutes.base}/${id}`)
            .done((res) => {
                fillGsForm(res.data);
                $("#modal-greensand").modal("show");
            })
            .fail((xhr) => {
                alert("Gagal mengambil data (edit).");
                console.error(xhr.responseText || xhr);
            });
    });

    // ====== Submit form (create / update) ======
    $("#gsForm").on("submit", function (e) {
        e.preventDefault();
        const mode = $("#gs_mode").val();
        const id = $("#gs_id").val();
        const formData = $(this).serialize();

        $("#gsSubmitBtn").prop("disabled", true);

        // Spoof _method utk UPDATE (lebih kompatibel)
        const req =
            mode === "edit"
                ? $.post(
                      `${serversideRoutes.base}/${id}`,
                      formData + "&_method=PUT"
                  )
                : $.post(serversideRoutes.store, formData);

        req.done(() => {
            $("#modal-greensand").modal("hide");
            const idTab = $(".tab-pane.active").attr("id"); // mm1|mm2|all
            if (window.instances && window.instances[idTab]) {
                window.instances[idTab].ajax.reload(null, false);
            }
        })
            .fail((xhr) => {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const msgs = Object.values(xhr.responseJSON.errors)
                        .flat()
                        .join("\n");
                    alert("Validasi gagal:\n" + msgs);
                } else if (xhr.status === 419) {
                    alert("CSRF token invalid (419). Refresh halaman.");
                } else {
                    alert("Gagal menyimpan data. Cek console.");
                }
                console.error(xhr.responseText || xhr);
            })
            .always(() => {
                $("#gsSubmitBtn").prop("disabled", false);
            });
    });

    // ====== Delete row ======
    $(document).on("click", ".btn-delete-gs", function () {
        const id = $(this).data("id");
        if (!confirm("Yakin hapus data ini?")) return;

        // Spoof DELETE method
        $.post(`${serversideRoutes.base}/${id}`, { _method: "DELETE" })
            .done(() => {
                const idTab = $(".tab-pane.active").attr("id");
                if (window.instances && window.instances[idTab]) {
                    window.instances[idTab].ajax.reload(null, false);
                }
            })
            .fail((xhr) => {
                if (xhr.status === 419) {
                    alert("CSRF token invalid (419). Refresh halaman.");
                } else {
                    alert("Gagal menghapus data. Cek console.");
                }
                console.error(xhr.responseText || xhr);
            });
    });

    // ====== DataTables ======
    const getShift = () => $("#shiftSelect").val() || "";
    const getKeyword = () => $("#keywordInput").val() || "";

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
                [10, 25, 50, 100],
                [10, 25, 50, 100],
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

    const instances = { mm1: null, mm2: null, all: null };
    window.instances = instances; // <- expose global

    // init MM1
    instances.mm1 = makeDt($("#dt-mm1"), serversideRoutes.mm1);

    // lazy init tab lain
    $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
        const target = $(e.target).attr("href");
        if (target === "#mm2" && !instances.mm2) {
            instances.mm2 = makeDt($("#dt-mm2"), serversideRoutes.mm2);
        }
        if (target === "#all" && !instances.all) {
            instances.all = makeDt($("#dt-all"), serversideRoutes.all);
        }
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    function reloadActive() {
        const id = $(".tab-pane.active").attr("id");
        const dt = instances[id];
        if (dt) dt.ajax.reload();
    }

    $("#btnSearch, #btnQuickSearch").on("click", reloadActive);
    $("#btnRefresh").on("click", function () {
        $("#startDate,#endDate").val("");
        $("#shiftSelect").val("");
        $("#keywordInput").val("");
        reloadActive();
    });
    $("#keywordInput").on("keydown", (e) => {
        if (e.key === "Enter") reloadActive();
    });
});
