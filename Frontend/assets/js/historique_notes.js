/* =========================================================
   Gestion d'une demande : historique des actions + notes internes
   ---------------------------------------------------------
   La page contient TOUT l'historique et TOUTES les notes, du plus récent
   au plus ancien. Ce script choisit ce qui est visible :

   HISTORIQUE
   - par défaut : les 5 dernières actions de CHAQUE type (statuts,
     assignations, notes internes, autres) ;
   - « Afficher tout l'historique » : toutes les actions ;
   - filtre Statuts / Assignations / Notes : même règle, sur ce type ;
   - recherche : sur toutes les actions.

   NOTES INTERNES
   - par défaut : les 5 dernières notes ;
   - « Afficher toutes les notes » : toutes ;
   - 24h / Cette semaine / Mois : TOUTES les notes de la période ;
   - recherche : sur toutes les notes.

   Après chaque action (statut, assignation, note, pièce jointe, réponse),
   window.rafraichirHistoriqueNotes() recharge l'historique et les notes
   depuis le serveur (Backend/ajax/historique_notes.php).
   ========================================================= */
(() => {
    const LIMITE = 5;

    const timeline = document.getElementById("history-timeline");
    const listeNotes = document.getElementById("liste-notes");
    if (!timeline && !listeNotes) {
        return;
    }

    const etat = {
        historique: { tout: false, type: "all", recherche: "" },
        notes: { tout: false, periode: "all", recherche: "" }
    };

    /* ---------- Outils ---------- */

    /** Date « AAAA-MM-JJTHH:MM:SS » -> Date locale (fiable dans tous les navigateurs). */
    function lireDate(texte) {
        const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/.exec(texte || "");
        return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]) : null;
    }

    /** Début de la semaine en cours (lundi 00h00). */
    function debutSemaine(maintenant) {
        const d = new Date(maintenant.getFullYear(), maintenant.getMonth(), maintenant.getDate());
        const decalage = (d.getDay() + 6) % 7;      // lundi = 0
        d.setDate(d.getDate() - decalage);
        return d;
    }

    function dansLaPeriode(date, periode) {
        if (periode === "all") return true;
        if (!date) return false;
        const maintenant = new Date();
        if (periode === "24h")   return maintenant - date <= 24 * 60 * 60 * 1000;
        if (periode === "week")  return date >= debutSemaine(maintenant);
        if (periode === "month") return date.getFullYear() === maintenant.getFullYear()
                                      && date.getMonth() === maintenant.getMonth();
        return true;
    }

    /** Masque les groupes de dates dont aucun élément n'est visible. */
    function masquerGroupesVides(conteneur, selecteurGroupe, selecteurItem) {
        conteneur.querySelectorAll(selecteurGroupe).forEach(groupe => {
            const visible = [...groupe.querySelectorAll(selecteurItem)].some(i => !i.hidden);
            groupe.hidden = !visible;
        });
    }

    /**
     * @param masquables nombre d'éléments que la vue réduite cache :
     *        s'il est nul, le bouton ne sert à rien et disparaît.
     */
    function majBouton(bouton, masquables, total, toutAffiche, libelleTout, libelleReduire) {
        if (!bouton) return;
        bouton.hidden = masquables === 0;
        bouton.textContent = toutAffiche ? libelleReduire : `${libelleTout} (${total})`;
        bouton.setAttribute("aria-expanded", String(toutAffiche));
    }

    /* ---------- Historique ---------- */

    const btnHistorique = document.getElementById("show-full-history");
    const compteurHistorique = document.getElementById("history-compteur");
    const rechercheHistorique = document.getElementById("history-search-input");

    function appliquerHistorique() {
        if (!timeline) return;
        const e = etat.historique;
        const items = [...timeline.querySelectorAll(".history-item")];
        const parType = {};
        let visibles = 0;
        let correspondants = 0;
        let masquables = 0;               // ce que la vue réduite cache

        items.forEach(item => {           // déjà du plus récent au plus ancien
            const type = item.dataset.type || "other";
            const texte = (item.dataset.text || "").toLowerCase();
            let ok = (e.type === "all" || type === e.type)
                  && (!e.recherche || texte.includes(e.recherche));

            if (ok) {
                correspondants++;
                // Vue réduite : les 5 derniers de chaque type
                parType[type] = (parType[type] || 0) + 1;
                const auDela = parType[type] > LIMITE;
                if (auDela) masquables++;
                if (auDela && !e.tout && !e.recherche) ok = false;
            }
            item.hidden = !ok;
            if (ok) visibles++;
        });

        masquerGroupesVides(timeline, ".history-date-group", ".history-item");
        majBouton(btnHistorique, e.recherche ? 0 : masquables, correspondants, e.tout,
                  "Afficher tout l’historique", "Réduire l’historique");
        if (compteurHistorique) {
            compteurHistorique.textContent = items.length
                ? `${visibles} action${visibles > 1 ? "s" : ""} affichée${visibles > 1 ? "s" : ""} sur ${correspondants}`
                : "";
        }
    }

    btnHistorique?.addEventListener("click", () => {
        etat.historique.tout = !etat.historique.tout;
        appliquerHistorique();
    });

    document.querySelectorAll(".history-filter-btn").forEach(btn => {
        btn.setAttribute("aria-pressed", String(btn.classList.contains("active")));
        btn.addEventListener("click", () => {
            document.querySelectorAll(".history-filter-btn").forEach(b => {
                b.classList.toggle("active", b === btn);
                b.setAttribute("aria-pressed", String(b === btn));
            });
            etat.historique.type = btn.dataset.filter || "all";
            appliquerHistorique();
        });
    });

    rechercheHistorique?.addEventListener("input", () => {
        etat.historique.recherche = rechercheHistorique.value.trim().toLowerCase();
        appliquerHistorique();
    });

    /* ---------- Notes internes ---------- */

    const btnNotes = document.getElementById("show-full-notes");
    const compteurNotes = document.getElementById("notes-compteur");
    const rechercheNotes = document.getElementById("notes-search-input");

    function appliquerNotes() {
        if (!listeNotes) return;
        const e = etat.notes;
        const items = [...listeNotes.querySelectorAll(".note-item")];
        // Une période ou une recherche montre TOUTES les notes correspondantes
        const limiter = !e.tout && e.periode === "all" && !e.recherche;
        let visibles = 0;
        let correspondants = 0;

        items.forEach(note => {           // déjà de la plus récente à la plus ancienne
            const texte = (note.dataset.text || "").toLowerCase();
            let ok = dansLaPeriode(lireDate(note.dataset.createdAt), e.periode)
                  && (!e.recherche || texte.includes(e.recherche));
            if (ok) {
                correspondants++;
                if (limiter && correspondants > LIMITE) ok = false;
            }
            note.hidden = !ok;
            if (ok) visibles++;
        });

        masquerGroupesVides(listeNotes, ".notes-date-group", ".note-item");
        // Le bouton n'a de sens que pour la vue « Tous » sans recherche
        const masquables = (e.periode === "all" && !e.recherche) ? Math.max(0, correspondants - LIMITE) : 0;
        majBouton(btnNotes, masquables, correspondants, e.tout, "Afficher toutes les notes", "Réduire les notes");
        if (compteurNotes) {
            const periode = { all: "", "24h": " sur les dernières 24 heures", week: " cette semaine", month: " ce mois-ci" }[e.periode];
            compteurNotes.textContent = items.length
                ? (correspondants === 0
                    ? `Aucune note${periode}.`
                    : `${visibles} note${visibles > 1 ? "s" : ""} affichée${visibles > 1 ? "s" : ""} sur ${correspondants}${periode}`)
                : "";
        }
    }

    btnNotes?.addEventListener("click", () => {
        etat.notes.tout = !etat.notes.tout;
        appliquerNotes();
    });

    document.querySelectorAll(".notes-filter-btn").forEach(btn => {
        btn.setAttribute("aria-pressed", String(btn.classList.contains("active")));
        btn.addEventListener("click", () => {
            document.querySelectorAll(".notes-filter-btn").forEach(b => {
                b.classList.toggle("active", b === btn);
                b.setAttribute("aria-pressed", String(b === btn));
            });
            etat.notes.periode = btn.dataset.filter || "all";
            appliquerNotes();
        });
    });

    rechercheNotes?.addEventListener("input", () => {
        etat.notes.recherche = rechercheNotes.value.trim().toLowerCase();
        appliquerNotes();
    });

    /* ---------- Rechargement après une action ---------- */

    const demandeId = (timeline || listeNotes).dataset.demandeId;

    window.rafraichirHistoriqueNotes = function () {
        if (!demandeId) return Promise.resolve();
        return fetch(`../Backend/ajax/historique_notes.php?demande_id=${encodeURIComponent(demandeId)}`, {
            headers: { "Accept": "application/json" },
            credentials: "same-origin",
            cache: "no-store"
        })
        .then(res => res.ok ? res.json() : Promise.reject(new Error("HTTP " + res.status)))
        .then(data => {
            if (!data.success) return;
            // HTML produit et échappé côté serveur (même gabarit que la page)
            if (timeline) timeline.innerHTML = data.historique;
            if (listeNotes) listeNotes.innerHTML = data.notes;
            appliquerHistorique();
            appliquerNotes();
        })
        .catch(err => console.error("Historique : rechargement impossible", err));
    };

    appliquerHistorique();
    appliquerNotes();
})();
