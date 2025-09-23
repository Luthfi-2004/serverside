(function () {
    var $ = window.jQuery;

    // util
    function pad(n) {
        return String(n).padStart(2, "0");
    }
    function today() {
        var d = new Date();
        return (
            d.getFullYear() +
            "-" +
            pad(d.getMonth() + 1) +
            "-" +
            pad(d.getDate())
        );
    }
    function autoShift() {
        var h = new Date().getHours();
        return h >= 6 && h < 17 ? "D" : h >= 22 || h < 6 ? "N" : "S";
    }

    // === Select2 (BALIKIN) ===
    function s2() {
        if (!$ || !$.fn.select2) return;
        $(".select2").select2({
            autoclose: true,
            placeholder: function () {
                var ph = $(this).data("placeholder");
                return ph && ph.length ? ph : "-- Select --";
            },
        });
    }

    // === Datepicker (fix modal) ===
    function dp() {
        if (!$ || !$.fn.datepicker) return;
        // filter
        var $f = $("#fDate");
        if ($f.length) {
            $f.datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                orientation: "bottom",
            });
        }
        // modal (PASTI container modal, biar gak ketimpa z-index)
        var $g = $("#gfnDate");
        if ($g.length) {
            $g.datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                orientation: "bottom",
                container: "#modal-greensand",
            });
        }
    }

    // seed filter (hari ini + shift auto, kalau kosong)
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

    // sync modal dari filter (tanggal & shift)
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
        if (sh) {
            $gs.val(sh).trigger("change");
        }
    }

    // fold icon
    if ($) {
        var $c = $("#filterCollapse"),
            $i = $("#filterIcon"),
            $h = $("#filterHeader");
        function ico(o) {
            if (!$i.length) return;
            $i.removeClass("ri-add-line ri-subtract-line").addClass(
                o ? "ri-subtract-line" : "ri-add-line"
            );
        }
        if ($c.length) {
            ico($c.hasClass("show"));
            $c.on("shown.bs.collapse", function () {
                ico(true);
            });
            $c.on("hidden.bs.collapse", function () {
                ico(false);
            });
            $h.on("click", function () {});
        }
    }

    // fmt
    function fmt(n, d) {
        if (d === void 0) d = 2;
        if (!isFinite(n)) n = 0;
        return Number(n).toLocaleString("id-ID", {
            minimumFractionDigits: d,
            maximumFractionDigits: d,
        });
    }

    // kalkulasi tabel input di modal
    function recalc() {
        var tb = document.getElementById("gfnBody");
        if (!tb) return;
        var rows = tb.querySelectorAll("tr[data-row]"),
            tg = 0;
        rows.forEach(function (tr) {
            var g = parseFloat(
                (tr.querySelector(".gfn-gram") &&
                    tr.querySelector(".gfn-gram").value) ||
                    "0"
            );
            if (!isNaN(g)) tg += g;
        });
        var tp = 0,
            tpi = 0;
        rows.forEach(function (tr) {
            var idx = parseFloat(tr.dataset.index || "0");
            var g = parseFloat(
                (tr.querySelector(".gfn-gram") &&
                    tr.querySelector(".gfn-gram").value) ||
                    "0"
            );
            var p = tg > 0 ? (g / tg) * 100 : 0,
                pi = p * idx;
            var cP = tr.querySelector(".gfn-percent"),
                cPI = tr.querySelector(".gfn-percent-index");
            if (cP) cP.textContent = fmt(p, 2);
            if (cPI) cPI.textContent = fmt(pi, 1);
            tp += p;
            tpi += pi;
        });
        var elTG = document.getElementById("gfn-total-gram");
        var elTP = document.getElementById("gfn-total-percent");
        var elTPI = document.getElementById("gfn-total-percent-index");
        if (elTG) elTG.textContent = fmt(tg, 2);
        if (elTP) elTP.textContent = fmt(tp, 2);
        if (elTPI) elTPI.textContent = fmt(tpi, 1);
    }

 // === CHART: Line (% per Mesh) — X pakai nomor urut + label nilai di atas titik ===
