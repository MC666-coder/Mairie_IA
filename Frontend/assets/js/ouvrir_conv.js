document.addEventListener("DOMContentLoaded", function () {

    const openBtn = document.getElementById("open-conversation");
    const closeBtn = document.getElementById("close-conversation");
    const overlay = document.getElementById("conversation-overlay");
    const modal = document.getElementById("conversation-modal");

    if (!openBtn || !closeBtn || !overlay || !modal) return;

    // Ouvrir la conversation
    openBtn.addEventListener("click", () => {
        modal.classList.add("active");
        overlay.classList.add("active");
    });

    // Fermer via la croix
    closeBtn.addEventListener("click", () => {
        modal.classList.remove("active");
        overlay.classList.remove("active");
    });

    // Fermer en cliquant sur le fond flouté
    overlay.addEventListener("click", () => {
        modal.classList.remove("active");
        overlay.classList.remove("active");
    });

});
