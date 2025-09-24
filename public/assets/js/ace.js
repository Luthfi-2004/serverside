// public/assets/js/ace.js
(function () {
    var $ = window.jQuery;
    if (!window.aceRoutes) {
        console.error("aceRoutes missing. Define it in Blade before loading ace.js");
        return;
    }

    /* ================= helpers ================= */
    function normalizeFilterDate(s) {
        if (!s || typeof s !== "string") return "";
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (m) return s;
        var m2 = /^(\d{2})-(\d{2})-(\d{4})$/.exec(s);
        return m2 ? [m2[3], m2[2], m2[1]].join("-") : "";
    }
    function todayYmd() {
        var d = new Date();
        return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
    }
    function detectShiftByNow() {
        var h = new Date().getHours();
        return h >= 6 && h < 16 ? "D" : h >= 16 && h < 22 ? "S" : "N";
    }
    function fmt(v) { if (v === null || v === undefined || v === "") return "-"; if (typeof v === "number") return v.toFixed(2); return v; }
    function fmtInt(v) { if (v === null || v === undefined || v === "") return "-"; var n = parseInt(v, 10); return isNaN(n) ? "-" : n; }
    function toHm(s) { if (!s) return ""; var m = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(s)); return m ? m[1] + ":" + m[2] : String(s).substring(0, 5); }
    function toYmdHm(s) {
        if (!s) return "-";
        var m = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/.exec(String(s));
        return m ? m[1] + " " + m[2] : String(s).replace("T", " ").substring(0, 16);
    }
    function currentFilters() {
        return {
            date: normalizeFilterDate($("#filterDate").val()),
            shift: $("#shiftSelect").val() || "",
            product_type_id: $("#productSelect").val() || "",
        };
    }
    function isEmptyVal(x) {
        if (x === null || x === undefined) return true;
        var s = String(x).trim();
        return s === "" || s === "-" || s === "–";
    }

    (function initFilters() {
        var $d = $("#filterDate"), $s = $("#shiftSelect");
        if (!$d.val()) $d.val(todayYmd()).trigger("change");
        if (!$s.val()) $s.val(detectShiftByNow()).trigger("change");
    })();

    /* ================= columns ================= */
    var columns = [
        {
            data: null, orderable: false, searchable: false, width: 80,
            render: function (_, __, row) {
                var id = row.id || "";
                return [
                    '<div class="btn-group btn-group-sm" role="group">',
                    '<button type="button" class="btn btn-outline-warning ace-edit btn-sm mr-2" data-id="', id, '"><i class="fas fa-edit"></i></button>',
                    '<button type="button" class="btn btn-outline-danger ace-del btn-sm" data-id="', id, '"><i class="fas fa-trash"></i></button>',
                    "</div>",
                ].join("");
            },
            defaultContent: ""
        },
        { data: "number", render: fmtInt, defaultContent: "" },
        {
            data: "date",
            render: function (v, __, row) {
                var dt = row.created_at || row.updated_at || v || "";
                return /[ T]\d{2}:\d{2}/.test(String(dt)) ? toYmdHm(dt) : (dt ? String(dt).substring(0, 10) : "-");
            },
            defaultContent: ""
        },
        { data: "shift", defaultContent: "" },
        { data: "product_type_name", defaultContent: "-" },
        { data: "sample_start", render: function (v) { return v ? toHm(v) : "-"; }, defaultContent: "" },
        { data: "sample_finish", render: function (v) { return v ? toHm(v) : "-"; }, defaultContent: "" },
        { data: "p", render: fmt, defaultContent: "" },
        { data: "c", render: fmt, defaultContent: "" },
        { data: "gt", render: fmt, defaultContent: "" },
        { data: "cb_lab", render: fmt, defaultContent: "" },
        { data: "moisture", render: fmt, defaultContent: "" },
        { data: "machine_no", render: fmt, defaultContent: "" },
        { data: "bakunetsu", render: fmt, defaultContent: "" },
        { data: "ac", render: fmt, defaultContent: "" },
        { data: "tc", render: fmt, defaultContent: "" },
        { data: "vsd", render: fmt, defaultContent: "" },
        { data: "ig", render: fmt, defaultContent: "" },
        { data: "cb_weight", render: fmt, defaultContent: "" },
        { data: "tp50_weight", render: fmt, defaultContent: "" },
        { data: "ssi", render: fmt, defaultContent: "" },
        { data: "dw29_vas", render: fmt, defaultContent: "" },
        { data: "dw29_debu", render: fmt, defaultContent: "" },
        { data: "dw31_vas", render: fmt, defaultContent: "" },
        { data: "dw31_id", render: fmt, defaultContent: "" },
        { data: "dw31_moldex", render: fmt, defaultContent: "" },
        { data: "dw31_sc", render: fmt, defaultContent: "" },
        { data: "no_mix", render: fmt, defaultContent: "" },
        { data: "bc13_cb", render: fmt, defaultContent: "" },
        { data: "bc13_c", render: fmt, defaultContent: "" },
        { data: "bc13_m", render: fmt, defaultContent: "" },
    ];

    /* ================= footer summary (ala Greensand, super-stabil) ================= */
    var START_DATA_COL = 7; // 0..6 = Action, Number, Date, Shift, Product, Start, Finish

    function getKeyOrder() {
        var order = [];
        for (var i = START_DATA_COL; i < columns.length; i++) {
            var k = columns[i] && columns[i].data;
            if (typeof k === "string") order.push(k);
        }
        return order;
    }
    function totalColumnCount() {
        return Array.isArray(columns) ? columns.length : $("#dt-ace thead th").length;
    }

    function buildFooterStructureOnce() {
        var $t = $("#dt-ace"), $tfoot = $t.find("tfoot");
        if (!$tfoot.length) $tfoot = $("<tfoot/>").appendTo($t);
        if ($tfoot.find("tr.ace-summary-row").length) {
            $tfoot.removeClass("d-none");
            return $tfoot;
        }
        var totalCols = totalColumnCount();
        $tfoot.empty();
        ["MIN", "MAX", "AVG", "JUDGE"].forEach(function (L) {
            var $tr = $("<tr/>").addClass("ace-summary-row ace-summary-" + L.toLowerCase());
            $tr.append($("<td/>").addClass("ace-foot-label").attr("colspan", START_DATA_COL).text(L));
            for (var i = START_DATA_COL; i < totalCols; i++) $tr.append($("<td/>"));
            $tfoot.append($tr);
        });
        $tfoot.removeClass("d-none");
        return $tfoot;
    }

    // Defender: kalau ada lib yang mindahin/melepas tfoot saat redraw, pasang lagi.
    var footObserver = null;
    function ensureFooterAttached() {
        var $t = $("#dt-ace");
        var $tfoot = $t.find("tfoot");
        if (!$tfoot.length || !$tfoot.find("tr.ace-summary-row").length) {
            $tfoot = buildFooterStructureOnce();
        }
        if (!$t.children("tfoot").length) $t.append($tfoot);

        if (!footObserver) {
            footObserver = new MutationObserver(function () {
                var $t2 = $("#dt-ace");
                if (!$t2.children("tfoot").length) {
                    $t2.append($tfoot);
                }
            });
            footObserver.observe($t[0], { childList: true });
        }
        return $tfoot;
    }

    function adaptSummaryResponse(res, keyOrder) {
        var out = { min: {}, max: {}, avg: {}, judge: {} };
        if (res && Array.isArray(res.summary)) {
            var map = {};
            res.summary.forEach(function (s) { if (s && s.field) map[s.field] = s; });
            keyOrder.forEach(function (k) {
                var s = map[k] || {};
                out.min[k]   = (s.min   != null ? s.min   : "");
                out.max[k]   = (s.max   != null ? s.max   : "");
                out.avg[k]   = (s.avg   != null ? s.avg   : "");
                out.judge[k] = (s.judge != null ? s.judge : "");
            });
            return out;
        }
        function pickKeys(raw) {
            var obj = {};
            if (!raw) return obj;
            if (Array.isArray(raw)) {
                for (var i = 0; i < Math.min(raw.length, keyOrder.length); i++) obj[keyOrder[i]] = raw[i];
                return obj;
            }
            if (typeof raw === "object") {
                keyOrder.forEach(function (k) { if (k in raw) obj[k] = raw[k]; });
                return obj;
            }
            return obj;
        }
        var rows = (res && res.rows) || {};
        out.min   = pickKeys(rows.min);
        out.max   = pickKeys(rows.max);
        out.avg   = pickKeys(rows.avg);
        out.judge = pickKeys(rows.judge);
        return out;
    }

    function buildJudgeMask(rowsObj, present, keyOrder) {
        var mask = {};
        keyOrder.forEach(function (k) {
            if (present && typeof present[k] !== "undefined") {
                mask[k] = !!present[k];
            } else {
                var has =
                    (rowsObj.min && !isEmptyVal(rowsObj.min[k])) ||
                    (rowsObj.max && !isEmptyVal(rowsObj.max[k])) ||
                    (rowsObj.avg && !isEmptyVal(rowsObj.avg[k]));
                mask[k] = !!has;
            }
        });
        return mask;
    }

    function renderFooterKeyed($row, objValues, isJudgeRow, judgeMask, keyOrder) {
        var tds = $row.find("td");
        if (!tds.length) return;
        for (var i = 1; i < tds.length; i++) $(tds[i]).html("").removeClass("j-ok j-ng");

        keyOrder.forEach(function (k, idx) {
            var cell = tds[1 + idx];
            if (!cell) return;
            var v = (objValues && Object.prototype.hasOwnProperty.call(objValues, k)) ? objValues[k] : "";
            if (isJudgeRow && judgeMask && judgeMask[k] === false) v = "";
            $(cell).html(v);
            if (isJudgeRow) {
                var s = String(v);
                if (s === "OK" || /text-success/.test(s)) $(cell).addClass("j-ok");
                else if (s === "NG" || /text-danger/.test(s)) $(cell).addClass("j-ng");
            }
        });
    }

    // Panggil ini ketika butuh isi summary (dipakai di footerCallback)
    function fillSummaryIntoFooter() {
        if (!aceRoutes.summary) return;
        var $tfoot = ensureFooterAttached();
        var keyOrder = getKeyOrder();

        $.get(aceRoutes.summary, currentFilters())
            .done(function (res) {
                var norm = adaptSummaryResponse(res || {}, keyOrder);
                var mask = buildJudgeMask(norm, (res && res.present) || null, keyOrder);
                renderFooterKeyed($tfoot.find("tr.ace-summary-min"),   norm.min,   false, null, keyOrder);
                renderFooterKeyed($tfoot.find("tr.ace-summary-max"),   norm.max,   false, null, keyOrder);
                renderFooterKeyed($tfoot.find("tr.ace-summary-avg"),   norm.avg,   false, null, keyOrder);
                renderFooterKeyed($tfoot.find("tr.ace-summary-judge"), norm.judge, true,  mask, keyOrder);
            })
            .fail(function (xhr) {
                console.error("summary fail", xhr && (xhr.responseText || xhr.statusText));
                $("#dt-ace tfoot").removeClass("d-none");
            });
    }

    /* ================= DataTable init ================= */
    window.aceTable = $("#dt-ace").DataTable({
        serverSide: true,
        processing: true,
        responsive: false,
        lengthChange: true,
        scrollX: true,
        scrollCollapse: true,
        pageLength: 25,
        order: [[2, "desc"]],
        ajax: {
            url: aceRoutes.data,
            type: "GET",
            data: function (d) {
                var f = currentFilters();
                d.date = f.date;
                d.shift = f.shift;
                d.product_type_id = f.product_type_id;
            },
            error: function (xhr) { console.error("DT ajax error", xhr); },
        },
        columns: columns,
        columnDefs: [{ targets: "_all", className: "align-middle text-center" }],
        initComplete: function () {
            buildFooterStructureOnce();   // buat sekali
            ensureFooterAttached();       // kunci di table
            fillSummaryIntoFooter();      // isi awal
        },
        footerCallback: function () {
            // dipanggil setiap draw; aman buat isi tanpa rebuild struktur
            ensureFooterAttached();
            fillSummaryIntoFooter();
        }
        // catatan: tidak perlu drawCallback lagi
    });

    /* ================= actions ================= */
    function reloadTable() {
        if (window.aceTable) window.aceTable.ajax.reload(null, false);
        // footerCallback akan jalan otomatis pas redraw → summary terisi
    }

    $("#btnSearch").on("click", reloadTable);
    $("#btnRefresh").on("click", function () {
        $("#filterDate").val(todayYmd());
        $("#shiftSelect").val(detectShiftByNow()).trigger("change");
        $("#productSelect").val("").trigger("change");
        reloadTable();
    });
    $("#btnExport").on("click", function () {
        if (!aceRoutes.export) return;
        var q = $.param(currentFilters());
        window.location.href = aceRoutes.export + (q ? "?" + q : "");
    });

    $(document).on("click", '[data-target="#modal-ace"]', function () {
        $("#aceForm")[0].reset();
        $("#ace_mode").val("create");
        $("#ace_id").val("");
        $("#mDate").val(todayYmd());
        $("#mShift").val(detectShiftByNow());
        $("#aceFormAlert").addClass("d-none").empty();
    });

    $("#dt-ace").on("click", ".ace-edit", function () {
        var id = $(this).data("id");
        if (!id) return;
        $.get(aceRoutes.base + "/" + id)
            .done(function (row) {
                $("#aceFormAlert").addClass("d-none").empty();
                $("#ace_mode").val("update");
                $("#ace_id").val(row.id || "");
                fillForm(row);
                $("#modal-ace").modal("show");
            })
            .fail(function (xhr) {
                alert("Failed to fetch data");
                console.error(xhr.responseText || xhr.statusText);
            });
    });

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
                reloadTable();
            })
            .fail(function (xhr) {
                alert("Delete failed");
                console.error(xhr.responseText || xhr.statusText);
            });
    });

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
            url: url, type: method, data: fd, processData: false, contentType: false,
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        })
            .done(function () {
                $("#modal-ace").modal("hide");
                reloadTable();
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || "Save failed";
                $("#aceFormAlert").removeClass("d-none").text(msg);
                console.error(xhr.responseText || xhr.statusText);
            })
            .always(function () {
                $btn.prop("disabled", false).html($btn.data("orig") || "Submit");
            });
    });
})();
