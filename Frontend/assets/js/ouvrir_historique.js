document.addEventListener("DOMContentLoaded", () => {
    const timeline = document.getElementById("history-timeline");
    const btn = document.getElementById("show-full-history");

    if (!timeline || !btn) return;

    const items = timeline.querySelectorAll(".history-item");

    if (items.length <= 5) {
        btn.style.display = "none";
        return;
    }

    // Masquer toutes sauf les 5 dernières
    items.forEach((item, index) => {
        if (index < items.length - 5) {
            item.classList.add("hidden-item");
        }
    });

    // Masquer les groupes de dates vides
    const groups = timeline.querySelectorAll(".history-date-group");

    groups.forEach(group => {
        const visibleItems = group.querySelectorAll(".history-item:not(.hidden-item)");
        if (visibleItems.length === 0) {
            group.classList.add("hidden-item");
        }
    });

    // Afficher tout l’historique au clic
    btn.addEventListener("click", () => {
        items.forEach(item => item.classList.remove("hidden-item"));
        groups.forEach(group => group.classList.remove("hidden-item"));
        btn.style.display = "none";
    });
});