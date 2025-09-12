// public/assets/js/app-global.js

// ==============================
// 1) Livewire init: sync start/end date (GLOBAL)
// ==============================
document.addEventListener("livewire:init", () => {
    function syncDates() {
        const s = document.getElementById("startDate");
        const e = document.getElementById("endDate");
        if (!s || !e) return;

        e.min = s.value || "";
        if (s.value && (!e.value || e.value < s.value)) {
            e.value = s.value;
            e.dispatchEvent(new Event("input", { bubbles: true }));
            e.dispatchEvent(new Event("change", { bubbles: true }));
        } else if (!s.value) {
            e.removeAttribute("min");
        }
    }

    document.addEventListener("input", (ev) => {
        if (ev.target?.id === "startDate") syncDates();
    });
    document.addEventListener("change", (ev) => {
        if (ev.target?.id === "startDate") syncDates();
    });
});

// ==============================
// 2) DataTables init/destroy (GLOBAL)
//    - id tabel default: #datatable1
//    - state per tab via data-tab
// ==============================
(function attachDataTablesClientSide() {
    const $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    const TABLE_ID = "#datatable1";
    const INIT_FLAG = "__gs_dt_inited";
    const $tbl = () => $(TABLE_ID);
    const el = () => document.querySelector(TABLE_ID);

    const isDT = () => {
        const n = el();
        if (!n) return false;
        try {
            return $.fn.DataTable.isDataTable(n);
        } catch {
            return false;
        }
    };

    function sanitizeWrappers() {
        const n = el();
        if (!n) return;
        document.querySelectorAll(".dataTables_wrapper").forEach((w) => {
            if (!w.contains(n)) {
                try {
                    w.remove();
                } catch {}
            }
        });
        if (!isDT() && $(n).closest(".dataTables_wrapper").length) {
            try {
                $(n).DataTable().destroy(true);
            } catch {}
        }
    }

    function destroyDT() {
        const n = el();
        if (!n) return;
        try {
            if (isDT()) $(n).DataTable().clear().destroy(true);
        } catch {}
        $(n).find("thead th, tbody td").css("width", "");
        $tbl().data(INIT_FLAG, false);
        sanitizeWrappers();
    }

    function isReady() {
        const n = el();
        return !!(n && n.tHead && n.tBodies && n.tBodies[0]);
    }

    function buildOptions() {
        const $ = window.jQuery;
        const tab = ($tbl().data("tab") || "all").toString();
        const storageKey = `dt:datatable1:${tab}`;

        const opts = {
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            scrollX: true,
            lengthMenu: [10, 25, 50, 100, 500, 1000],
            pageLength: 10,
            stateSave: true,
            stateDuration: -1,
            stateSaveCallback: (_s, data) => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(data));
                } catch {}
            },
            stateLoadCallback: () => {
                try {
                    return JSON.parse(
                        localStorage.getItem(storageKey) || "null"
                    );
                } catch {
                    return null;
                }
            },
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_–_END_ of _TOTAL_ entries",
                infoEmpty: "No data available",
                zeroRecords: "No matching records found",
                paginate: { first: "<<", last: ">>", next: ">", previous: "<" },
                processing: "Processing...",
            },
        };

        if ($.fn.dataTable && $.fn.dataTable.Buttons) {
            opts.dom =
                "<'row'<'col-sm-6'B><'col-sm-6'f>>tr<'row'<'col-sm-5'i><'col-sm-7'p>>";
            opts.buttons = [
                { extend: "copyHtml5", titleAttr: "Copy" },
                { extend: "csvHtml5", titleAttr: "CSV" },
                { extend: "excelHtml5", titleAttr: "Excel" },
                { extend: "print", titleAttr: "Print" },
            ];
        } else {
            opts.dom =
                "<'row'<'col-sm-6'l><'col-sm-6'f>>tr<'row'<'col-sm-5'i><'col-sm-7'p>>";
        }

        return opts;
    }

    function initDT(retry = 0) {
        const n = el();
        if (!n) return;
        if (isDT() || $tbl().data(INIT_FLAG) === true) return;
        if (!isReady()) {
            if (retry < 12) setTimeout(() => initDT(retry + 1), 60);
            return;
        }
        sanitizeWrappers();
        $(n).DataTable(buildOptions());
        $tbl().data(INIT_FLAG, true);
    }

    document.addEventListener("DOMContentLoaded", () =>
        setTimeout(() => initDT(0), 100)
    );

    if (window.Livewire?.hook) {
        window.Livewire.hook("message.sent", () => destroyDT());
        window.Livewire.hook("message.processed", () =>
            Promise.resolve().then(() => requestAnimationFrame(() => initDT(0)))
        );
        window.Livewire.hook("morph.removing", (node) => {
            const n = el();
            if (!n) return;
            if (node === n || node?.querySelector?.(TABLE_ID)) destroyDT();
        });
        window.Livewire.hook("morph.added", () => initDT(0));
        window.Livewire.hook("morph.updated", () => initDT(0));
    }

    document.addEventListener("livewire:navigated", () => {
        destroyDT();
        setTimeout(() => initDT(0), 120);
    });

    (function ensureObserver() {
        const n = el();
        if (!n || $tbl().data("dt-observer")) return;
        const obs = new MutationObserver((muts) => {
            if (
                muts.some(
                    (m) => m.type === "childList" || m.type === "attributes"
                )
            )
                initDT(0);
        });
        obs.observe(n, { childList: true, subtree: true, attributes: true });
        $tbl().data("dt-observer", obs);
    })();
})();

