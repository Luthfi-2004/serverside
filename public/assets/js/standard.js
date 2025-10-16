(function () {
    function baseName(name) {
        const m = name && name.match(/^(.*)_(min|max)$/);
        return m ? m[1] : null;
    }

    // Normalisasi angka
    function normalize(el) {
        if (!el) return;
        el.value = el.value.replace(",", ".");
        const v = el.value.trim();
        if (v === "") return;
        const n = Number(v);
        if (!isNaN(n)) {
            let s = String(n);
            if (s.indexOf(".") >= 0) s = s.replace(/\.?0+$/, "");
            el.value = s;
        }
    }

    // Pasang handler
    function attach(form) {
        const inputs = Array.from(form.querySelectorAll("input.std-num"));
        if (!inputs.length) return;

        // Kelompok pair
        const groups = {};

        // Inisiasi input
        inputs.forEach(function (el) {
            // Ketik ganti
            el.addEventListener("input", function () {
                this.value = this.value.replace(",", ".");
            });

            // Blur normalisasi
            el.addEventListener("blur", function () {
                normalize(this);

                // Cek pasangan
                const b = baseName(this.name);
                if (!b) return;
                groups[b] = groups[b] || {};
                if (/_min$/.test(this.name)) groups[b].min = this;
                if (/_max$/.test(this.name)) groups[b].max = this;

                // Auto tukar
                const g = groups[b];
                if (g.min && g.max) {
                    const a = g.min.value.trim();
                    const z = g.max.value.trim();
                    if (
                        a !== "" &&
                        z !== "" &&
                        !isNaN(Number(a)) &&
                        !isNaN(Number(z))
                    ) {
                        const amin = Number(a),
                            amax = Number(z);
                        if (amin > amax) {
                            g.min.value = String(amax).replace(/\.?0+$/, "");
                            g.max.value = String(amin).replace(/\.?0+$/, "");
                        }
                    }
                }
            });

            // Seed pasangan
            const b = baseName(el.name);
            if (b) {
                groups[b] = groups[b] || {};
                if (/_min$/.test(el.name)) groups[b].min = el;
                if (/_max$/.test(el.name)) groups[b].max = el;
            }
        });

        // Jelang submit
        form.addEventListener("submit", function () {
            Object.values(groups).forEach(function (g) {
                if (g.min) normalize(g.min);
                if (g.max) normalize(g.max);
                if (g.min && g.max) {
                    const a = g.min.value.trim();
                    const z = g.max.value.trim();
                    if (
                        a !== "" &&
                        z !== "" &&
                        !isNaN(Number(a)) &&
                        !isNaN(Number(z))
                    ) {
                        const amin = Number(a),
                            amax = Number(z);
                        if (amin > amax) {
                            g.min.value = String(amax).replace(/\.?0+$/, "");
                            g.max.value = String(amin).replace(/\.?0+$/, "");
                        }
                    }
                }
            });
        });
    }

    // DOM siap
    document.addEventListener("DOMContentLoaded", function () {
        // Pasang semua
        document.querySelectorAll("form").forEach(attach);

        // Auto dismiss
        document.querySelectorAll(".alert.auto-dismiss").forEach(function (el) {
            var timeout = parseInt(
                el.getAttribute("data-timeout") || "3000",
                10
            );
            setTimeout(function () {
                // Coba bootstrap
                if (window.jQuery && jQuery.fn && jQuery.fn.alert) {
                    try {
                        jQuery(el).alert("close");
                        return;
                    } catch (e) {}
                }
                // Fallback manual
                el.classList.remove("show");
                el.classList.add("fade");
                setTimeout(function () {
                    if (el && el.parentNode) el.parentNode.removeChild(el);
                }, 150);
            }, timeout);
        });
    });
})();