function renderGFNCharts() {
  if (!window.jQuery || !$.plot) return;

  var $line = $('#gfn-line');
  if (!$line.length) return;

  var dataObj = window.gfnChartData || {};
  var rows = Array.isArray(dataObj.rows) ? dataObj.rows : [];
  if (!rows.length) { $line.empty(); return; }

  var ticks = [];
  var lineData = [];
  for (var i = 0; i < rows.length; i++) {
    var x = i + 1; // No 1..n
    var pct = parseFloat(rows[i]?.percentage) || 0;
    ticks.push([x, String(x)]);
    lineData.push([x, pct]);
  }

  // plot
  var plot;
  try {
    plot = $.plot($line, [
      { data: lineData, label: '%', lines: { show: true, lineWidth: 2 }, points: { show: true, radius: 3 } }
    ], {
      xaxis: { ticks: ticks },
      yaxis: { min: 0 },
      grid: { hoverable: true, clickable: true, borderWidth: 1, labelMargin: 10 },
      tooltip: true,
      tooltipOpts: { content: 'No %x: %y.2%%', defaultTheme: false }
    });
  } catch (e) { return; }

  // --- gambar label nilai di atas tiap titik ---
  try {
    var ctx = plot.getCanvas().getContext('2d');
    var s = plot.getData()[0] || { data: [] };

    ctx.save();
    // gaya teks (silakan ubah kalau perlu)
    ctx.font = '12px Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'bottom';
    // ctx.fillStyle = '#495057'; // opsional: pilih warna teks

    for (var j = 0; j < s.data.length; j++) {
      var pt = s.data[j];
      var o = plot.pointOffset({ x: pt[0], y: pt[1] });
      // naikkan 6px biar gak nabrak marker
      ctx.fillText(pt[1].toFixed(2) + '%', o.left, o.top - 6);
    }
    ctx.restore();
  } catch (_) { /* no-op */ }
}


    // Re-plot saat resize (flot.resize biasanya cukup, ini tambahan)
    var _resizeTimer = null;
    function onWinResize() {
        if (_resizeTimer) clearTimeout(_resizeTimer);
        _resizeTimer = setTimeout(renderGFNCharts, 150);
    }

    // listeners
    document.addEventListener("input", function (e) {
        if (e.target && e.target.classList.contains("gfn-gram")) recalc();
    });

    // modal-flag
    if (window.openModalGFN) {
        $(function () {
            $("#modal-greensand").modal("show");
            recalc();
        });
    }

    // DOM ready
    document.addEventListener("DOMContentLoaded", function () {
        s2();
        dp();
        seedFilter();
        var b = document.getElementById("btn-add-greensand");
        if (b && window.jQuery) {
            b.addEventListener("click", function () {
                setTimeout(function () {
                    var el = document.getElementById("modal-greensand");
                    if (el) jQuery(el).modal("show");
                }, 30);
            });
        }

        // render chart saat data sudah di-bridge dari Blade
        if (window.gfnChartData) {
            if ($) {
                $(renderGFNCharts);
            } else {
                renderGFNCharts();
            }
            if (window.addEventListener)
                window.addEventListener("resize", onWinResize);
        }
    });

    // modal-show (jangan re-init select2 disini biar gak dobel)
    if ($) {
        $(document).on("shown.bs.modal", "#modal-greensand", function () {
            if ($.fn.datepicker && $("#gfnDate").data("datepicker") == null) {
                $("#gfnDate").datepicker({
                    format: "yyyy-mm-dd",
                    autoclose: true,
                    orientation: "bottom",
                    container: "#modal-greensand",
                });
            }
            syncModalFromFilter();
            var $gd = $("#gfnDate"),
                $gs = $('select[name="shift"]');
            if ($gd.length && (!$gd.val() || !$gd.val().trim())) {
                $gd.val(today());
                if ($.fn.datepicker) $gd.datepicker("update", $gd.val());
            }
            if ($gs.length && (!$gs.val() || !$gs.val().trim())) {
                $gs.val(autoShift()).trigger("change");
            }
        });
    }

    // delete confirm
    if ($) {
        $(document).on("click", ".btn-delete-gs", function () {
            var d = $(this).data("gfn-date"),
                s = $(this).data("shift");
            $("#delDateText").text(d || "—");
            $("#delShiftText").text(s || "—");
            $("#delDate").val(d || "");
            $("#delShift").val(s || "");
        });
    }
})();
