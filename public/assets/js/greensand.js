// Filter
document.addEventListener("livewire:init", () => {
    const $ = window.jQuery;
    if (!$) return;
    const $col = $("#filterCollapse"),
        $icon = $("#filterIcon"),
        $header = $("#filterHeader");
    if ($col.length) {
        $col.collapse({ toggle: false });
        let t = null;

        function setIcon(v) {
            if ($icon.length)
                $icon
                    .removeClass("ri-add-line ri-subtract-line")
                    .addClass(v ? "ri-subtract-line" : "ri-add-line");
        }
        function getLW() {
            const r = $col.closest("[wire\\:id]");
            return r.length && window.Livewire
                ? window.Livewire.find(r.attr("wire:id"))
                : null;
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

        function reconcile() {
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
            } else if (isShown !== lastState) setIcon(isShown);
        }

        function schedule() {
            if (t) clearTimeout(t);
            t = setTimeout(reconcile, 80);
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

        document.addEventListener("DOMContentLoaded", schedule);
        document.addEventListener("livewire:navigated", schedule);
        window.Livewire?.hook?.("message.processed", schedule);
    }
});

// Modal
(() => {
    const $ = window.jQuery;
    if (!$) return;
    window.addEventListener("gs:open", () =>
        $("#modal-greensand").modal("show"),
    );
    window.addEventListener("gs:close", () =>
        $("#modal-greensand").modal("hide"),
    );
    window.addEventListener("gs:export", (e) => {
        const d = e.detail;
        const url = (d && (d.url || (Array.isArray(d) && d[0]?.url))) || null;
        if (url) window.location.href = url;
    });
})();
