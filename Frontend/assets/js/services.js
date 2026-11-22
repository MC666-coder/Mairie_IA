/* ============================================================
   SERVICES (Frontend/Services.php) : recherche en direct
   ------------------------------------------------------------
   Chaque tuile porte data-recherche : nom + description + mots-clés
   + synonymes (Backend/Fonctions/synonymes_services.php).
   Le citoyen peut écrire comme il parle : « mon père est mort »,
   « je veux construire une piscine », « poubelles pas ramassées ».
   1. on ignore accents, majuscules et petits mots (je, veux, mon, de…)
   2. chaque mot restant est comparé aux mots du service :
      même mot, pluriel (poubelles = poubelle), début de mot
      (inscri → inscription), même racine (inscrire ≈ inscription)
   3. on affiche seulement le ou les services qui correspondent le mieux ;
      si aucun mot ne correspond, aucun service (message « aucun résultat »)
============================================================ */
(function () {
    const champ = document.getElementById("recherche-services");
    const tuiles = document.querySelectorAll(".tuile-service");
    const resultat = document.getElementById("resultat-recherche");
    const aucun = document.getElementById("aucun-service");
    if (!champ || !tuiles.length) return;

    // Petits mots qui ne disent rien du besoin (sans accents)
    const MOTS_VIDES = new Set((
        "je j tu il elle on nous vous ils elles le la les l un une des de du d au aux a en et ou mais donc or ni car " +
        "pour par sur sous dans avec sans chez vers entre mon ma mes ton ta tes son sa ses notre nos votre vos leur leurs " +
        "ce cet cette ces ca cela ceci qui que quoi qu quel quelle quels quelles comment pourquoi quand combien " +
        "est suis es sont etre ete ai as avons avez ont avoir veux voudrais voudrait souhaite souhaiterais aimerais " +
        "besoin faire fait fais peux peut pouvoir dois doit devoir faut y ne pas plus tres bien merci bonjour svp stp " +
        "moi toi lui me te se s n c m t aussi encore deja tout tous toute toutes mairie demande demander demandes service services"
    ).split(" "));

    // « Éclairages » → « eclairage » : sans accents, minuscules, sans pluriel simple
    const sansAccents = texte => texte.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
    const racine = mot => (mot.length > 3 && /[sx]$/.test(mot) ? mot.slice(0, -1) : mot);
    const decouper = texte => sansAccents(texte).split(/[^a-z0-9]+/).filter(Boolean);

    // Mots de chaque tuile, préparés une seule fois
    const index = [...tuiles].map(tuile => ({
        tuile,
        mots: [...new Set(decouper(tuile.dataset.recherche || "").map(racine))]
    }));

    /** Deux mots se ressemblent-ils assez ? (mot tapé, mot du service) */
    function correspond(tape, mot) {
        if (tape === mot) return true;
        // début de mot : « inscri » → « inscription » (au moins 3 lettres tapées)
        if (tape.length >= 3 && mot.startsWith(tape)) return true;
        // même racine : « inscrire » ≈ « inscription », « construire » ≈ « construction »
        let commun = 0;
        while (commun < tape.length && commun < mot.length && tape[commun] === mot[commun]) commun++;
        return commun >= 6 || (commun >= 5 && commun >= Math.min(tape.length, mot.length) - 1);
    }

    function filtrer() {
        const tapes = [...new Set(decouper(champ.value).filter(m => !MOTS_VIDES.has(m)).map(racine))];
        // Que des petits mots (« je veux… » en cours de frappe) : on montre encore tout
        const recherche = tapes.length > 0;

        // Score de chaque service = nombre de mots tapés qu'il reconnaît
        const scores = index.map(({ mots }) =>
            tapes.filter(tape => mots.some(mot => correspond(tape, mot))).length);
        const meilleur = Math.max(0, ...scores);

        let visibles = 0;
        index.forEach(({ tuile }, i) => {
            // sans recherche : tout ; sinon seulement les meilleurs (et au moins un mot reconnu)
            const montre = !recherche || (meilleur > 0 && scores[i] === meilleur);
            tuile.hidden = !montre;
            if (montre) visibles++;
        });

        aucun.hidden = visibles > 0;
        resultat.textContent = !recherche ? ""
            : visibles === 0 ? "Aucun service ne correspond."
            : visibles === 1 ? "Le service qui correspond à votre recherche :"
            : visibles + " services correspondent à votre recherche :";
    }

    champ.addEventListener("input", filtrer);
    // Entrée : pas de rechargement de la page
    document.getElementById("form-recherche-services")?.addEventListener("submit", e => e.preventDefault());
})();
