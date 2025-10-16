// public/assets/js/ace.js
(function () {
    var $ = window.jQuery;

    if (!$) { console.error("jQuery not found. Please make sure jQuery is loaded before ace.js"); return; }
    if (!window.aceRoutes) { console.error("aceRoutes missing. Define it in Blade before loading ace.js"); return; }

    $.ajaxSetup({ cache: false });

    // UI Init
    function initPageUI() {
        try {
            $("#shiftSelect").select2({ width: "100%", placeholder: "Select shift" });
            $("#productSelectFilter").select2({
                width: "100%",
                placeholder: "All type",
                ajax: {
                    url: window.aceRoutes.lookupProducts,
                    dataType: "json",
                    delay: 200,
                    data: function (params) { return { q: params.term || "", page: params.page || 1 }; },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: Array.isArray(data.results) ? data.results : [],
                            pagination: { more: !!(data.pagination && data.pagination.more) },
                        };
                    },
                    cache: true,
                },
                minimumInputLength: 0,
                templateResult: function (item) { return item.text || ""; },
                templateSelection: function (item) { return item.text || item.id || ""; },
            });
        } catch (e) { console.error("Select2 init error:", e); }

        try {
            $("#filterDate").datepicker({ format: "yyyy-mm-dd", autoclose: true, orientation: "bottom" });
        } catch (e) { console.warn("datepicker init error:", e); }

        $("#filterHeader").off("click").on("click", function () {
            $("#filterCollapse").slideToggle(120);
            $("#filterIcon").toggleClass("ri-subtract-line ri-add-line");
        });
    }

    // Flash Helper
    function gsFlash(msg, type = "success", timeout = 3000) {
        var holder = document.getElementById("flash-holder");
        if (!holder) return;
        var div = document.createElement("div");
        div.className = "alert alert-" + type + " alert-dismissible fade show auto-dismiss";
        div.innerHTML = msg + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
        holder.prepend(div);
        setTimeout(function () {
            if (window.jQuery && jQuery.fn && jQuery.fn.alert) { try { jQuery(div).alert("close"); return; } catch (e) {} }
            if (div.parentNode) div.parentNode.removeChild(div);
        }, timeout);
    }
    window.gsFlash = gsFlash;

    // Date Normalize
    function normalizeFilterDate(s) {
        if (!s || typeof s !== "string") return "";
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (m) return s;
        var m2 = /^(\d{2})-(\d{2})-(\d{4})$/.exec(s);
        return m2 ? [m2[3], m2[2], m2[1]].join("-") : "";
    }
    // Today YMD
    function todayYmd() {
        var d = new Date();
        return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
    }
    // Shift Detect
    function detectShiftByNow() {
        var h = new Date().getHours();
        return h >= 6 && h < 16 ? "D" : h >= 16 && h < 22 ? "S" : "N";
    }
    // Value Format
    function fmt(v) { if (v === null || v === undefined || v === "") return "-"; if (typeof v === "number") return v.toFixed(2); return v; }
    // Time Trim
    function toHm(s) {
        if (!s) return "";
        var m = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(s));
        return m ? m[1] + ":" + m[2] : String(s).substring(0, 5);
    }
    // Date Render
    function formatDateTimeColumn(v, type, row) {
        if (v) return String(v);
        if (row && row.created_time) return String(row.created_time);
        return "-";
    }

    // Filter State
    function currentFilters() {
        return {
            date: normalizeFilterDate($("#filterDate").val()),
            shift: $("#shiftSelect").val() || "",
            product_type_id: $("#productSelectFilter").val() || "",
            _ts: Date.now(),
        };
    }
    // Default Set
    (function initFiltersDefaults() {
        var $d = $("#filterDate"), $s = $("#shiftSelect");
        if (!$d.val()) $d.val(todayYmd()).trigger("change");
        if (!$s.val()) $s.val(detectShiftByNow()).trigger("change");
    })();

    // Column Map
    var columns = [
        // Row Actions
        {
            data: null,
            orderable: false,
            searchable: false,
            width: 80,
            render: function (_, __, row) {
                var id = row.id || "";
                return [
                    '<div class="btn-group btn-group-sm" role="group">',
                    '<button type="button" class="btn btn-outline-warning ace-edit btn-sm mr-2" data-id="'+id+'"><i class="fas fa-edit"></i></button>',
                    '<button type="button" class="btn btn-outline-danger ace-del btn-sm" data-id="'+id+'"><i class="fas fa-trash"></i></button>',
                    "</div>",
                ].join("");
            },
            defaultContent: "",
        },
        // Data Number
        { data: "number", defaultContent: "" },

        // Data Fields
        { data: "date", render: formatDateTimeColumn, defaultContent: "" }, // 2
        { data: "shift", defaultContent: "" },                              // 3
        { data: "product_type_name", defaultContent: "-" },                 // 4
        { data: "sample_start", render: toHm, defaultContent: "" },         // 5
        { data: "sample_finish", render: toHm, defaultContent: "" },        // 6
        // MM Fields
        { data: "p", render: fmt, defaultContent: "" },                     // 7
        { data: "c", render: fmt, defaultContent: "" },                     // 8
        { data: "gt", render: fmt, defaultContent: "" },                    // 9
        { data: "cb_lab", render: fmt, defaultContent: "" },                // 10
        { data: "moisture", render: fmt, defaultContent: "" },              // 11
        { data: "machine_no", render: fmt, defaultContent: "" },            // 12
        { data: "bakunetsu", render: fmt, defaultContent: "" },             // 13
        { data: "ac", render: fmt, defaultContent: "" },                    // 14
        { data: "tc", render: fmt, defaultContent: "" },                    // 15
        { data: "vsd", render: fmt, defaultContent: "" },                   // 16
        { data: "ig", render: fmt, defaultContent: "" },                    // 17
        { data: "cb_weight", render: fmt, defaultContent: "" },             // 18
        { data: "tp50_weight", render: fmt, defaultContent: "" },           // 19
        { data: "ssi", render: fmt, defaultContent: "" },                   // 20
        { data: "most", render: fmt, defaultContent: "" },                  // 21
        // Additive Set
        { data: "dw29_vas", render: fmt, defaultContent: "" },              // 22
        { data: "dw29_debu", render: fmt, defaultContent: "" },             // 23
        { data: "dw31_vas", render: fmt, defaultContent: "" },              // 24
        { data: "dw31_id", render: fmt, defaultContent: "" },               // 25
        { data: "dw31_moldex", render: fmt, defaultContent: "" },           // 26
        { data: "dw31_sc", render: fmt, defaultContent: "" },               // 27
        // BC13 Set
        { data: "no_mix", render: fmt, defaultContent: "" },                // 28
        { data: "bc13_cb", render: fmt, defaultContent: "" },               // 29
        { data: "bc13_c", render: fmt, defaultContent: "" },                // 30
        { data: "bc13_m", render: fmt, defaultContent: "" },                // 31
    ];

    // Summary Rows
    var summaryManager = {
        ensureTfoot: function () {
            var $table = $("#dt-ace");
            var $tfoot = $table.find("tfoot#ace-foot");
            if (!$tfoot.length) $tfoot = $('<tfoot id="ace-foot"/>').appendTo($table);
            return $tfoot;
        },
        load: function () {
            if (!window.aceRoutes.summary) return;
            var f = currentFilters();
            $.get(window.aceRoutes.summary, { date: f.date, shift: f.shift, product_type_id: f.product_type_id })
                .done(function (res) {
                    var list = Array.isArray(res.summary) ? res.summary : [];
                    summaryManager.render(list);
                })
                .fail(function () { summaryManager.render([]); });
        },
        render: function (summary) {
            var $tfoot = this.ensureTfoot();
            var colIndex = {
                p: 7, c: 8, gt: 9, cb_lab: 10, moisture: 11, bakunetsu: 13,
                ac: 14, tc: 15, vsd: 16, ig: 17, cb_weight: 18, tp50_weight: 19,
                ssi: 20, dw29_vas: 22, dw29_debu: 23, dw31_vas: 24, dw31_id: 25,
                dw31_moldex: 26, dw31_sc: 27, bc13_cb: 29, bc13_c: 30, bc13_m: 31,
            };
            function makeRow(label, valuesMap) {
                var tds = '<td class="text-center font-weight-bold" colspan="7">' + label + "</td>";
                for (var i = 7; i < columns.length; i++) {
                    var val = valuesMap && valuesMap[i] != null ? valuesMap[i] : "";
                    tds += '<td class="text-center">' + val + "</td>";
                }
                return '<tr class="ace-summary-row">' + tds + "</tr>";
            }
            var rows = { min: {}, max: {}, avg: {}, judge: {} };
            summary.forEach(function (s) {
                var idx = colIndex[s.field]; if (idx == null) return;
                rows.min[idx]   = (s.min ?? "") === "" ? "" : s.min;
                rows.max[idx]   = (s.max ?? "") === "" ? "" : s.max;
                rows.avg[idx]   = (s.avg ?? "") === "" ? "" : s.avg;
                rows.judge[idx] = s.judge ? '<span class="' + (s.judge === "NG" ? "j-ng" : "j-ok") + '">' + s.judge + "</span>" : "";
            });
            $tfoot.html(
                makeRow("MIN", rows.min) +
                makeRow("MAX", rows.max) +
                makeRow("AVG", rows.avg) +
                makeRow("JUDGE", rows.judge)
            );
        },
    };

    // Table Init
    window.aceTable = $("#dt-ace").DataTable({
        serverSide: true,
        processing: true,
        responsive: false,
        lengthChange: true,
        scrollX: true,
        scrollCollapse: true,
        deferRender: true,
        pageLength: 25,

        // Sort Default
        order: [[2, "asc"], [1, "asc"]],

        ajax: {
            url: aceRoutes.data,
            type: "GET",
            data: function (d) {
                var f = currentFilters();
                d.date = f.date;
                d.shift = f.shift;
                d.product_type_id = f.product_type_id;
                d._ts = Date.now();
            },
            cache: false,
            error: function (xhr) {
                console.error("DT ajax error", xhr);
                gsFlash("Gagal memuat data.", "danger");
            },
        },
        columns: columns,
        columnDefs: [{ targets: "_all", className: "align-middle text-center" }],
        drawCallback: function () { summaryManager.load(); },
        initComplete: function () { summaryManager.load(); },
    });

    // Table Reload
    function reloadTable(cb) {
        if (window.aceTable) {
            window.aceTable.ajax.reload(function () { if (typeof cb === "function") cb(); }, false);
        }
    }

    // Filter Apply
    $("#btnSearch").on("click", function () {
        reloadTable(function () { gsFlash("Filter diterapkan.", "info"); });
    });

    // Filter Reset
    $("#btnRefresh").on("click", function () {
        $("#filterDate").val(todayYmd());
        $("#shiftSelect").val(detectShiftByNow()).trigger("change");
        $("#productSelectFilter").val("").trigger("change");
        reloadTable(function () { gsFlash("Filter direset.", "secondary"); });
    });

    // Excel Export
    $("#btnExport").on("click", function () {
        if (!aceRoutes.export) return;
        var q = $.param(currentFilters());
        window.location.href = aceRoutes.export + (q ? "?" + q : "");
        gsFlash("Menyiapkan file Excel…", "info");
    });

    // Modal Open
    $(document).on("click", '[data-target="#modal-ace"]', function () {
        var form = document.getElementById("aceForm");
        if (form && form.reset) form.reset();
        $("#ace_mode").val("create");
        $("#ace_id").val("");
        $("#mDate").val(todayYmd());
        $("#mShift").val(detectShiftByNow());
        $("#aceFormAlert").addClass("d-none").empty();

        var $ps = $("#productSelectModal");
        if ($ps.data("select2")) $ps.empty().trigger("change");
        $ps.val(null).trigger("change");
        $ps.removeData("selected-id").removeData("selected-text");
        $ps.attr("data-selected-id", "").attr("data-selected-text", "");
        $("#productTypeName").val("");
    });

    // Edit Click
    $("#dt-ace").on("click", ".ace-edit", function () {
        var id = $(this).data("id"); if (!id) return;
        $.get(aceRoutes.base + "/" + id)
            .done(function (row) {
                $("#aceFormAlert").addClass("d-none").empty();
                $("#ace_mode").val("update");
                $("#ace_id").val(row.id || "");
                fillForm(row);

                var $ps = $("#productSelectModal");
                if (row.product_type_id && row.product_type_name) {
                    if ($ps.data("select2")) $ps.empty();
                    var opt = new Option(row.product_type_name, row.product_type_id, true, true);
                    $ps.append(opt).trigger("change");
                    $ps.data("selected-id", row.product_type_id);
                    $ps.data("selected-text", row.product_type_name);
                    $ps.attr("data-selected-id", row.product_type_id);
                    $ps.attr("data-selected-text", row.product_type_name);
                    $("#productTypeName").val(row.product_type_name);
                } else {
                    if ($ps.data("select2")) $ps.empty().trigger("change");
                    $ps.val(null).trigger("change");
                    $ps.removeData("selected-id").removeData("selected-text");
                    $ps.attr("data-selected-id", "").attr("data-selected-text", "");
                    $("#productTypeName").val("");
                }

                $("#modal-ace").modal("show");
            })
            .fail(function (xhr) {
                gsFlash("Gagal mengambil data untuk edit.", "danger");
                console.error(xhr.responseText || xhr.statusText);
            });
    });

    // Delete Flow
    var deleteId = null;
    $("#dt-ace").on("click", ".ace-del", function () {
        deleteId = $(this).data("id") || null;
        $("#confirmDeleteModal").modal("show");
    });
    $("#confirmDeleteYes").on("click", function () {
        if (!deleteId) return;
        $.ajax({
            url: aceRoutes.base + "/" + deleteId,
            type: "DELETE",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        })
            .done(function () {
                $("#confirmDeleteModal").modal("hide");
                reloadTable(function () { gsFlash("Data berhasil dihapus.", "success"); });
            })
            .fail(function (xhr) {
                gsFlash("Hapus data gagal.", "danger");
                console.error(xhr.responseText || xhr.statusText);
            });
    });

    // Form Submit
    $("#aceForm").on("submit", function (e) {
        e.preventDefault();
        var mode = $("#ace_mode").val(), id = $("#ace_id").val();

        $("#mStart").val(toHm($("#mStart").val()));
        $("#mFinish").val(toHm($("#mFinish").val()));

        var url = aceRoutes.store, method = "POST";
        if (mode === "update" && id) { url = aceRoutes.base + "/" + id; method = "POST"; }
        var fd = new FormData(this);
        if (mode === "update") fd.append("_method", "PUT");

        var $btn = $("#aceSubmitBtn");
        $btn.prop("disabled", true).data("orig", $btn.html())
            .html('<span class="spinner-border spinner-border-sm mr-1"></span> Saving...');

        $.ajax({
            url, type: method, data: fd, processData: false, contentType: false,
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        })
            .done(function () {
                $("#modal-ace").modal("hide");
                reloadTable(function () {
                    gsFlash(mode === "update" ? "Data berhasil diperbarui." : "Data berhasil disimpan.", "success");
                });
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || "Simpan data gagal.";
                $("#aceFormAlert").removeClass("d-none").text(msg);
                gsFlash(msg, "danger");
                console.error(xhr.responseText || xhr.statusText);
            })
            .always(function () {
                $btn.prop("disabled", false).html($btn.data("orig") || "Submit");
            });
    });

    // Form Filler
    function fillForm(data) {
        if (!data) return;
        Object.keys(data).forEach(function (k) {
            var $f = $("#m_" + k);
            if ($f.length) $f.val(data[k]);
        });
        $("#mStart").val(data.sample_start || "");
        $("#mFinish").val(data.sample_finish || "");
        $("#mNoMix").val(data.no_mix || "");

        if (data.date) { $("#mDate").val(String(data.date).substring(0, 10)); }
        if (data.shift) $("#mShift").val(data.shift);
    }

    $(function () { initPageUI(); });
})();
