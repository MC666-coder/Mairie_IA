/* ============================================================
   Ouverture / fermeture de l'assistant IA (espace citoyen)
   - bouton rond : ouvre / ferme (il devient une croix quand c'est ouvert)
   - croix de l'en-tête et touche Échap : ferment
   - arrivée avec ?assistant=ouvert (depuis « Accès au chatbot ») :
     la fenêtre s'ouvre toute seule, prête à écrire
============================================================ */
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("ia-toggle-btn");
    const bot = document.getElementById("chatbot");
    if (!toggleBtn || !bot) return;

    const fermerBtn = document.getElementById("chatbot-fermer");
    const champ = bot.querySelector("#message");

    function ouvrir(ouvert) {
        bot.classList.toggle("chatbot-visible", ouvert);
        bot.classList.toggle("chatbot-hidden", !ouvert);
        bot.setAttribute("aria-hidden", ouvert ? "false" : "true");
        toggleBtn.setAttribute("aria-expanded", ouvert ? "true" : "false");
        toggleBtn.setAttribute("aria-label", ouvert ? "Fermer l’assistant IA" : "Ouvrir l’assistant IA");
        if (ouvert) {
            // après l'animation d'ouverture, curseur dans le champ
            setTimeout(() => champ?.focus(), 150);
        }
    }

    const estOuvert = () => bot.classList.contains("chatbot-visible");

    ouvrir(false);
    toggleBtn.addEventListener("click", () => ouvrir(!estOuvert()));
    fermerBtn?.addEventListener("click", () => {
        ouvrir(false);
        toggleBtn.focus();
    });
    document.addEventListener("keydown", e => {
        if (e.key === "Escape" && estOuvert()) {
            ouvrir(false);
            toggleBtn.focus();
        }
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get("assistant") === "ouvert") {
        ouvrir(true);
        // On retire le paramètre de l'adresse : un rechargement ne rouvre pas le chat
        params.delete("assistant");
        const reste = params.toString();
        history.replaceState(null, "", window.location.pathname + (reste ? "?" + reste : "") + window.location.hash);
    }
});
