// public/assets/js/ace.js
(function () {
    var $ = window.jQuery;
    if (!window.aceRoutes) {
        console.error(
            "aceRoutes missing. Define it in Blade before loading ace.js"
        );
        return;
    }

    /* =========== helpers =========== */
    function normalizeFilterDate(s) {
        if (!s || typeof s !== "string") return "";
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (m) return s;
        var m2 = /^(\d{2})-(\d{2})-(\d{4})$/.exec(s);
        return m2 ? [m2[3], m2[2], m2[1]].join("-") : "";
    }
    function todayYmd() {
        var d = new Date();
        return (
            d.getFullYear() +
            "-" +
            String(d.getMonth() + 1).padStart(2, "0") +
            "-" +
            String(d.getDate()).padStart(2, "0")
        );
    }
    function detectShiftByNow() {
        var h = new Date().getHours();
        return h >= 6 && h < 16 ? "D" : h >= 16 && h < 22 ? "S" : "N";
    }
    function fmt(v) {
        if (v === null || v === undefined || v === "") return "-";
        if (typeof v === "number") return v.toFixed(2);
        return v;
    }
    function fmtInt(v) {
        if (v === null || v === undefined || v === "") return "-";
        var n = parseInt(v, 10);
        return isNaN(n) ? "-" : n;
    }
    function toHm(s) {
        if (!s) return "";
        var m = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(s));
        return m ? m[1] + ":" + m[2] : String(s).substring(0, 5);
    }
    function toYmdHm(s) {
        if (!s) return "-";
        var m = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/.exec(String(s));
        return m
            ? m[1] + " " + m[2]
            : String(s).replace("T", " ").substring(0, 16);
    }
    function reloadTable() {
        if (window.aceTable) window.aceTable.ajax.reload(null, false);
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

    /* =========== form =========== */
    function fillForm(row) {
        $("#ace_id").val(row.id || "");
        $("#ace_mode").val(row.id ? "update" : "create");
        $("#mDate").val((row.date || "").substring(0, 10));
        $("#mShift").val(row.shift || "");
        $("#mProductName").val(row.product_type_name || "");
        $("#mNoMix").val(row.no_mix || "");
        $("#mStart").val(toHm(row.sample_start || ""));
        $("#mFinish").val(toHm(row.sample_finish || ""));
        [
            "p",
            "c",
            "gt",
            "cb_lab",
            "moisture",
            "machine_no",
            "bakunetsu",
            "ac",
            "tc",
            "vsd",
            "ig",
            "cb_weight",
            "tp50_weight",
            "ssi",
            "dw29_vas",
            "dw29_debu",
            "dw31_vas",
            "dw31_id",
            "dw31_moldex",
            "dw31_sc",
            "bc13_cb",
            "bc13_c",
            "bc13_m",
        ].forEach(function (f) {
            $("#m_" + f).val(row[f] != null ? row[f] : "");
        });
    }
    function collectForm() {
        return new FormData(document.getElementById("aceForm"));
    }
    function setSubmitting(btn, on) {
        var $b = $(btn);
        if (on) {
            $b.prop("disabled", true)
                .data("orig", $b.html())
                .html(
                    '<span class="spinner-border spinner-border-sm mr-1"></span> Saving...'
                );
        } else {
            $b.prop("disabled", false).html($b.data("orig") || "Submit");
        }
    }
    function showAlert(msg) {
        $("#aceFormAlert")
            .removeClass("d-none")
            .text(msg || "Validation error");
    }
    function clearAlert() {
        $("#aceFormAlert").addClass("d-none").empty();
    }

    (function () {
        var $d = $("#filterDate"),
            $s = $("#shiftSelect");
        if (!$d.val()) $d.val(todayYmd()).trigger("change");
        if (!$s.val()) $s.val(detectShiftByNow()).trigger("change");
    })();

    /* =========== columns =========== */
    var columns = [
        {
            data: null,
            orderable: false,
            searchable: false,
            width: 80,
            render: function (_, __, row) {
                var id = row.id || "";
                return [
                    '<div class="btn-group btn-group-sm" role="group">',
                    '<button type="button" class="btn btn-outline-warning ace-edit btn-sm mr-2" data-id="',
                    id,
                    '"><i class="fas fa-edit"></i></button>',
                    '<button type="button" class="btn btn-outline-danger ace-del btn-sm" data-id="',
                    id,
                    '"><i class="fas fa-trash"></i></button>',
                    "</div>",
                ].join("");
            },
        },
        { data: "number", render: fmtInt },
        {
            data: "date",
            render: function (v, __, row) {
                var dt = row.created_at || row.updated_at || v || "";
                return /[ T]\d{2}:\d{2}/.test(String(dt))
                    ? toYmdHm(dt)
                    : dt
                    ? String(dt).substring(0, 10)
                    : "-";
            },
        },
        { data: "shift" },
        { data: "product_type_name", defaultContent: "-" },
        {
            data: "sample_start",
            render: function (v) {
                return v ? toHm(v) : "-";
            },
        },
        {
            data: "sample_finish",
            render: function (v) {
                return v ? toHm(v) : "-";
            },
        },
        { data: "p", render: fmt },
        { data: "c", render: fmt },
        { data: "gt", render: fmt },
        { data: "cb_lab", render: fmt },
        { data: "moisture", render: fmt },
        { data: "machine_no", render: fmt },
        { data: "bakunetsu", render: fmt },
        { data: "ac", render: fmt },
        { data: "tc", render: fmt },
        { data: "vsd", render: fmt },
        { data: "ig", render: fmt },
        { data: "cb_weight", render: fmt },
        { data: "tp50_weight", render: fmt },
        { data: "ssi", render: fmt },
        { data: "dw29_vas", render: fmt },
        { data: "dw29_debu", render: fmt },
        { data: "dw31_vas", render: fmt },
        { data: "dw31_id", render: fmt },
        { data: "dw31_moldex", render: fmt },
        { data: "dw31_sc", render: fmt },
        { data: "no_mix", render: fmt },
        { data: "bc13_cb", render: fmt },
        { data: "bc13_c", render: fmt },
        { data: "bc13_m", render: fmt },
    ];

    /* =========== footer summary (stabil & anti-geser) =========== */
    var START_DATA_COL = 7; // kolom P mulai index 7 (Action..Finish = 0..6)

    // KEY_ORDER otomatis mengikuti konfigurasi kolom dari index START_DATA_COL ke akhir
    function getKeyOrder() {
        var order = [];
        for (var i = START_DATA_COL; i < columns.length; i++) {
            var k = columns[i] && columns[i].data;
            if (typeof k === "string") order.push(k);
        }
        return order;
    }

    function ensureFooterRows() {
        var $t = $("#dt-ace"),
            $tfoot = $t.find("tfoot");
        if (!$tfoot.length) $tfoot = $("<tfoot/>").appendTo($t);
        $tfoot.empty();
        var totalCols = $t.find("thead th").length;
        ["MIN", "MAX", "AVG", "JUDGE"].forEach(function (L) {
            var $tr = $("<tr/>").addClass(
                "ace-summary-row ace-summary-" + L.toLowerCase()
            );
            $tr.append(
                $("<td/>")
                    .addClass("ace-foot-label")
                    .attr("colspan", START_DATA_COL)
                    .text(L)
            );
            for (var i = START_DATA_COL; i < totalCols; i++)
                $tr.append($("<td/>"));
            $tfoot.append($tr);
        });
        $tfoot.removeClass("d-none");
    }

    // Normalisasi rows.{min,max,avg,judge} jadi object keyed by field-name
    function normalizeOne(kind, raw, keyOrder) {
        var out = {};
        if (!raw) return out;

        // Jika array: bisa absolut (panjang = total kolom) atau relatif (mulai dari P)
        if (Array.isArray(raw)) {
            // relatif: panjang = keyOrder.length
            if (raw.length === keyOrder.length) {
                for (var i = 0; i < keyOrder.length; i++)
                    out[keyOrder[i]] = raw[i];
            } else {
                // fallback: map secukupnya dari awal keyOrder
                for (var j = 0; j < Math.min(raw.length, keyOrder.length); j++)
                    out[keyOrder[j]] = raw[j];
            }
            return out;
        }

        // Jika object: ambil hanya key yang ada di keyOrder
        if (typeof raw === "object") {
            keyOrder.forEach(function (k) {
                if (k in raw) out[k] = raw[k];
            });
            return out;
        }

        return out;
    }

    function normalizeRows(rows, keyOrder) {
        rows = rows || {};
        return {
            min: normalizeOne("min", rows.min, keyOrder),
            max: normalizeOne("max", rows.max, keyOrder),
            avg: normalizeOne("avg", rows.avg, keyOrder),
            judge: normalizeOne("judge", rows.judge, keyOrder),
        };
    }

    // Build mask untuk kolom yang sudah punya data (supaya JUDGE kosong kalau belum diisi)
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

    function renderFooterKeyed(
        $row,
        objValues,
        label,
        overall,
        judgeMask,
        keyOrder
    ) {
        var tds = $row.find("td");
        if (!tds.length) return;
        if (label) {
            var txt = label;
            if ($row.hasClass("ace-summary-judge") && overall)
                txt += " (OVERALL: " + overall + ")";
            $(tds[0]).text(txt);
        }
        if ($row.hasClass("ace-summary-judge")) tds.removeClass("j-ok j-ng");

        // clear isi dulu
        for (var i = 1; i < tds.length; i++) $(tds[i]).html("");

        // render sesuai urutan keyOrder → cell ke- (1 + index)
        keyOrder.forEach(function (k, idx) {
            var cell = tds[1 + idx];
            if (!cell) return;
            var v =
                objValues && Object.prototype.hasOwnProperty.call(objValues, k)
                    ? objValues[k]
                    : "";
            if (
                $row.hasClass("ace-summary-judge") &&
                judgeMask &&
                judgeMask[k] === false
            ) {
                v = ""; // belum ada data → kosong
            }
            $(cell).html(v);
            if ($row.hasClass("ace-summary-judge")) {
                if (v === "OK" || /text-success/.test(String(v)))
                    $(cell).addClass("j-ok");
                else if (v === "NG" || /text-danger/.test(String(v)))
                    $(cell).addClass("j-ng");
            }
        });
    }

    function loadSummary() {
        if (!aceRoutes.summary) return;
        ensureFooterRows();

        $.get(aceRoutes.summary, currentFilters())
            .done(function (res) {
                var $tfoot = $("#dt-ace tfoot");
                var keyOrder = getKeyOrder();

                // kalau server nggak kirim rows sama sekali, tetap tampil labelnya (kosong)
                var norm = normalizeRows(res && res.rows, keyOrder);
                var mask = buildJudgeMask(norm, res && res.present, keyOrder);

                renderFooterKeyed(
                    $tfoot.find("tr.ace-summary-min"),
                    norm.min,
                    "MIN",
                    null,
                    null,
                    keyOrder
                );
                renderFooterKeyed(
                    $tfoot.find("tr.ace-summary-max"),
                    norm.max,
                    "MAX",
                    null,
                    null,
                    keyOrder
                );
                renderFooterKeyed(
                    $tfoot.find("tr.ace-summary-avg"),
                    norm.avg,
                    "AVG",
                    null,
                    null,
                    keyOrder
                );
                renderFooterKeyed(
                    $tfoot.find("tr.ace-summary-judge"),
                    norm.judge,
                    "JUDGE",
                    null,
                    null,
                    keyOrder
                );
            })
            .fail(function () {
                $("#dt-ace tfoot").removeClass(
                    "d-none"
                ); /* tetap tampilin label */
            });
    }

    /* =========== DataTable init =========== */
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
            error: function (xhr) {
                console.error("DT ajax error", xhr);
            },
        },
        columns: columns,
        columnDefs: [
            { targets: "_all", className: "align-middle text-center" },
        ],
        initComplete: function () {
            ensureFooterRows();
        },
        drawCallback: function () {
            loadSummary();
        },
    });

    /* =========== actions =========== */
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
        clearAlert();
        $("#mDate").val(todayYmd());
        $("#mShift").val(detectShiftByNow());
    });

    $("#dt-ace").on("click", ".ace-edit", function () {
        var id = $(this).data("id");
        if (!id) return;
        $.get(aceRoutes.base + "/" + id)
            .done(function (row) {
                clearAlert();
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
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
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
        clearAlert();
        var mode = $("#ace_mode").val(),
            id = $("#ace_id").val();
        $("#mStart").val(toHm($("#mStart").val()));
        $("#mFinish").val(toHm($("#mFinish").val()));
        var url = aceRoutes.store,
            method = "POST";
        if (mode === "update" && id) {
            url = aceRoutes.base + "/" + id;
            method = "POST";
        }
        var fd = collectForm();
        if (mode === "update") fd.append("_method", "PUT");
        var $btn = $("#aceSubmitBtn");
        setSubmitting($btn, true);
        $.ajax({
            url: url,
            type: method,
            data: fd,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        })
            .done(function () {
                $("#modal-ace").modal("hide");
                reloadTable();
            })
            .fail(function (xhr) {
                var msg = "Save failed";
                if (xhr.responseJSON && xhr.responseJSON.message)
                    msg = xhr.responseJSON.message;
                showAlert(msg);
                console.error(xhr.responseText || xhr.statusText);
            })
            .always(function () {
                setSubmitting($btn, false);
            });
    });
})();
