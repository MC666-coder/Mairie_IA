/* ============================================================
   DÉTAIL D'UNE DEMANDE (Agent-prenium/gestion_demande.php)
   La conversation est maintenant DANS la page (plus de fenêtre).
   demande.js « ouvre » toujours la conversation en cliquant sur le
   bouton caché #open-conversation (messages marqués comme lus,
   document du dossier à envoyer, « voir le message »…) :
   - au chargement : on le clique une fois (la conversation est visible,
     donc les messages sont lus) ;
   - ensuite : chaque « ouverture » fait défiler jusqu'à la conversation.
============================================================ */
document.addEventListener("DOMContentLoaded", () => {
    const bouton = document.getElementById("open-conversation");
    const conversation = document.getElementById("conversation");
    if (!bouton || !conversation) return;

    let chargement = true;
    bouton.addEventListener("click", () => {
        if (chargement) return;
        conversation.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    // Après les autres scripts (demande.js a branché son propre écouteur)
    setTimeout(() => {
        bouton.click();
        chargement = false;
    }, 0);
});
