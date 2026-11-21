/* =========================================================
   Menus de l'en-tête (Backend/Fonctions/gabarit_public.php)
   - bouton burger (mobile) et rond d'initiales (profil citoyen)
   - un bouton [data-bascule="id"] ouvre / ferme l'élément #id
   - fermeture au clic en dehors et avec la touche Échap
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const boutons = [...document.querySelectorAll("[data-bascule]")];

    const fermer = (bouton) => {
        const cible = document.getElementById(bouton.dataset.bascule);
        if (!cible) return;
        cible.classList.add("hidden");
        cible.classList.remove("flex");
        bouton.setAttribute("aria-expanded", "false");
    };

    boutons.forEach((bouton) => {
        const cible = document.getElementById(bouton.dataset.bascule);
        if (!cible) return;

        bouton.addEventListener("click", (e) => {
            e.stopPropagation();
            const ouvrir = cible.classList.contains("hidden");
            boutons.filter((b) => b !== bouton).forEach(fermer);
            cible.classList.toggle("hidden", !ouvrir);
            cible.classList.toggle("flex", ouvrir);
            bouton.setAttribute("aria-expanded", String(ouvrir));
        });
        cible.addEventListener("click", (e) => e.stopPropagation());
    });

    document.addEventListener("click", () => boutons.forEach(fermer));
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") boutons.forEach(fermer);
    });
});
