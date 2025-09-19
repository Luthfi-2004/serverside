(function () {
  var $ = window.jQuery;

  // init
  function s2() {
    if ($ && $.fn.select2) {
      $('.select2').select2({
        autoclose: true,
        placeholder: function () {
          var ph = $(this).data('placeholder');
          return ph && ph.length ? ph : '-- Select --';
        }
      });
    }
  }
  function dp() {
    if ($ && $.fn.datepicker) {
      var $d = $('#fDate').length ? $('#fDate') : $('#startDate');
      if ($d.length) {
        $d.datepicker({
          format: 'yyyy-mm-dd',
          autoclose: true,
          orientation: 'bottom'
        });
      }
    }
  }

  // fold
  if ($) {
    var $c = $("#filterCollapse"), $i = $("#filterIcon"), $h = $("#filterHeader");
    function ico(o) {
      if (!$i.length) return;
      $i.removeClass("ri-add-line ri-subtract-line")
        .addClass(o ? "ri-subtract-line" : "ri-add-line");
    }
    if ($c.length) {
      ico($c.hasClass("show"));
      $c.on("shown.bs.collapse", function () { ico(true) });
      $c.on("hidden.bs.collapse", function () { ico(false) });
      $h.on("click", function () { });
    }
  }

  // fmt
  function fmt(n, d) {
    if (d === void 0) d = 2;
    if (!isFinite(n)) n = 0;
    return Number(n).toLocaleString('id-ID', {
      minimumFractionDigits: d,
      maximumFractionDigits: d
    });
  }

  // calc
  function recalc() {
    var tb = document.getElementById('gfnBody'); if (!tb) return;
    var rows = tb.querySelectorAll('tr[data-row]'), tg = 0;
    rows.forEach(function (tr) {
      var g = parseFloat((tr.querySelector('.gfn-gram') && tr.querySelector('.gfn-gram').value) || '0');
      if (!isNaN(g)) tg += g;
    });
    var tp = 0, tpi = 0;
    rows.forEach(function (tr) {
      var idx = parseFloat(tr.dataset.index || '0');
      var g = parseFloat((tr.querySelector('.gfn-gram') && tr.querySelector('.gfn-gram').value) || '0');
      var p = tg > 0 ? (g / tg) * 100 : 0, pi = p * idx;
      var cP = tr.querySelector('.gfn-percent'), cPI = tr.querySelector('.gfn-percent-index');
      if (cP) cP.textContent = fmt(p, 2);
      if (cPI) cPI.textContent = fmt(pi, 1);
      tp += p; tpi += pi;
    });
    var elTG = document.getElementById('gfn-total-gram');
    var elTP = document.getElementById('gfn-total-percent');
    var elTPI = document.getElementById('gfn-total-percent-index');
    if (elTG) elTG.textContent = fmt(tg, 2);
    if (elTP) elTP.textContent = fmt(tp, 2);
    if (elTPI) elTPI.textContent = fmt(tpi, 1);
  }

  // listen
  document.addEventListener('input', function (e) {
    if (e.target && e.target.classList.contains('gfn-gram')) recalc();
  });

  // modal auto-open (server side flag)
  if (window.openModalGFN) {
    $(function () { $('#modal-greensand').modal('show'); recalc(); });
  }

  // dom
  document.addEventListener('DOMContentLoaded', function () {
    s2(); dp();
    var b = document.getElementById('btn-add-greensand');
    if (b && window.jQuery) {
      b.addEventListener('click', function () {
        setTimeout(function () {
          var el = document.getElementById('modal-greensand');
          if (el) jQuery(el).modal('show');
        }, 30);
      });
    }
  });

  // del
  if ($) {
    $(document).on('click', '.btn-delete-gs', function () {
      var d = $(this).data('gfn-date'), s = $(this).data('shift');
      $('#delDateText').text(d || '—'); $('#delShiftText').text(s || '—');
      $('#delDate').val(d || ''); $('#delShift').val(s || '');
    });
  }
})();
