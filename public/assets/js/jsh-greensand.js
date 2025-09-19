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

    const $ = window.jQuery;
    if (!$) return;
    const $col = $("#filterCollapse");
    const $icon = $("#filterIcon");
    const $header = $("#filterHeader");

    if ($col.length) {
        $col.collapse({ toggle: false });
        let reconcileTimer = null;

        function setIcon(isOpen) {
            if (!$icon.length) return;
            $icon
                .removeClass("ri-add-line ri-subtract-line")
                .addClass(isOpen ? "ri-subtract-line" : "ri-add-line");
        }
        function getLW() {
            const root = $col.closest("[wire\\:id]");
            if (!root.length || !window.Livewire) return null;
            return window.Livewire.find(root.attr("wire:id"));
        }
        $header.on("click", (e) => {
            if ($col.attr("data-lock") === "1") {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
        function lock(on) {
            $col.attr("data-lock", on ? "1" : "0");
            $header.toggleClass("locked", !!on);
        }
        function reconcileCollapse() {
            if ($col.attr("data-lock") === "1") return;
            const shouldOpen = $col.attr("data-open") === "1";
            const lastState = $col.attr("data-gs-state") === "1";
            const isShown = $col.hasClass("show");
            if (!$col.data("gs-boot")) {
                setIcon(isShown);
                $col.attr("data-gs-state", isShown ? "1" : "0");
                $col.data("gs-boot", 1);
                return;
            }
            if (shouldOpen !== lastState) {
                $col[0].offsetHeight;
                lock(true);
                $col.collapse(shouldOpen ? "show" : "hide");
            } else if (isShown !== lastState) {
                setIcon(isShown);
            }
        }
        function scheduleReconcile() {
            if (reconcileTimer) clearTimeout(reconcileTimer);
            reconcileTimer = setTimeout(() => {
                syncDates();
                reconcileCollapse();
            }, 80);
        }
        if (!$col.data("gs-bound")) {
            $col.on("show.bs.collapse", () => lock(true));
            $col.on("hide.bs.collapse", () => lock(true));
            $col.on("shown.bs.collapse", () => {
                setIcon(true);
                $col.attr("data-gs-state", "1");
                lock(false);
                const lw = getLW();
                if (lw?.get("filterOpen") !== true) lw.set("filterOpen", true);
            });
            $col.on("hidden.bs.collapse", () => {
                setIcon(false);
                $col.attr("data-gs-state", "0");
                lock(false);
                const lw = getLW();
                if (lw?.get("filterOpen") !== false)
                    lw.set("filterOpen", false);
            });
            $col.data("gs-bound", 1);
        }
        document.addEventListener("DOMContentLoaded", scheduleReconcile);
        document.addEventListener("livewire:navigated", scheduleReconcile);
        window.Livewire?.hook?.("message.processed", scheduleReconcile);
    }

    window.addEventListener("gs:open", () => {
        $("#modal-greensand").modal("show");
    });
    window.addEventListener("gs:close", () => {
        $("#modal-greensand").modal("hide");
    });
});

// Script khusus JSH Green Sand modal
document.addEventListener("livewire:init", () => {
    Livewire.on("showModalGreensand", () => {
        $("#modal-greensand").modal("show");
    });
    Livewire.on("hideModalGreensand", () => {
        $("#modal-greensand").modal("hide");
    });

    // reset form saat modal ditutup manual (klik X / backdrop)
    $("#modal-greensand").on("hidden.bs.modal", function () {
        Livewire.dispatch("resetForm");
    });
});

// Event JS untuk kontrol modal
document.addEventListener("livewire:init", () => {
    Livewire.on("showModalGreensand", () =>
        $("#modal-greensand").modal("show")
    );
    Livewire.on("hideModalGreensand", () =>
        $("#modal-greensand").modal("hide")
    );
});

document.addEventListener("DOMContentLoaded", function () {
    // buka/tutup modal pakai dispatch yang ada (modal form harus mendengarkan)
    if (window.Livewire) {
        Livewire.on("openJshForm", () => {
            const el = document.getElementById("modal-greensand");
            if (el && window.jQuery) jQuery(el).modal("show");
        });

        Livewire.on("closeJshForm", () => {
            const el = document.getElementById("modal-greensand");
            if (el && window.jQuery) jQuery(el).modal("hide");
        });
    }

    // fallback: klik tombol Add selalu juga buka modal jika dispatch gagal
    const btn = document.getElementById("btn-add-greensand");
    if (btn) {
        btn.addEventListener("click", () => {
            setTimeout(() => {
                const el = document.getElementById("modal-greensand");
                if (el && window.jQuery) jQuery(el).modal("show");
            }, 50);
        });
    }
});

document.addEventListener("livewire:init", () => {
    // existing confirm-delete-batch listener already present
    Livewire.on("confirm-delete-batch", function (detail) {
        const batch = detail?.batch || null;
        const msg =
            detail?.message || "Hapus seluruh data batch " + batch + " ?";
        if (!batch) return;
        if (confirm(msg)) {
            Livewire.emit("deleteBatchConfirmed", batch);
        }
    });

    // confirm-delete-selected -> delete multiple ids
    Livewire.on("confirm-delete-selected", function (detail) {
        const batch = detail?.batch || null;
        const ids = detail?.ids || [];
        const msg =
            detail?.message ||
            "Hapus " + ids.length + " baris dari batch " + batch + " ?";
        if (!batch || !ids.length) return;
        if (confirm(msg)) {
            // emit server method: deleteSelectedConfirmed(batch, ids)
            Livewire.emit("deleteSelectedConfirmed", batch, ids);
        }
    });
});
