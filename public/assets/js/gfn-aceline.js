(function () {
  var $ = window.jQuery;

  // ===== Guards =====
  if (!$) return;

  // ===== Utilities =====
  function pad(n) { return String(n).padStart(2, "0"); }
  function today() {
    var d = new Date();
    return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
  }
  function autoShift() {
    var h = new Date().getHours();
    return h >= 6 && h < 17 ? "D" : h >= 22 || h < 6 ? "N" : "S";
  }
  function fmt(n, d) {
    if (d === void 0) d = 2;
    if (!isFinite(n)) n = 0;
    return Number(n).toLocaleString("id-ID", {
      minimumFractionDigits: d, maximumFractionDigits: d,
    });
  }

  // ===== Select2 init (tanpa “kunci” filter) =====
  function initSelect2() {
    if (!$.fn.select2) return;
    $(".select2").select2({
      autoclose: true,
      placeholder: function () {
        var ph = $(this).data("placeholder");
        return ph && ph.length ? ph : "-- Select --";
      },
    });
  }

  // ===== Datepicker init =====
  function initDatepickers() {
    if (!$.fn.datepicker) return;

    // FILTER bebas
    var $f = $("#fDate");
    if ($f.length) {
      $f.datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        orientation: "bottom",
      });
    }

    // MODAL: kunci ke hari ini
    var $g = $("#gfnDate");
    if ($g.length) {
      var todayDate = new Date();
      $g.datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        orientation: "bottom",
        container: "#modal-greensand",
        startDate: todayDate,
        endDate: todayDate,
      }).datepicker("setDate", todayDate);
    }
  }

  // ===== Seed filter default (boleh diubah user) =====
  function seedFilter() {
    var $fd = $("#filterForm #fDate");
    var $fs = $('#filterForm select[name="shift"]');
    if ($fd.length && (!$fd.val() || !$fd.val().trim())) {
      $fd.val(today());
      if ($.fn.datepicker) $fd.datepicker("update", $fd.val());
    }
    if ($fs.length && (!$fs.val() || !$fs.val().trim())) {
      $fs.val(autoShift()).trigger("change");
    }
  }

  // ===== Sync nilai filter → modal =====
  function syncModalFromFilter() {
    var $m = $("#modal-greensand");
    if (!$m.length) return;
    var fd = $("#filterForm #fDate").val();
    var sh = $('#filterForm select[name="shift"]').val();
    var $gd = $m.find("#gfnDate");
    var $gs = $m.find('select[name="shift"]');
    if (fd) {
      $gd.val(fd);
      if ($.fn.datepicker) $gd.datepicker("update", fd);
    }
    if (sh) $gs.val(sh).trigger("change");
  }

  // ===== Filter header icon (collapse state) =====
  (function initFilterIcon() {
    var $c = $("#filterCollapse"), $i = $("#filterIcon"), $h = $("#filterHeader");
    function setIcon(open) {
      if (!$i.length) return;
      $i.removeClass("ri-add-line ri-subtract-line").addClass(open ? "ri-subtract-line" : "ri-add-line");
    }
    if (!$c.length) return;
    setIcon($c.hasClass("show"));
    $c.on("shown.bs.collapse", function () { setIcon(true); });
    $c.on("hidden.bs.collapse", function () { setIcon(false); });
    $h.on("click", function () { /* handled by bs collapse */ });
  })();

  // ===== Auto-dismiss flash (pindahan dari Blade) =====
  function initAutoDismissFlash() {
    $('.alert.auto-dismiss').each(function () {
      var $el = $(this);
      var ms = parseInt($el.attr('data-timeout'), 10);
      if (!Number.isFinite(ms) || ms < 0) ms = 3000;
      setTimeout(function () {
        if (typeof $.fn.alert === 'function') {
          try { $el.alert('close'); return; } catch (e) {}
        }
        $el.fadeOut(200, function () { $(this).remove(); });
      }, ms);
    });
  }

  // ===== Recalc table (form GFN) =====
  function recalc() {
    var tb = document.getElementById("gfnBody");
    if (!tb) return;
    var rows = tb.querySelectorAll("tr[data-row]"), tg = 0;
    rows.forEach(function (tr) {
      var g = parseFloat((tr.querySelector(".gfn-gram") && tr.querySelector(".gfn-gram").value) || "0");
      if (!isNaN(g)) tg += g;
    });
    var tp = 0, tpi = 0;
    rows.forEach(function (tr) {
      var idx = parseFloat(tr.dataset.index || "0");
      var g = parseFloat((tr.querySelector(".gfn-gram") && tr.querySelector(".gfn-gram").value) || "0");
      var p = tg > 0 ? (g / tg) * 100 : 0, pi = p * idx;
      var cP = tr.querySelector(".gfn-percent"), cPI = tr.querySelector(".gfn-percent-index");
      if (cP) cP.textContent = fmt(p, 2);
      if (cPI) cPI.textContent = fmt(pi, 1);
      tp += p; tpi += pi;
    });
    var elTG = document.getElementById("gfn-total-gram");
    var elTP = document.getElementById("gfn-total-percent");
    var elTPI = document.getElementById("gfn-total-percent-index");
    if (elTG) elTG.textContent = fmt(tg, 2);
    if (elTP) elTP.textContent = fmt(tp, 2);
    if (elTPI) elTPI.textContent = fmt(tpi, 1);
  }

  // ===== Flot Chart =====
  function renderGFNCharts() {
    if (!$.plot) return;
    var $line = $("#gfn-line");
    if (!$line.length) return;

    var dataObj = window.gfnChartData || {};
    var rows = Array.isArray(dataObj.rows) ? dataObj.rows : [];
    if (!rows.length) { $line.empty(); return; }

    var ticks = [], lineData = [];
    for (var i = 0; i < rows.length; i++) {
      var x = i + 1;
      var pct = parseFloat(rows[i]?.percentage) || 0;
      ticks.push([x, String(x)]);
      lineData.push([x, pct]);
    }

    var plot;
    try {
      plot = $.plot(
        $line,
        [{ data: lineData, label: "%", lines: { show: true, lineWidth: 2 }, points: { show: true, radius: 3 } }],
        {
          xaxis: { ticks: ticks },
          yaxis: { min: 0 },
          grid: { hoverable: true, clickable: true, borderWidth: 1, labelMargin: 10 },
          tooltip: true,
          tooltipOpts: { content: "No %x: %y.2%%", defaultTheme: false },
        }
      );
    } catch (e) { return; }

    try {
      var ctx = plot.getCanvas().getContext("2d");
      var s = plot.getData()[0] || { data: [] };
      ctx.save();
      ctx.font = "12px Arial, sans-serif";
      ctx.textAlign = "center";
      ctx.textBaseline = "bottom";
      for (var j = 0; j < s.data.length; j++) {
        var pt = s.data[j];
        var o = plot.pointOffset({ x: pt[0], y: pt[1] });
        ctx.fillText(pt[1].toFixed(2) + "%", o.left, o.top - 6);
      }
      ctx.restore();
    } catch (_) {}
  }
  var _resizeTimer = null;
  function onWinResize() {
    if (_resizeTimer) clearTimeout(_resizeTimer);
    _resizeTimer = setTimeout(renderGFNCharts, 150);
  }

  // ===== Duplicate guard (before submit) =====
  function ensureWarnHost() {
    var m = document.getElementById("modal-greensand");
    if (!m) return null;
    var box = m.querySelector(".modal-body");
    if (!box) return null;
    var host = m.querySelector("#gfnDupAlert");
    if (!host) {
      host = document.createElement("div");
      host.id = "gfnDupAlert";
      host.className = "alert alert-danger d-none";
      host.setAttribute("role", "alert");
      host.style.marginBottom = "12px";
      box.insertBefore(host, box.firstChild);
    }
    return host;
  }
  function showWarn(msg) {
    var host = ensureWarnHost();
    if (host) {
      host.textContent = msg || "Data sudah ada.";
      host.classList.remove("d-none");
    } else {
      alert(msg || "Data sudah ada.");
    }
  }
  function hideWarn() {
    var host = document.getElementById("gfnDupAlert");
    if (host) host.classList.add("d-none");
  }
  async function checkDuplicate(date, shift) {
    if (!(window.aceRoutes && aceRoutes.gfnExists)) return false; // fallback
    var url = aceRoutes.gfnExists + "?date=" + encodeURIComponent(date || "") + (shift ? "&shift=" + encodeURIComponent(shift) : "");
    try {
      var res = await fetch(url, { headers: { Accept: "application/json" }, credentials: "same-origin" });
      if (!res.ok) return false;
      var j = await res.json();
      return !!(j.exists || j.found || j.duplicate || j.data_exists);
    } catch (_) { return false; }
  }

  // ===== Event bindings =====
  document.addEventListener("input", function (e) {
    if (e.target && e.target.classList.contains("gfn-gram")) recalc();
  });

  if (window.openModalGFN) {
    $(function () {
      $("#modal-greensand").modal("show");
      recalc();
    });
  }

  // delete button fill modal
  $(document).on("click", ".btn-delete-gs", function () {
    var d = $(this).data("gfn-date"), s = $(this).data("shift");
    $("#delDateText").text(d || "—");
    $("#delShiftText").text(s || "—");
    $("#delDate").val(d || "");
    $("#delShift").val(s || "");
  });

  // modal shown → lock & sync
  $(document).on("shown.bs.modal", "#modal-greensand", function () {
    if ($.fn.datepicker && $("#gfnDate").data("datepicker") == null) {
      $("#gfnDate")
        .datepicker({
          format: "yyyy-mm-dd",
          autoclose: true,
          orientation: "bottom",
          container: "#modal-greensand",
          startDate: new Date(),
          endDate: new Date(),
        })
        .datepicker("setDate", new Date());
    }
    syncModalFromFilter();
    var $gd = $("#gfnDate"), $gs = $('select[name="shift"]');
    if ($gd.length && (!$gd.val() || !$gd.val().trim())) {
      $gd.val(today());
      if ($.fn.datepicker) $gd.datepicker("update", $gd.val());
    }
    // kunci shift = current shift only
    var cs = autoShift();
    if ($gs.length) {
      $gs.find("option").prop("disabled", false);
      $gs.find("option").each(function () {
        var v = $(this).val();
        if (v && v !== cs) $(this).prop("disabled", true);
      });
      $gs.val(cs).trigger("change");
    }
  });

  // form submit duplicate check
  $(document).on("submit", "#form-greensand", async function (e) {
    hideWarn();
    var $form = $(this);
    var d = ($form.find("#gfnDate").val() || "").trim();
    var s = ($form.find('select[name="shift"]').val() || "").trim();
    if (!d) return; // server-side validate
    e.preventDefault();
    var dup = await checkDuplicate(d, s);
    if (dup) {
      showWarn("Data untuk tanggal " + d + (s ? " (shift " + s + ")" : "") + " sudah ada. Hapus data tersebut dulu sebelum input ulang.");
      return;
    }
    this.submit();
  });

  // ===== DOM ready =====
  $(function () {
    initSelect2();
    initDatepickers();
    seedFilter();
    initAutoDismissFlash();

    var btn = document.getElementById("btn-add-greensand");
    if (btn) {
      btn.addEventListener("click", function () {
        setTimeout(function () {
          var el = document.getElementById("modal-greensand");
          if (el) $(el).modal("show");
        }, 30);
      });
    }

    if (window.gfnChartData) {
      renderGFNCharts();
      window.addEventListener("resize", onWinResize);
    }
  });
})();
