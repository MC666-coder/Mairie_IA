/* ============================================================
   SERVICES (Frontend/Services.php) : recherche en direct
   Chaque tuile porte data-recherche (nom + description + mots-clés).
   On ignore les accents et les majuscules : « ecole » trouve « École ».
============================================================ */
(function () {
    const champ = document.getElementById("recherche-services");
    const tuiles = document.querySelectorAll(".tuile-service");
    const resultat = document.getElementById("resultat-recherche");
    const aucun = document.getElementById("aucun-service");
    if (!champ || !tuiles.length) return;

    // « Éclairage » → « eclairage »
    const normaliser = texte => texte.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "").trim();

    // Texte de chaque tuile, normalisé une seule fois
    const index = [...tuiles].map(tuile => ({ tuile, texte: normaliser(tuile.dataset.recherche || "") }));

    function filtrer() {
        const mots = normaliser(champ.value).split(/\s+/).filter(Boolean);
        let visibles = 0;

        index.forEach(({ tuile, texte }) => {
            // Tous les mots tapés doivent être trouvés (« permis piscine »)
            const correspond = mots.every(mot => texte.includes(mot));
            tuile.hidden = !correspond;
            if (correspond) visibles++;
        });

        aucun.hidden = visibles > 0;
        resultat.textContent = mots.length
            ? (visibles === 0 ? "Aucun service trouvé."
                : visibles + " service" + (visibles > 1 ? "s" : "") + " correspond" + (visibles > 1 ? "ent" : "") + " à votre recherche.")
            : "";
    }

    champ.addEventListener("input", filtrer);
    // Entrée : pas de rechargement de la page
    document.getElementById("form-recherche-services")?.addEventListener("submit", e => e.preventDefault());
})();
