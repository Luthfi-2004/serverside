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
