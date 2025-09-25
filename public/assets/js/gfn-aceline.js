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

    // select2 (TANPA kunci di filter)
    function s2() {
        if (!$ || !$.fn.select2) return;
        $(".select2").select2({
            autoclose: true,
            placeholder: function () {
                var ph = $(this).data("placeholder");
                return ph && ph.length ? ph : "-- Select --";
            },
        });
        // ❌ Tidak disable shift di sini (filter bebas)
    }

    // datepicker
    function dp() {
        if (!$ || !$.fn.datepicker) return;

        // FILTER: bebas
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

    // seeding filter (boleh default, tapi tidak membatasi)
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

    // sync nilai filter ke modal
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

    // ikon filter
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

    // format
    function fmt(n, d) {
        if (d === void 0) d = 2;
        if (!isFinite(n)) n = 0;
        return Number(n).toLocaleString("id-ID", {
            minimumFractionDigits: d,
            maximumFractionDigits: d,
        });
    }

    // hitung
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

    // chart
    function renderGFNCharts() {
        if (!window.jQuery || !$.plot) return;
        var $line = $("#gfn-line");
        if (!$line.length) return;

        var dataObj = window.gfnChartData || {};
        var rows = Array.isArray(dataObj.rows) ? dataObj.rows : [];
        if (!rows.length) {
            $line.empty();
            return;
        }

        var ticks = [],
            lineData = [];
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
                [
                    {
                        data: lineData,
                        label: "%",
                        lines: { show: true, lineWidth: 2 },
                        points: { show: true, radius: 3 },
                    },
                ],
                {
                    xaxis: { ticks: ticks },
                    yaxis: { min: 0 },
                    grid: {
                        hoverable: true,
                        clickable: true,
                        borderWidth: 1,
                        labelMargin: 10,
                    },
                    tooltip: true,
                    tooltipOpts: {
                        content: "No %x: %y.2%%",
                        defaultTheme: false,
                    },
                }
            );
        } catch (e) {
            return;
        }

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

    // input
    document.addEventListener("input", function (e) {
        if (e.target && e.target.classList.contains("gfn-gram")) recalc();
    });

    // autoload modal
    if (window.openModalGFN) {
        $(function () {
            $("#modal-greensand").modal("show");
            recalc();
        });
    }

    // notifikasi duplikat
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
        var url =
            aceRoutes.gfnExists +
            "?date=" +
            encodeURIComponent(date || "") +
            (shift ? "&shift=" + encodeURIComponent(shift) : "");
        try {
            var res = await fetch(url, {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });
            if (!res.ok) return false;
            var j = await res.json();
            return !!(j.exists || j.found || j.duplicate || j.data_exists);
        } catch (_) {
            return false;
        }
    }

    // ready
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

        if (window.gfnChartData) {
            if ($) {
                $(renderGFNCharts);
            } else {
                renderGFNCharts();
            }
            if (window.addEventListener)
                window.addEventListener("resize", onWinResize);
        }

        // submit
        if ($) {
            $(document).on("submit", "#form-greensand", async function (e) {
                hideWarn();
                var $form = $(this);
                var d = ($form.find("#gfnDate").val() || "").trim();
                var s = ($form.find('select[name="shift"]').val() || "").trim();
                if (!d) return; // server will validate
                e.preventDefault();
                var dup = await checkDuplicate(d, s);
                if (dup) {
                    showWarn(
                        "Data untuk tanggal " +
                            d +
                            (s ? " (shift " + s + ")" : "") +
                            " sudah ada. Hapus data tersebut dulu sebelum input ulang."
                    );
                    return;
                }
                this.submit();
            });
        }
    });

    // modal shown → di sinilah kita KUNCI
    if ($) {
        $(document).on("shown.bs.modal", "#modal-greensand", function () {
            // Lock date ke hari ini
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

            // Sync nilai filter
            syncModalFromFilter();

            // Set default jika kosong
            var $gd = $("#gfnDate"),
                $gs = $('select[name="shift"]');

            if ($gd.length && (!$gd.val() || !$gd.val().trim())) {
                $gd.val(today());
                if ($.fn.datepicker) $gd.datepicker("update", $gd.val());
            }

            // Kunci shift di modal: hanya shift saat ini yang enabled
            var cs = autoShift();
            if ($gs.length) {
                $gs.find("option").prop("disabled", false); // reset dulu
                $gs.find("option").each(function () {
                    var v = $(this).val();
                    if (v && v !== cs) $(this).prop("disabled", true);
                });
                $gs.val(cs).trigger("change");
            }
        });
    }

    // hapus (tetap)
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
