(function () {
  var $ = window.jQuery;
  if (!$) return console.error("jQuery not found.");
  if (!window.aceRoutes) return console.error("aceRoutes missing.");

  $.ajaxSetup({ cache: false });

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
          data: p => ({ q: p.term || "", page: p.page || 1 }),
          processResults: (data) => ({
            results: Array.isArray(data.results) ? data.results : [],
            pagination: { more: !!(data.pagination && data.pagination.more) },
          }),
        },
      });
    } catch (e) {}
    try {
      $("#filterDate").datepicker({ format: "yyyy-mm-dd", autoclose: true, orientation: "bottom" });
    } catch (e) {}
    $("#filterHeader").off("click").on("click", function () {
      $("#filterCollapse").slideToggle(120);
      $("#filterIcon").toggleClass("ri-subtract-line ri-add-line");
    });
  }

  function gsFlash(msg, type = "success", timeout = 3000) {
    var holder = document.getElementById("flash-holder");
    if (!holder) return;
    var div = document.createElement("div");
    div.className = "alert alert-" + type + " alert-dismissible fade show auto-dismiss";
    div.innerHTML = msg + '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>';
    holder.prepend(div);
    setTimeout(() => { if (window.jQuery && jQuery.fn.alert) try { jQuery(div).alert("close"); } catch {} }, timeout);
  }
  window.gsFlash = gsFlash;

  function normalizeFilterDate(s) {
    if (!s) return "";
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
  function fmt(v) { if (v == null || v === "") return "-"; if (typeof v === "number") return v.toFixed(2); return v; }
  function toHm(s) {
    if (!s) return "";
    var m = /^(\d{2}):(\d{2})(?::\d{2})?$/.exec(String(s));
    return m ? m[1] + ":" + m[2] : String(s).substring(0, 5);
  }
  function formatDateTimeColumn(v, type, row) {
    if (v) return String(v);
    if (row && row.created_time) return String(row.created_time);
    return "-";
  }
  function debounce(fn, wait) {
    var t;
    return function () { clearTimeout(t); var ctx = this, args = arguments; t = setTimeout(() => fn.apply(ctx, args), wait); };
  }

  function currentFilters() {
    return {
      date: normalizeFilterDate($("#filterDate").val()),
      shift: $("#shiftSelect").val() || "",
      product_type_id: $("#productSelectFilter").val() || "",
      _ts: Date.now(),
    };
  }
  (function initFiltersDefaults() {
    var $d = $("#filterDate"), $s = $("#shiftSelect");
    if (!$d.val()) $d.val(todayYmd()).trigger("change");
    if (!$s.val()) $s.val(detectShiftByNow()).trigger("change");
  })();

  var columns = [
    {
      data: null,
      orderable: false,
      searchable: false,
      width: 80,
      render: (_, __, row) => {
        var id = row.id || "";
        return `
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-warning ace-edit btn-sm mr-2" data-id="${id}">
              <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-outline-danger ace-del btn-sm" data-id="${id}">
              <i class="fas fa-trash"></i>
            </button>
          </div>`;
      },
      defaultContent: "",
    },
    { data: "number", defaultContent: "" },
    { data: "date", render: formatDateTimeColumn, defaultContent: "" },
    { data: "shift", defaultContent: "" },
    { data: "product_type_name", defaultContent: "-" },
    { data: "sample_start", render: toHm, defaultContent: "" },
    { data: "sample_finish", render: toHm, defaultContent: "" },
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
    { data: "most", render: fmt, defaultContent: "" },
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

  var summaryManager = (function () {
    var summaryCache = null;
    var colIndex = {
      p: 7, c: 8, gt: 9, cb_lab: 10, moisture: 11, bakunetsu: 13,
      ac: 14, tc: 15, vsd: 16, ig: 17, cb_weight: 18, tp50_weight: 19,
      ssi: 20, dw29_vas: 22, dw29_debu: 23, dw31_vas: 24, dw31_id: 25,
      dw31_moldex: 26, dw31_sc: 27, bc13_cb: 29, bc13_c: 30, bc13_m: 31,
    };

    function columnsLength() { return (window.aceTable && window.aceTable.settings().init().columns.length) || 0; }
    function $tbody() { return $(window.aceTable.table().body()); }
    function clear() { $tbody().find("tr.ace-summary-row, tr.ace-summary-divider").remove(); }

    function dividerRow(cols) {
      return '<tr class="ace-summary-divider"><td colspan="' + cols + '"></td></tr>';
    }
    function makeRowHtml(label, valuesMap, cols) {
      var html = '<tr class="ace-summary-row">';
      html += '<td class="text-center font-weight-bold" colspan="7">' + label + '</td>';
      for (var i = 7; i < cols; i++) {
        var val = valuesMap && valuesMap[i] != null ? valuesMap[i] : "";
        html += '<td class="text-center">' + val + '</td>';
      }
      html += "</tr>";
      return html;
    }

    function buildRows(summaryList) {
      var cols = columnsLength();
      var rows = { min: {}, max: {}, avg: {}, judge: {} };
      (summaryList || []).forEach(function (s) {
        var idx = colIndex[s.field];
        if (idx == null) return;
        rows.min[idx]   = s.min ?? "";
        rows.max[idx]   = s.max ?? "";
        rows.avg[idx]   = s.avg ?? "";
        rows.judge[idx] = s.judge ? '<span class="' + (s.judge === "NG" ? "j-ng" : "j-ok") + '">' + s.judge + "</span>" : "";
      });
      return (
        dividerRow(cols) +
        makeRowHtml("MIN", rows.min, cols) +
        makeRowHtml("MAX", rows.max, cols) +
        makeRowHtml("AVG", rows.avg, cols) +
        makeRowHtml("JUDGE", rows.judge, cols)
      );
    }

    function render(list) {
      var $tb = $tbody();
      if (!$tb.length) return;
      clear();
      $tb.append(buildRows(list || []));
    }

    function load() {
      if (!window.aceRoutes || !window.aceRoutes.summary) return;
      var f = currentFilters();
      $.get(window.aceRoutes.summary, { date: f.date, shift: f.shift, product_type_id: f.product_type_id })
        .done(function (res) {
          summaryCache = Array.isArray(res.summary) ? res.summary : [];
          render(summaryCache);
        })
        .fail(function () {
          summaryCache = [];
          render(summaryCache);
        });
    }

    return {
      load: load,
      renderFromCache: function () { if (summaryCache) render(summaryCache); },
      clear: clear
    };
  })();

  window.aceTable = $("#dt-ace").DataTable({
    serverSide: true,
    processing: true,
    responsive: false,
    lengthChange: true,
    scrollX: true,
    scrollCollapse: true,
    deferRender: true,
    pageLength: 25,
    order: [[2, "asc"], [1, "asc"]],
    ajax: {
      url: aceRoutes.data,
      type: "GET",
      data: d => Object.assign(d, currentFilters()),
      cache: false,
      error: xhr => { console.error("DT ajax error", xhr); gsFlash("Gagal memuat data.", "danger"); },
    },
    columns: columns,
    columnDefs: [{ targets: "_all", className: "align-middle text-center" }],
    drawCallback: function () { summaryManager.load(); setTimeout(() => summaryManager.renderFromCache(), 0); },
    initComplete: function () { summaryManager.load(); setTimeout(() => summaryManager.renderFromCache(), 0); },
  });

  var reRenderSummary = debounce(() => summaryManager.renderFromCache(), 120);
  $(window).on("resize.ace", reRenderSummary);

  function reloadTable(cb) {
    if (window.aceTable) {
      summaryManager.clear();
      window.aceTable.ajax.reload(() => { if (typeof cb === "function") cb(); }, false);
    }
  }

  $("#btnSearch").on("click", () => reloadTable(() => gsFlash("Filter diterapkan.", "info")));
  $("#btnRefresh").on("click", () => {
    $("#filterDate").val(todayYmd());
    $("#shiftSelect").val(detectShiftByNow()).trigger("change");
    $("#productSelectFilter").val("").trigger("change");
    reloadTable(() => gsFlash("Filter direset.", "secondary"));
  });

  $("#btnExport").on("click", () => {
    if (!aceRoutes.export) return;
    var q = $.param(currentFilters());
    window.location.href = aceRoutes.export + (q ? "?" + q : "");
    gsFlash("Menyiapkan file Excel…", "info");
  });

  $(document).on("click", '[data-toggle="modal"][data-target="#modal-ace"]', function () {
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
    $("#productTypeName").val("");
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
        var $ps = $("#productSelectModal");
        if (row.product_type_id && row.product_type_name) {
          if ($ps.data("select2")) $ps.empty();
          var opt = new Option(row.product_type_name, row.product_type_id, true, true);
          $ps.append(opt).trigger("change");
          $("#productTypeName").val(row.product_type_name);
        }
        $("#modal-ace").modal("show");
      })
      .fail(() => gsFlash("Gagal mengambil data untuk edit.", "danger"));
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
      .done(() => {
        $("#confirmDeleteModal").modal("hide");
        reloadTable(() => gsFlash("Data berhasil dihapus.", "success"));
      })
      .fail(() => gsFlash("Hapus data gagal.", "danger"));
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
    $btn.prop("disabled", true).data("orig", $btn.html()).html('<span class="spinner-border spinner-border-sm mr-1"></span> Saving...');
    $.ajax({
      url, type: method, data: fd, processData: false, contentType: false,
      headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
    })
      .done(() => {
        $("#modal-ace").modal("hide");
        reloadTable(() => gsFlash(mode === "update" ? "Data berhasil diperbarui." : "Data berhasil disimpan.", "success"));
      })
      .fail(xhr => {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || "Simpan data gagal.";
        $("#aceFormAlert").removeClass("d-none").text(msg);
        gsFlash(msg, "danger");
      })
      .always(() => $btn.prop("disabled", false).html($btn.data("orig") || "Submit"));
  });

  function fillForm(data) {
    if (!data) return;
    Object.keys(data).forEach(k => {
      var $f = $("#m_" + k);
      if ($f.length) $f.val(data[k]);
    });
    $("#mStart").val(data.sample_start || "");
    $("#mFinish").val(data.sample_finish || "");
    $("#mNoMix").val(data.no_mix || "");
    if (data.date) $("#mDate").val(String(data.date).substring(0, 10));
    if (data.shift) $("#mShift").val(data.shift);
  }

  $(function () { initPageUI(); });
})();
