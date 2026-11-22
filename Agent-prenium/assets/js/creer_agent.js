/* ============================================================
   CRÉER UN AGENT (Agent-prenium/inscription_agent.php)
   Aperçu de la fiche rempli en direct pendant la saisie.
   Le serveur revérifie tout (traitement_création_agent.php).
============================================================ */
(function () {
    const form = document.getElementById("form-creer-agent");
    if (!form) return;

    const champ = id => document.getElementById(id);
    const nom = champ("nom_agent");
    const prenom = champ("prenom_agent");
    const email = champ("email_agent");
    const service = champ("service_id");
    const role = champ("role");

    // Texte de l'aperçu, ou sa valeur par défaut (data-vide) si le champ est vide
    function ecrire(id, texte) {
        const el = champ(id);
        el.textContent = texte || el.dataset.vide;
    }

    function texteChoisi(liste) {
        return liste.value ? liste.selectedOptions[0].textContent.trim() : "";
    }

    function majApercu() {
        const p = prenom.value.trim();
        const n = nom.value.trim();
        ecrire("apercu-nom", [p, n].filter(Boolean).join(" "));
        ecrire("apercu-email", email.value.trim());
        champ("apercu-initiales").textContent = ((p[0] || "") + (n[0] || "")).toUpperCase() || "?";
        ecrire("apercu-service", texteChoisi(service));
        ecrire("apercu-role", texteChoisi(role));
        const niveau = form.querySelector('input[name="niveau_acces"]:checked');
        ecrire("apercu-niveau", niveau ? niveau.dataset.libelle : "");
    }

    form.addEventListener("input", majApercu);
    form.addEventListener("change", majApercu);
    majApercu();
})();
