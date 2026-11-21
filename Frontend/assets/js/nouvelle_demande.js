/* ============================================================
   NOUVELLE DEMANDE (Frontend/nouvelle_demande.php)
   - Récapitulatif rempli en direct à droite du formulaire
   - Compteur de caractères du message
   - Liste des choix qui manquent encore avant l'envoi
   Le serveur revérifie tout (traitement_nouvelle_demande.php).
============================================================ */
(function () {
    const form = document.getElementById("form-nouvelle-demande");
    if (!form) return;

    const message = form.querySelector("#message");
    const compteur = document.getElementById("compteur-message");
    const manquant = document.getElementById("recap-manquant");
    const bouton = document.getElementById("envoyer-demande");
    const min = Number(message.getAttribute("minlength")) || 0;
    const max = Number(compteur.dataset.max) || 0;

    const NOMS = { services_id: "le service", type: "le type", priorite: "la priorité" };

    // Valeur pas encore choisie : texte gris clair au lieu de foncé
    function vide(element, estVide) {
        element.classList.toggle("text-slate-400", estVide);
        element.classList.toggle("text-slate-800", !estVide);
    }

    function choix(nom) {
        return form.querySelector('input[name="' + nom + '"]:checked');
    }

    function majRecap() {
        const manque = [];

        Object.keys(NOMS).forEach(nom => {
            const coche = choix(nom);
            const cible = form.querySelector('[data-recap="' + nom + '"]');
            cible.textContent = coche ? coche.dataset.libelle : cible.dataset.vide;
            vide(cible, !coche);

            // Recopie l'icône de la tuile choisie (service, type)
            const icone = form.querySelector('[data-recap-icone="' + nom + '"]');
            const svg = coche && coche.closest("label").querySelector(".tuile-icone svg");
            if (icone && svg) {
                icone.replaceChildren(svg.cloneNode(true));
                icone.classList.replace("text-slate-400", "text-[#003b8e]");
            }
            if (!coche) manque.push(NOMS[nom]);
        });

        const texte = message.value.trim();
        const cibleMessage = form.querySelector('[data-recap="message"]');
        cibleMessage.textContent = texte || cibleMessage.dataset.vide;
        vide(cibleMessage, !texte);
        if (texte.length < min) manque.push("un message d’au moins " + min + " caractères");

        // Compteur : orange près de la limite
        const longueur = message.value.length;
        compteur.textContent = longueur + " / " + max;
        compteur.classList.toggle("text-amber-600", longueur > max * 0.9);

        manquant.hidden = manque.length === 0;
        manquant.textContent = manque.length ? "Il manque encore : " + manque.join(", ") + "." : "";
    }

    form.addEventListener("change", majRecap);
    message.addEventListener("input", majRecap);

    // Empêche le double envoi
    form.addEventListener("submit", () => {
        bouton.disabled = true;
        bouton.lastChild.textContent = " Envoi en cours…";
    });

    majRecap();
})();
