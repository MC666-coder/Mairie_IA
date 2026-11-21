/* =========================================================
   Gestion d'une demande : historique des actions + notes internes
   ---------------------------------------------------------
   La page contient TOUT l'historique et TOUTES les notes, du plus récent
   au plus ancien. Ce script choisit ce qui est visible :

   HISTORIQUE
   - « Toutes » : les 5 dernières actions, tous types confondus ;
   - Statuts / Assignations / Notes : les 5 dernières de ce type ;
   - « Afficher tout » : toutes les actions correspondantes ;
   - recherche : sur toutes les actions.

   NOTES INTERNES
   - par défaut : les 5 dernières notes ;
   - « Afficher tout » : toutes ;
   - 24h / Cette semaine / Mois : TOUTES les notes de la période ;
   - recherche : sur toutes les notes.

   Affichage (UX) :
   - le bouton est toujours SOUS la liste, et toujours visible : il
     indique « Tout est affiché » quand il n'y a rien de plus à montrer ;
   - une liste longue reste dans un cadre qui défile (la page ne
     s'allonge pas), avec « Réduire » en haut ET en bas.

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
        // mois : « AAAA-MM » choisi dans le filtre « Mois »
        notes: { tout: false, periode: "all", recherche: "", mois: null }
    };

    const NOMS_MOIS = ["janvier", "février", "mars", "avril", "mai", "juin", "juillet",
                       "août", "septembre", "octobre", "novembre", "décembre"];

    /** « AAAA-MM » d'une date, pour regrouper par mois. */
    function cleMois(date) {
        return date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}` : "";
    }

    /** « 2026-11 » -> « novembre 2026 » */
    function libelleMois(cle) {
        const [annee, mois] = cle.split("-");
        return `${NOMS_MOIS[parseInt(mois, 10) - 1]} ${annee}`;
    }

    /* ---------- Outils ---------- */

    /** Date « AAAA-MM-JJTHH:MM:SS » -> Date locale (fiable dans tous les navigateurs). */
    function lireDate(texte) {
        const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/.exec(texte || "");
        return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]) : null;
    }

    /** Début de la semaine en cours (lundi 00h00). */
    function debutSemaine(maintenant) {
        const d = new Date(maintenant.getFullYear(), maintenant.getMonth(), maintenant.getDate());
        d.setDate(d.getDate() - (d.getDay() + 6) % 7);      // lundi = 0
        return d;
    }

    function dansLaPeriode(date, periode) {
        if (periode === "all") return true;
        if (!date) return false;
        const maintenant = new Date();
        if (periode === "24h")   return maintenant - date <= 24 * 60 * 60 * 1000;
        if (periode === "week")  return date >= debutSemaine(maintenant);
        if (periode === "month") return cleMois(date) === etat.notes.mois;   // mois choisi
        return true;
    }

    /** Masque les groupes de dates dont aucun élément n'est visible. */
    function masquerGroupesVides(conteneur, selecteurGroupe, selecteurItem) {
        conteneur.querySelectorAll(selecteurGroupe).forEach(groupe => {
            groupe.hidden = ![...groupe.querySelectorAll(selecteurItem)].some(i => !i.hidden);
        });
    }

    /**
     * Met à jour le bouton du bas, le « Réduire » du haut et le cadre déroulant.
     * @param {object} o  { liste, bouton, boutonHaut, masquables, total, tout, nom, nomPluriel, libelleReduire }
     */
    function majAffichage(o) {
        if (o.bouton) {
            o.bouton.hidden = false;
            if (o.tout && o.masquables > 0) {
                o.bouton.textContent = " " + o.libelleReduire;
                o.bouton.prepend(iconeElement("chevron-haut"));
                o.bouton.disabled = false;
                o.bouton.setAttribute("aria-expanded", "true");
            } else if (o.masquables > 0) {
                o.bouton.textContent = ` Afficher tout (${o.total} ${o.total > 1 ? o.nomPluriel : o.nom})`;
                o.bouton.prepend(iconeElement("chevron-bas"));
                o.bouton.disabled = false;
                o.bouton.setAttribute("aria-expanded", "false");
            } else {
                // Rien de plus à montrer : le bouton reste là, mais informatif
                o.bouton.textContent = o.total
                    ? `Tout est affiché (${o.total} ${o.total > 1 ? o.nomPluriel : o.nom})`
                    : `Aucune ${o.nom}`;            // « Aucune note », « Aucune action »
                o.bouton.disabled = true;
                o.bouton.removeAttribute("aria-expanded");
            }
        }
        if (o.boutonHaut) {
            o.boutonHaut.hidden = !(o.tout && o.masquables > 0);
        }
        // Liste longue : cadre qui défile, pour que la page ne s'allonge pas
        o.liste.classList.toggle("liste-deroulante", o.visibles > LIMITE);
    }

    /** Après « Réduire », on remet le haut de la section sous les yeux. */
    function revenirEnHaut(liste) {
        liste.scrollTop = 0;
        liste.closest("section")?.scrollIntoView({ block: "start", behavior: "smooth" });
    }

    /* ---------- Historique ---------- */

    const btnHistorique = document.getElementById("show-full-history");
    const btnHistoriqueHaut = document.getElementById("reduire-historique-haut");
    const compteurHistorique = document.getElementById("history-compteur");
    const rechercheHistorique = document.getElementById("history-search-input");

    function appliquerHistorique() {
        if (!timeline) return;
        const e = etat.historique;
        const items = [...timeline.querySelectorAll(".history-item")];
        let visibles = 0;
        let correspondants = 0;

        items.forEach(item => {           // déjà du plus récent au plus ancien
            const type = item.dataset.type || "other";
            const texte = (item.dataset.text || "").toLowerCase();
            let ok = (e.type === "all" || type === e.type)
                  && (!e.recherche || texte.includes(e.recherche));
            if (ok) {
                correspondants++;
                // Vue réduite : les 5 dernières (« Toutes » = tous types confondus,
                // sinon les 5 dernières du type filtré)
                if (!e.tout && !e.recherche && correspondants > LIMITE) ok = false;
            }
            item.hidden = !ok;
            if (ok) visibles++;
        });

        masquerGroupesVides(timeline, ".history-date-group", ".history-item");
        majAffichage({
            liste: timeline, bouton: btnHistorique, boutonHaut: btnHistoriqueHaut, visibles,
            masquables: e.recherche ? 0 : Math.max(0, correspondants - LIMITE),
            total: correspondants, tout: e.tout,
            nom: "action", nomPluriel: "actions", libelleReduire: "Réduire l’historique"
        });
        if (compteurHistorique) {
            compteurHistorique.textContent = items.length
                ? `${visibles} action${visibles > 1 ? "s" : ""} affichée${visibles > 1 ? "s" : ""} sur ${correspondants}`
                : "";
        }
    }

    function basculerHistorique() {
        etat.historique.tout = !etat.historique.tout;
        appliquerHistorique();
        if (!etat.historique.tout) revenirEnHaut(timeline);
    }
    btnHistorique?.addEventListener("click", basculerHistorique);
    btnHistoriqueHaut?.addEventListener("click", basculerHistorique);

    document.querySelectorAll(".history-filter-btn").forEach(btn => {
        btn.setAttribute("aria-pressed", String(btn.classList.contains("active")));
        btn.addEventListener("click", () => {
            document.querySelectorAll(".history-filter-btn").forEach(b => {
                b.classList.toggle("active", b === btn);
                b.setAttribute("aria-pressed", String(b === btn));
            });
            etat.historique.type = btn.dataset.filter || "all";
            etat.historique.tout = false;            // nouveau filtre : vue réduite
            appliquerHistorique();
        });
    });

    rechercheHistorique?.addEventListener("input", () => {
        etat.historique.recherche = rechercheHistorique.value.trim().toLowerCase();
        appliquerHistorique();
    });

    /* ---------- Notes internes ---------- */

    const btnNotes = document.getElementById("show-full-notes");
    const btnNotesHaut = document.getElementById("reduire-notes-haut");
    const compteurNotes = document.getElementById("notes-compteur");
    const rechercheNotes = document.getElementById("notes-search-input");

    const rangeeMois = document.getElementById("notes-mois");

    /**
     * Filtre « Mois » : un bouton par mois contenant des notes (du plus
     * récent au plus ancien, avec le nombre de notes). Le mois le plus
     * récent est choisi par défaut.
     */
    function afficherMois(items) {
        if (!rangeeMois) return;
        if (etat.notes.periode !== "month") {
            rangeeMois.hidden = true;
            return;
        }
        const compte = new Map();
        items.forEach(n => {
            const cle = cleMois(lireDate(n.dataset.createdAt));
            if (cle) compte.set(cle, (compte.get(cle) || 0) + 1);
        });
        const mois = [...compte.keys()].sort().reverse();
        if (!mois.includes(etat.notes.mois)) etat.notes.mois = mois[0] || null;

        rangeeMois.replaceChildren();
        mois.forEach(cle => {
            const bouton = document.createElement("button");
            bouton.type = "button";
            bouton.className = "mois-btn" + (cle === etat.notes.mois ? " active" : "");
            bouton.setAttribute("aria-pressed", String(cle === etat.notes.mois));
            const libelle = libelleMois(cle);
            bouton.textContent = `${libelle.charAt(0).toUpperCase()}${libelle.slice(1)} · ${compte.get(cle)}`;
            bouton.addEventListener("click", () => {
                etat.notes.mois = cle;
                appliquerNotes();
            });
            rangeeMois.append(bouton);
        });
        rangeeMois.hidden = mois.length === 0;
    }

    function appliquerNotes() {
        if (!listeNotes) return;
        const e = etat.notes;
        const items = [...listeNotes.querySelectorAll(".note-item")];
        afficherMois(items);
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
        majAffichage({
            liste: listeNotes, bouton: btnNotes, boutonHaut: btnNotesHaut, visibles,
            // Le bouton n'a de sens que pour la vue « Tous » sans recherche
            masquables: (e.periode === "all" && !e.recherche) ? Math.max(0, correspondants - LIMITE) : 0,
            total: correspondants, tout: e.tout,
            nom: "note", nomPluriel: "notes", libelleReduire: "Réduire les notes"
        });
        if (compteurNotes) {
            const periode = { all: "", "24h": " sur les dernières 24 heures", week: " cette semaine",
                              month: e.mois ? ` en ${libelleMois(e.mois)}` : "" }[e.periode];
            compteurNotes.textContent = items.length
                ? (correspondants === 0
                    ? `Aucune note${periode}.`
                    : `${visibles} note${visibles > 1 ? "s" : ""} affichée${visibles > 1 ? "s" : ""} sur ${correspondants}${periode}`)
                : "";
        }
    }

    function basculerNotes() {
        etat.notes.tout = !etat.notes.tout;
        appliquerNotes();
        if (!etat.notes.tout) revenirEnHaut(listeNotes);
    }
    btnNotes?.addEventListener("click", basculerNotes);
    btnNotesHaut?.addEventListener("click", basculerNotes);

    document.querySelectorAll(".notes-filter-btn").forEach(btn => {
        btn.setAttribute("aria-pressed", String(btn.classList.contains("active")));
        btn.addEventListener("click", () => {
            document.querySelectorAll(".notes-filter-btn").forEach(b => {
                b.classList.toggle("active", b === btn);
                b.setAttribute("aria-pressed", String(b === btn));
            });
            etat.notes.periode = btn.dataset.filter || "all";
            etat.notes.tout = false;
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