// ==============================
// 3) Toast + Confirm Modal (GLOBAL)
//    - Event 'app:toast' (umum)
//    - Kompat: juga dengarkan 'gs:toast'
//    - Confirm modal global: #confirmDeleteModal + tombol #confirmDeleteYes
//    - DELETE SMART-HANDLER:
//        * Jika klik dari .js-delete (punya pendingId)  -> panggil delete(id)
//        * Jika dibuka dari event app:confirm-open      -> panggil deleteConfirmed()
// ==============================
(function () {
    const $ = window.jQuery;

    // --- Toastr ---
    if (window.toastr) {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            preventDuplicates: true,
            positionClass: "toast-top-right",
            timeOut: 3000,
            extendedTimeOut: 1500,
            showDuration: 200,
            hideDuration: 200,
            showMethod: "fadeIn",
            hideMethod: "fadeOut",
            // escapeHtml: false,
        };

        function handleToast(e) {
            const d = e.detail || {};
            const type = String(d.type || "success").toLowerCase();
            const title = (d.title ?? "").toString().trim();
            const msgRaw = (d.text ?? d.message ?? "").toString();
            const msg = msgRaw.trim();
            const message =
                msg ||
                (type === "success"
                    ? "Success"
                    : type === "error"
                    ? "Terjadi kesalahan"
                    : type === "warning"
                    ? "Perhatian"
                    : "Info");

            switch (type) {
                case "success":
                    toastr.success(message, title);
                    break;
                case "error":
                    toastr.error(message, title);
                    break;
                case "warning":
                    toastr.warning(message, title);
                    break;
                default:
                    toastr.info(message, title);
                    break;
            }
        }

        window.addEventListener("app:toast", handleToast);
        window.addEventListener("gs:toast", handleToast); // kompat lama
    }

    // --- Confirm modal global ---
    const MODAL_ID = "#confirmDeleteModal";
    const BTN_YES = "#confirmDeleteYes";
    const MODAL_TITLE = "#confirmDeleteTitle";
    const MODAL_TEXT = "#confirmDeleteText";

    // state global untuk alur delete
    window.__APP_PENDING_ID = null;

    function cleanupBackdrops() {
        try {
            document
                .querySelectorAll(".modal-backdrop")
                .forEach((b) => b.remove());
            if (!document.querySelector(".modal.show")) {
                document.body.classList.remove("modal-open");
                document.body.style.removeProperty("padding-right");
            }
        } catch (_) {}
    }

    // A) Pola umum dari komponen (tanpa id): buka/tutup modal
    window.addEventListener("app:confirm-open", () => {
        $(MODAL_ID).modal("show");
    });
    window.addEventListener("app:confirm-close", () => {
        $(MODAL_ID).modal("hide");
        setTimeout(cleanupBackdrops, 50);
    });

    // B) Pola cepat byId: klik tombol .js-delete -> set pendingId + buka modal
    document.addEventListener("click", (ev) => {
        const btn = ev.target.closest(".js-delete");
        if (!btn) return;

        ev.preventDefault();
        const id = btn.getAttribute("data-id");
        window.__APP_PENDING_ID = id ? parseInt(id, 10) : null;

        const label = btn.getAttribute("data-label") || (id ? `ID ${id}` : "");
        const $title = document.querySelector(MODAL_TITLE);
        const $text = document.querySelector(MODAL_TEXT);
        if ($title) $title.textContent = "Confirm Delete";
        if ($text)
            $text.textContent = label
                ? `Are you sure want to delete the data ${label}?`
                : `Are you sure want to delete this data?`;

        $(MODAL_ID).modal("show");
    });

    // Tombol Yes -> pintar: kalau ada pendingId -> delete(id); else -> deleteConfirmed()
    $(document)
        .off("click.app-confirm")
        .on("click.app-confirm", BTN_YES, function () {
            try {
                // cari root komponen terdekat
                let lw = null;

                // prioritas: root dari tabel jika ada
                const table = document.querySelector("#datatable1");
                if (table) {
                    const root = table.closest("[wire\\:id]");
                    if (root && window.Livewire) {
                        lw = window.Livewire.find(root.getAttribute("wire:id"));
                    }
                }
                // fallback: root pertama yang ketemu
                if (!lw && window.Livewire) {
                    const anyRoot = document.querySelector("[wire\\:id]");
                    if (anyRoot)
                        lw = window.Livewire.find(
                            anyRoot.getAttribute("wire:id")
                        );
                }

                // mode pintar
                if (window.__APP_PENDING_ID != null) {
                    if (lw?.call) lw.call("delete", window.__APP_PENDING_ID);
                } else {
                    if (lw?.call) lw.call("deleteConfirmed");
                }
            } catch (_) {}

            // reset & tutup modal
            window.__APP_PENDING_ID = null;
            if (document.activeElement) document.activeElement.blur();
            $(MODAL_ID).modal("hide");
            setTimeout(cleanupBackdrops, 50);
        });

    // Reset saat modal ditutup manual
    $(MODAL_ID).on("hidden.bs.modal", () => {
        window.__APP_PENDING_ID = null;
        document.body.focus();
    });

    // Jaga-jaga saat Livewire update/navigate
    document.addEventListener("livewire:update", cleanupBackdrops);
    document.addEventListener("livewire:navigated", cleanupBackdrops);
})();
window.addEventListener("gs:open", () => {
    const $ = window.jQuery;
    if (!$) return;

    $("#modal-greensand").modal("show");
    window.Livewire?.emit("gs:modal-open"); // reset error setiap kali modal dibuka
});
