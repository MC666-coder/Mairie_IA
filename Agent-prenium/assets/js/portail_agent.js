/* ============================================================
   PORTAIL AGENT : menu latéral sur mobile, petits confort de l'assistant
   (le menu est toujours visible sur grand écran)
============================================================ */
document.addEventListener("DOMContentLoaded", () => {
    const menu = document.getElementById("menu-agent");
    const voile = document.getElementById("voile-menu-agent");
    const ouvrir = document.getElementById("ouvrir-menu-agent");
    const fermer = document.getElementById("fermer-menu-agent");

    function basculerMenu(ouvert) {
        if (!menu) return;
        menu.classList.toggle("-translate-x-full", !ouvert);
        menu.classList.toggle("translate-x-0", ouvert);
        if (voile) voile.hidden = !ouvert;
        ouvrir?.setAttribute("aria-expanded", ouvert ? "true" : "false");
        if (ouvert) fermer?.focus();
    }

    ouvrir?.addEventListener("click", () => basculerMenu(true));
    fermer?.addEventListener("click", () => { basculerMenu(false); ouvrir?.focus(); });
    voile?.addEventListener("click", () => basculerMenu(false));

    // Assistant IA : curseur dans le champ à l'ouverture, Échap pour fermer
    const boutonIa = document.getElementById("ia-agent-button");
    const fenetreIa = document.getElementById("ia-agent-window");
    boutonIa?.setAttribute("aria-expanded", "false");
    boutonIa?.addEventListener("click", () => {
        boutonIa.setAttribute("aria-expanded", "true");
        setTimeout(() => document.getElementById("ia-input")?.focus(), 50);
    });
    document.getElementById("ia-close-btn")?.addEventListener("click", () => {
        boutonIa?.setAttribute("aria-expanded", "false");
    });

    document.addEventListener("keydown", e => {
        if (e.key !== "Escape") return;
        if (fenetreIa && !fenetreIa.classList.contains("hidden")) {
            fenetreIa.classList.add("hidden");
            boutonIa?.setAttribute("aria-expanded", "false");
            boutonIa?.focus();
        } else if (voile && !voile.hidden) {
            basculerMenu(false);
            ouvrir?.focus();
        }
    });

    // Filtres : une liste déroulante modifiée applique aussitôt le filtre
    // (la recherche texte, elle, s'applique avec Entrée ou le bouton « Rechercher »)
    document.querySelectorAll("form[data-filtres-auto] select").forEach(liste => {
        liste.addEventListener("change", () => liste.form.submit());
    });
});
