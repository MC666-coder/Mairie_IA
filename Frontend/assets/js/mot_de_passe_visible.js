/* =========================================================
   Bouton « œil » des champs mot de passe (formulaires de connexion / inscription)
   Affiche ou masque le mot de passe ; l'icône change via aria-pressed (CSS .carte-auth).
   Fichier externe : compatible avec la CSP « script-src 'self' » des pages de connexion.
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".voir-mot-de-passe").forEach((bouton) => {
        const champ = document.getElementById(bouton.getAttribute("aria-controls"));
        if (!champ) return;

        bouton.addEventListener("click", () => {
            const visible = champ.type === "password";
            champ.type = visible ? "text" : "password";
            bouton.setAttribute("aria-pressed", String(visible));
            bouton.setAttribute("aria-label", visible ? "Masquer le mot de passe" : "Afficher le mot de passe");
            champ.focus();
        });
    });
});
