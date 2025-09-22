// public/assets/js/ace.js
(function () {
    var $ = window.jQuery;

    // guard
    if (!window.aceRoutes) {
        console.error(
            "aceRoutes missing. Define it in Blade before loading ace.js"
        );
        return;
    }

    // ===== helpers =====

    // robust parse: support "yyyy-mm-dd" OR "dd-mm-yyyy"
    function normalizeFilterDate(s) {
        if (!s || typeof s !== "string") return "";
        // yyyy-mm-dd
        var m1 = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (m1) return s;
        // dd-mm-yyyy -> yyyy-mm-dd
        var m2 = /^(\d{2})-(\d{2})-(\d{4})$/.exec(s);
        if (m2) return [m2[3], m2[2], m2[1]].join("-");
        return "";
    }

    // today (yyyy-mm-dd)
    function todayYmd() {
        var d = new Date();
        var dd = String(d.getDate()).padStart(2, "0");
        var mm = String(d.getMonth() + 1).padStart(2, "0");
        var yyyy = d.getFullYear();
        return yyyy + "-" + mm + "-" + dd;
    }

    // shift
    function detectShiftByNow() {
        var hh = new Date().getHours();
        if (hh >= 6 && hh < 16) return "D";
        if (hh >= 16 && hh < 22) return "S";
        return "N";
    }

    // format number or dash
    function fmt(v) {
        if (v === null || v === undefined || v === "") return "-";
        if (typeof v === "number") return v.toFixed(2);
        return v;
    }

    // integer (tanpa .00)
    function fmtInt(v) {
        if (v === null || v === undefined || v === "") return "-";
        var n = parseInt(v, 10);
        return isNaN(n) ? "-" : n;
    }

    // "HH:MM" dari "HH:MM" / "HH:MM:SS"
    function toHm(s) {
        if (!s) return "";
        var m = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(s));
        if (m) return m[1] + ":" + m[2];
        return String(s).substring(0, 5);
    }

    // "YYYY-MM-DD HH:MM" dari ISO/SQL datetime
    function toYmdHm(s) {
        if (!s) return "-";
        // cocokkan "yyyy-mm-ddThh:mm" atau "yyyy-mm-dd hh:mm"
        var m = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/.exec(String(s));
        if (m) return m[1] + " " + m[2];
        // fallback: potong aman
        return String(s).replace("T", " ").substring(0, 16);
    }

    // reload datatable
    function reloadTable() {
        if (window.aceTable) window.aceTable.ajax.reload(null, false);
    }

    // filters (pakai yyyy-mm-dd)
    function currentFilters() {
        return {
            date: normalizeFilterDate($("#filterDate").val()),
            shift: $("#shiftSelect").val() || "",
            product_type_id: $("#productSelect").val() || "",
        };
    }

    // ===== form fill =====
    function fillForm(row) {
        $("#ace_id").val(row.id || "");
        $("#ace_mode").val(row.id ? "update" : "create");
        $("#mDate").val((row.date || "").substring(0, 10));
        $("#mShift").val(row.shift || "");
        $("#mProductName").val(row.product_type_name || "");
        $("#mNoMix").val(row.no_mix || "");
        $("#mStart").val(toHm(row.sample_start || ""));
        $("#mFinish").val(toHm(row.sample_finish || ""));

        var fields = [
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
        ];
        fields.forEach(function (f) {
            $("#m_" + f).val(row[f] != null ? row[f] : "");
        });
    }

    // formdata
    function collectForm() {
        return new FormData(document.getElementById("aceForm"));
    }

    // submit button state
    function setSubmitting(btn, on) {
        var $btn = $(btn);
        if (on) {
            $btn.prop("disabled", true).data("orig", $btn.html());
            $btn.html(
                '<span class="spinner-border spinner-border-sm mr-1"></span> Saving...'
            );
        } else {
            $btn.prop("disabled", false).html($btn.data("orig") || "Submit");
        }
    }

    // alerts
    function showAlert(msg) {
        $("#aceFormAlert")
            .removeClass("d-none")
            .text(msg || "Validation error");
    }
    function clearAlert() {
        $("#aceFormAlert").addClass("d-none").empty();
    }

    // ===== defaults =====
    (function initDefaultFilters() {
        var $fDate = $("#filterDate");
        var $fShift = $("#shiftSelect");
        if (!$fDate.val()) $fDate.val(todayYmd()).trigger("change");
        if (!$fShift.val()) $fShift.val(detectShiftByNow()).trigger("change");
    })();

    // ===== columns =====
    // Catatan kolom Date: kita coba pakai created_at / updated_at jika ada,
    // supaya bisa tampil "tanggal + jam submit". Fallback ke row.date.
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
            data: "date", // penting: biarkan 'date' untuk server-side
            render: function (v, __, row) {
                var dt = row.created_at || row.updated_at || v || "";
                if (/[ T]\d{2}:\d{2}/.test(String(dt))) return toYmdHm(dt);
                return dt ? String(dt).substring(0, 10) : "-";
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

    // ===== DataTable init =====
    window.aceTable = $("#dt-ace").DataTable({
        serverSide: true,
        processing: true,
        responsive: false,
        lengthChange: true,
        scrollX: true,
        scrollCollapse: true,
        pageLength: 25,
        order: [[2, "desc"]], // urut by "Date" kolom index 2
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
        drawCallback: function () {
            if (aceRoutes.summary) loadSummary();
        },
    });

    // ===== actions =====
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

    // add
    $(document).on("click", '[data-target="#modal-ace"]', function () {
        $("#aceForm")[0].reset();
        $("#ace_mode").val("create");
        $("#ace_id").val("");
        clearAlert();
        $("#mDate").val(todayYmd());
        $("#mShift").val(detectShiftByNow());
    });

    // edit
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

    // delete
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

    // submit
    $("#aceForm").on("submit", function (e) {
        e.preventDefault();
        clearAlert();

        var mode = $("#ace_mode").val();
        var id = $("#ace_id").val();

        // pastikan time HH:MM
        $("#mStart").val(toHm($("#mStart").val()));
        $("#mFinish").val(toHm($("#mFinish").val()));

        var url = aceRoutes.store;
        var method = "POST";
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

    // summary
    function loadSummary() {
        if (!aceRoutes.summary) return;
        $.get(aceRoutes.summary, currentFilters())
            .done(function (res) {
                var $tfoot = $("#dt-ace tfoot");
                var $row = $tfoot.find("tr.ace-summary-row");
                if (!$row.length) return;

                var hasVals =
                    res && Array.isArray(res.values) && res.values.length > 0;
                if (!hasVals) {
                    $tfoot.addClass("d-none");
                    return;
                }

                $tfoot.removeClass("d-none");

                var tds = $row.find("td");
                if (!tds.length) return;

                $(tds[0]).text("");
                $(tds[1]).text(res.label || "TOTAL");

                var vals = res.values || [];
                for (var i = 2; i < tds.length; i++) {
                    var v = vals[i - 2];
                    $(tds[i]).text(
                        v !== undefined && v !== null && v !== "" ? v : ""
                    );
                }
            })
            .fail(function (xhr) {
                $("#dt-ace tfoot").addClass("d-none");
                console.warn("summary load failed", xhr.status);
            });
    }

    // done
})();
