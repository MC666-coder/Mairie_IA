/* ============================================================
   ALERTES PREMIUM
============================================================ */
/* pour afficher le badge "Nouveaux messages" sur la liste des demandes */
let conversationDejaOuverte = false;
/* Fin de la gestion du badge "Nouveaux messages" */

/* ============================================================
   SÉCURITÉ
============================================================ */

/* Échappe une valeur avant de l'insérer dans du HTML (anti-XSS).
   À utiliser pour TOUTE donnée venant du serveur ou d'un utilisateur. */
function escapeHTML(valeur) {
    return String(valeur ?? "").replace(/[&<>"']/g, c => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[c]));
}

/* En-têtes à joindre à chaque requête POST vers Backend/ajax :
   - X-CSRF-Token : jeton anti-CSRF fourni par la page (<meta name="csrf-token">)
   - X-Requested-With : le serveur répond en JSON si la session a expiré */
function enTetesSecurite() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return {
        "X-CSRF-Token": meta ? meta.content : "",
        "X-Requested-With": "XMLHttpRequest"
    };
}

function showAlert(type, message) {
    const container = document.getElementById("alert-container");

    container.innerHTML = `
        <div class="alert ${type}" role="${type === "success" ? "status" : "alert"}">
            ${icone(type === "success" ? "valide" : "croix")}
            <span>${escapeHTML(message)}</span>
        </div>
    `;

    setTimeout(() => container.innerHTML = "", 4000);
}

/* « 2026-11-22 04:26:33 » → « 22/11/2026 à 04h26 » (même format que la page) */
function dateLisible(date) {
    const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(String(date || ""));
    return m ? `${m[3]}/${m[2]}/${m[1]} à ${m[4]}h${m[5]}` : String(date || "");
}

function showSuccess(msg) { showAlert("success", msg); }
function showError(msg) { showAlert("error", msg); }


/* ============================================================
   FONCTION AJAX GÉNÉRIQUE
============================================================ */

function ajaxAction(url, formData, onSuccess = null) {
    fetch(url, {
        method: "POST",
        headers: enTetesSecurite(),
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            if (result.message) showSuccess(result.message);
            if (typeof onSuccess === "function") onSuccess(result);
        } else {
            showError(result.message || "Une erreur est survenue.");
        }
    })
    .catch(err => {
        showError("Erreur réseau ou serveur.");
        console.error("AJAX ERROR:", err);
    });
}

/* ============================================================
   CHARGER LES MESSAGES — VERSION PREMIUM WHATSAPP-LIKE
============================================================ */

const timeline = document.getElementById("messages-timeline");

/* ------------------------------------------------------------
   Scroll intelligent (WhatsApp-like)
------------------------------------------------------------ */
function scrollTimelineSmart() {
    const isAtBottom =
        timeline.scrollHeight - timeline.scrollTop <= timeline.clientHeight + 50;

    if (isAtBottom) {
        timeline.scrollTop = timeline.scrollHeight;
    }
}

/* ------------------------------------------------------------
   Groupement par date (Aujourd’hui / Hier / etc.)
------------------------------------------------------------ */
function getHumanDateLabel(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    const d = date.toISOString().slice(0, 10);
    const t = today.toISOString().slice(0, 10);
    const y = yesterday.toISOString().slice(0, 10);

    if (d === t) return "Aujourd’hui";
    if (d === y) return "Hier";

    return date.toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long"
    });
}

let lastDateGroup = null;

function ensureDateGroup(dateStr) {
    const dateKey = dateStr.slice(0, 10);

    if (lastDateGroup === dateKey) return;

    lastDateGroup = dateKey;

    const label = getHumanDateLabel(dateStr);

    timeline.insertAdjacentHTML(
        "beforeend",
        `
        <div class="messages-date-separator">
            <span>${label}</span>
        </div>
        `
    );
}

/* ------------------------------------------------------------
   Fonction principale : charger les messages
------------------------------------------------------------ */
function chargerMessages(demandeId) {
    fetch(`../Backend/ajax/get_messages.php?demande_id=${encodeURIComponent(demandeId)}`, { headers: enTetesSecurite() })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                timeline.innerHTML = "<p><em>Impossible de charger les messages.</em></p>";
                return;
            }

            const messages = data.messages;

            // Rien de nouveau depuis le dernier affichage : on ne redessine pas
            // (évite de faire sauter la conversation à chaque rafraîchissement)
            const signature = messages.map(m => m.id).join(",");
            if (timeline.dataset.signature === signature) return;
            timeline.dataset.signature = signature;

            if (messages.length === 0) {
                timeline.innerHTML = "<p><em>Aucun message pour le moment.</em></p>";
                return;
            }

            timeline.innerHTML = "";
            lastDateGroup = null;

            messages.forEach(m => {
                const isAgent = m.auteur_type === "agent";

                const initials = isAgent
                    ? ((m.prenom || "?")[0] + (m.nom || "?")[0]).toUpperCase()
                    : "C";

                const author = isAgent
                    ? `${m.prenom} ${m.nom}`
                    : "Citoyen";

                // Ajout du séparateur de date
                ensureDateGroup(m.created_at);

                // Construction du message — toutes les valeurs sont échappées (anti-XSS) :
                // le texte d'un message citoyen ne doit JAMAIS être interprété comme du HTML
                const html = `
                    <div class="message-item ${isAgent ? "agent" : "citoyen"}"
                         data-message-id="${escapeHTML(m.id)}"
                         data-created-at="${escapeHTML(m.created_at)}"
                         data-parent-id="${escapeHTML(m.parent_id ?? "")}">

                        <div class="message-avatar">${escapeHTML(initials)}</div>

                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-author">${escapeHTML(author)}</span>
                                <span class="message-date">${escapeHTML(m.created_at)}</span>
                            </div>

                            <div class="message-body">
                                ${escapeHTML(m.message).replace(/\n/g, "<br>")}
                            </div>
                            ${m.fichier_id ? `
                            <a class="message-piece-jointe"
                               href="../Backend/ajax/download_pj.php?id=${parseInt(m.fichier_id, 10)}">
                                ${icone("trombone")} ${escapeHTML(m.fichier_nom)}
                            </a>` : ""}
                        </div>
                    </div>
                `;

                timeline.insertAdjacentHTML("beforeend", html);
            });

            scrollTimelineSmart();

        })
        .catch(err => {
            console.error("Erreur chargement messages :", err);
            timeline.innerHTML =
                "<p><em>Erreur lors du chargement des messages.</em></p>";
        });
}

/* ============================================================
   Filtres & recherche dans les messages (version ptemium WhatsApp-like)
============================================================ */
const filterButtons = document.querySelectorAll(".filter-btn");

function applyMessagesFilters(filter) {
    document.querySelectorAll(".message-item").forEach(item => {
        const isAgent = item.classList.contains("agent");
        const isCitizen = item.classList.contains("citoyen");

        if (filter === "all") item.style.display = "";
        else if (filter === "agent" && isAgent) item.style.display = "";
        else if (filter === "citoyen" && isCitizen) item.style.display = "";
        else item.style.display = "none";
    });
}




/* ============================================================
   HISTORIQUE : rechargé après chaque action
   ------------------------------------------------------------
   Chaque action est enregistrée dans l'historique PAR LE SERVEUR
   (update_statut, Assigner_agent, Ajouter_note, upload_pj,
   supprimer_pj, Ajouter_message_agent). Ici, on se contente de
   réafficher l'historique et les notes (historique_notes.js).
============================================================ */

function rafraichirHistorique() {
    if (typeof window.rafraichirHistoriqueNotes === "function") {
        window.rafraichirHistoriqueNotes();
    }
}

/* Mettre à jour le badge de nouveaux messages sur la liste des demandes */
function mettreAJourBadgeNouveauxMessages(demandeId) {
    fetch(`../Backend/ajax/get_messages.php?demande_id=${encodeURIComponent(demandeId)}`, { headers: enTetesSecurite() })
        .then(res => res.json())
        .then(data => {

            const badge = document.getElementById(`badge-nouveaux-${demandeId}`);
            const card = document.querySelector(`.demande-card[data-demande-id="${demandeId}"]`);
            const titre = card ? card.querySelector("h3") : null;

            if (badge) {

                // 1️⃣ Si nouveaux messages → toujours afficher
                if (data.nouveaux_messages > 0) {
                    badge.innerText = data.nouveaux_messages + " nouveau(x)";
                    badge.classList.add("show");
                }

                // 2️⃣ Si PAS de nouveaux messages
                else {

                    // ❗ NE PAS masquer si l’agent n’a pas encore ouvert la conversation
                    if (conversationDejaOuverte) {
                        badge.classList.remove("show");
                    }
                }
            }

            // Gestion du titre "Aucun message pour l'instant"
            if (titre && titre.textContent.trim() === "Aucun message pour l'instant") {
                if (data.messages.length > 0) {
                    titre.style.display = "none";
                } else {
                    titre.style.display = "block";
                }
            }
        });
}


/* ============================================================
   MISE À JOUR DU STATUT
============================================================ */

document.addEventListener("DOMContentLoaded", () => {
    /* S'occpuer des messages du citoyen */
    const demandeIdMessages = document.getElementById("messages-timeline")?.dataset.demandeId;
    if (demandeIdMessages) {
        chargerMessages(demandeIdMessages);
    }
    /*Fin messages*/
    const demandeIdPage = document.getElementById("liste-pj")?.dataset.demandeId;

    if (demandeIdPage) {
        chargerPiecesJointes(demandeIdPage);
    }

    /* Mise à jour automatique toutes les 15 s (onglet visible) : messages et
       documents envoyés par le citoyen depuis son portail, badge « nouveau »,
       compteur de pièces jointes et historique. */
    const demandeIdAuto = demandeIdMessages || demandeIdPage;
    if (demandeIdAuto) {
        let signatureAuto = "";
        setInterval(() => {
            if (document.hidden) return;
            chargerMessages(demandeIdAuto);
            mettreAJourBadgeNouveauxMessages(demandeIdAuto);
            if (demandeIdPage) chargerPiecesJointes(demandeIdPage);

            // Historique : seulement si les messages ou les documents ont changé
            const signature = (document.getElementById("messages-timeline")?.dataset.signature || "")
                            + "|" + (document.getElementById("pj-citoyen-count")?.textContent || "");
            if (signatureAuto && signature !== signatureAuto) rafraichirHistorique();
            signatureAuto = signature;
        }, 15000);
    }

    const formStatut = document.getElementById("form-statut");

    if (formStatut) {
        formStatut.addEventListener("submit", function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append("statut", document.getElementById("statut").value);

            const demandeId = this.dataset.demandeId;

            ajaxAction(
                `../Backend/ajax/update_statut.php?id=${demandeId}`,
                formData,
                (result) => {

                    const newStatut = formData.get("statut");

                    document.getElementById("statut").value = newStatut;

                    const badge = document.querySelector(".statut");
                    if (badge) {
                        // Libellé lisible (« En cours ») : texte de l'option choisie, pas le code (« en_cours »)
                        const choix = document.getElementById("statut").selectedOptions[0];
                        badge.textContent = choix ? choix.textContent.trim() : newStatut;
                        badge.className = "statut statut-" + newStatut;
                    }

                    if (result.date_mise_a_jour) {
                        const dateSpan = document.getElementById("date-mise-a-jour");
                        if (dateSpan) dateSpan.textContent = dateLisible(result.date_mise_a_jour);
                    }

                    rafraichirHistorique();
                }
            );
        });
    }


/* ============================================================
   ASSIGNATION À UN AGENT
============================================================ */

    const formAssignation = document.getElementById("form-assignation");

    if (formAssignation) {
        formAssignation.addEventListener("submit", function(e) {
            e.preventDefault();

            const demandeId = this.dataset.demandeId;
            const formData = new FormData(this);

            ajaxAction(
                `../Backend/ajax/Assigner_agent.php?id=${demandeId}`,
                formData,
                (result) => {

                    const agentSpan = document.getElementById("agent-actuel");
                    if (agentSpan) agentSpan.textContent = result.agent_nom;

                    // L'agent s'est assigné la demande : on recharge pour lui ouvrir
                    // la zone d'écriture au citoyen (réservée à l'agent assigné)
                    if (this.dataset.moi && formData.get("agent_id") === this.dataset.moi) {
                        setTimeout(() => window.location.reload(), 900);
                    }

                    if (result.date_mise_a_jour) {
                        const dateSpan = document.getElementById("date-mise-a-jour");
                        if (dateSpan) dateSpan.textContent = dateLisible(result.date_mise_a_jour);
                    }

                    rafraichirHistorique();
                }
            );
        });
    }


/* ============================================================
   AJOUT D'UNE NOTE INTERNE
============================================================ */

    const formNote = document.getElementById("form-note");

    if (formNote) {
        formNote.addEventListener("submit", function(e) {
            e.preventDefault();

            const demandeId = this.dataset.demandeId;
            const formData = new FormData(this);

            ajaxAction(
                `../Backend/ajax/Ajouter_note.php?id=${demandeId}`,
                formData,
                () => {
                    document.getElementById("note").value = "";
                    // La note et sa trace dans l'historique sont déjà
                    // enregistrées par Ajouter_note.php : on réaffiche les deux
                    rafraichirHistorique();
                }
            );
        });
    }


/* Filtres et recherche des NOTES : voir historique_notes.js */


/* ============================================================
   MODULE PIÈCES JOINTES
============================================================ */

    const formUploadPJ = document.querySelector(".pieces-jointes .form-upload");

    /* ------------------------------------------------------------
       Documents du dossier : trois cases (reçus du citoyen, envoyés
       au citoyen, internes). Construits avec le DOM (textContent) :
       un nom de fichier ne peut jamais être interprété comme du HTML.
    ------------------------------------------------------------ */
    function chargerPiecesJointes(demandeId) {
        fetch(`../Backend/ajax/get_pj.php?demande_id=${encodeURIComponent(demandeId)}`, { headers: enTetesSecurite() })
            .then(res => res.json())
            .then(data => {
                const conteneur = document.getElementById("liste-pj");
                if (!conteneur) return;

                const majCompteur = (id, valeur) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = valeur ?? 0;
                };
                majCompteur("pj-count", data.count);
                majCompteur("pj-citoyen-count", data.count_citoyen);   // fiche de la demande
                majCompteur("pj-count-recus", data.recus?.length);
                majCompteur("pj-count-envoyes", data.envoyes?.length);
                majCompteur("pj-count-internes", data.internes?.length);

                // Rien de nouveau : on ne redessine pas (rafraîchissement automatique)
                const signature = JSON.stringify(data);
                if (conteneur.dataset.signature === signature) return;
                conteneur.dataset.signature = signature;

                remplirCaseDossier("pj-recus", data.recus || [], "Aucun document reçu du citoyen.", demandeId);
                remplirCaseDossier("pj-envoyes", data.envoyes || [], "Aucun document envoyé au citoyen.", demandeId);
                remplirCaseDossier("pj-internes", data.internes || [], "Aucun document interne.", demandeId);
                filtrerDocuments();
            })
            .catch(err => {
                console.error("Erreur chargement des documents :", err);
                showError("Impossible de charger les documents du dossier.");
            });
    }

    function tailleLisible(octets) {
        return octets >= 1048576 ? (octets / 1048576).toFixed(1) + " Mo" : Math.max(1, Math.round(octets / 1024)) + " Ko";
    }

    function remplirCaseDossier(idListe, documents, texteVide, demandeId) {
        const liste = document.getElementById(idListe);
        if (!liste) return;
        liste.replaceChildren();

        if (!documents.length) {
            const li = document.createElement("li");
            li.className = "dossier-vide";
            li.textContent = texteVide;
            liste.append(li);
            return;
        }

        documents.forEach(f => {
            const li = document.createElement("li");
            li.className = "pj-item document-dossier";

            const lien = document.createElement("a");
            lien.href = f.url;
            lien.textContent = " " + f.nom;
            lien.prepend(iconeElement(iconeDocument(f.type)));
            lien.className = "document-nom";
            lien.title = f.nom; // nom complet au survol (il peut être coupé à l'écran)

            const infos = document.createElement("span");
            infos.className = "document-infos";
            infos.textContent = `${tailleLisible(f.taille)} · ${f.date}${f.auteur ? " · " + f.auteur : ""}`;

            li.append(lien, infos);

            const actions = document.createElement("div");
            actions.className = "document-actions";

            // Lien vers le message de la conversation qui accompagne le document
            if (f.message_id) {
                const voir = document.createElement("button");
                voir.type = "button";
                voir.className = "btn-voir-message";
                voir.textContent = "Voir le message";
                voir.addEventListener("click", () => ouvrirMessage(f.message_id));
                actions.append(voir);
            }
            // Document interne -> l'envoyer au citoyen par la conversation
            // (bouton présent seulement si l'agent peut écrire : agent assigné)
            if (f.supprimable && document.getElementById("fichier-dossier-id")) {
                const envoyer = document.createElement("button");
                envoyer.type = "button";
                envoyer.className = "btn-envoyer-citoyen";
                envoyer.textContent = "Envoyer au citoyen";
                envoyer.addEventListener("click", () => preparerEnvoiDocument(f));
                actions.append(envoyer);
            }
            // Suppression : documents internes uniquement
            if (f.supprimable) {
                const supprimer = document.createElement("button");
                supprimer.type = "button";
                supprimer.className = "btn-delete";
                supprimer.textContent = "Supprimer";
                supprimer.addEventListener("click", () => {
                    if (confirm(`Supprimer définitivement le document interne « ${f.nom} » ?`)) {
                        window.supprimerPJ(f.id, demandeId);
                    }
                });
                actions.append(supprimer);
            }
            if (actions.children.length) li.append(actions);
            liste.append(li);
        });
    }

    /* ------------------------------------------------------------
       Envoyer au citoyen un document interne du dossier :
       la conversation s'ouvre avec le document prêt à partir.
       L'agent écrit son message et valide : rien ne part sans lui.
    ------------------------------------------------------------ */
    const champDossierId = document.getElementById("fichier-dossier-id");
    const encartDossier = document.getElementById("document-dossier-pret");
    const champNouveauFichier = document.getElementById("fichier-pour-citoyen");

    function preparerEnvoiDocument(f) {
        if (!champDossierId || !encartDossier) return;
        champDossierId.value = f.id;
        const nomDocument = document.getElementById("document-dossier-nom");
        nomDocument.textContent = ` ${f.nom} (document du dossier)`;
        nomDocument.prepend(iconeElement("trombone"));
        encartDossier.hidden = false;
        // Un seul document par message : on retire un éventuel nouveau fichier
        if (champNouveauFichier) champNouveauFichier.value = "";
        document.getElementById("joindre-nouveau-fichier")?.setAttribute("hidden", "");

        document.getElementById("open-conversation")?.click();
        setTimeout(() => {
            const zone = document.getElementById("message-agent");
            if (zone) {
                if (!zone.value.trim()) zone.value = "Bonjour, veuillez trouver ci-joint le document concernant votre demande.";
                zone.focus();
            }
        }, 300);
    }

    function annulerEnvoiDocument() {
        if (!champDossierId || !encartDossier) return;
        champDossierId.value = "";
        encartDossier.hidden = true;
        document.getElementById("joindre-nouveau-fichier")?.removeAttribute("hidden");
    }
    window.annulerEnvoiDocument = annulerEnvoiDocument;

    document.getElementById("annuler-document-dossier")?.addEventListener("click", annulerEnvoiDocument);

    /** Ouvre la conversation et met en évidence le message lié à un document. */
    function ouvrirMessage(messageId) {
        document.getElementById("open-conversation")?.click();
        setTimeout(() => {
            const message = document.querySelector(`.message-item[data-message-id="${parseInt(messageId, 10)}"]`);
            if (!message) return;
            message.scrollIntoView({ block: "center" });
            message.classList.add("message-surligne");
            setTimeout(() => message.classList.remove("message-surligne"), 2500);
        }, 300);
    }

    /** Recherche dans les trois cases (nom, date, auteur). */
    function filtrerDocuments() {
        const terme = (document.getElementById("pj-search")?.value || "").toLowerCase();
        document.querySelectorAll(".document-dossier").forEach(item => {
            item.hidden = terme !== "" && !item.textContent.toLowerCase().includes(terme);
        });
    }

    if (formUploadPJ) {
        formUploadPJ.addEventListener("submit", function(e) {
            e.preventDefault();

            const demandeId = this.dataset.demandeId;
            const formData = new FormData(this);
            formData.append("demande_id", demandeId);

            ajaxAction(
                "../Backend/ajax/upload_pj.php",
                formData,
                (result) => {

                    chargerPiecesJointes(demandeId);
                    rafraichirHistorique();

                    this.reset();
                }
            );
        });
    }

    window.supprimerPJ = function(id, demandeId) {
        const formData = new FormData();
        formData.append("id", id);

        ajaxAction(
            "../Backend/ajax/supprimer_pj.php",
            formData,
            (data) => {
                chargerPiecesJointes(demandeId);
                rafraichirHistorique();
            }
        );
    };


/* ============================================================
   RECHERCHE GLOBALE DANS LES PIÈCES JOINTES
============================================================ */

    const pjSearchInput = document.getElementById("pj-search");

    if (pjSearchInput) {
        pjSearchInput.addEventListener("input", filtrerDocuments);
    }


/* Filtres et recherche de l'HISTORIQUE : voir historique_notes.js */


    /* ============================================================
   Envoyer la réponse agent + rafraîchir la timeline des messages
   Version Premium WhatsApp-like
============================================================ */

const formMessageAgent = document.getElementById("form-message-agent");
const timeline = document.getElementById("messages-timeline");

/* ------------------------------------------------------------
   Fonction : scroll intelligent (WhatsApp-like)
------------------------------------------------------------ */
function scrollTimelineSmart() {
    const isAtBottom =
        timeline.scrollHeight - timeline.scrollTop <= timeline.clientHeight + 50;

    if (isAtBottom) {
        timeline.scrollTop = timeline.scrollHeight;
    }
}

/* ------------------------------------------------------------
   Fonction : insérer un message dans la timeline
------------------------------------------------------------ */
function insertMessageHTML(html) {
    timeline.insertAdjacentHTML("beforeend", html);
    scrollTimelineSmart();
}

/* ------------------------------------------------------------
   Fonction : groupement par date (Aujourd’hui / Hier / etc.)
------------------------------------------------------------ */
function getHumanDateLabel(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    const d = date.toISOString().slice(0, 10);
    const t = today.toISOString().slice(0, 10);
    const y = yesterday.toISOString().slice(0, 10);

    if (d === t) return "Aujourd’hui";
    if (d === y) return "Hier";

    return date.toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long"
    });
}

/* ------------------------------------------------------------
   Fonction : ajouter un séparateur de date si nécessaire
------------------------------------------------------------ */
let lastDateGroup = null;

function ensureDateGroup(dateStr) {
    const dateKey = dateStr.slice(0, 10);

    if (lastDateGroup === dateKey) return;

    lastDateGroup = dateKey;

    const label = getHumanDateLabel(dateStr);

    timeline.insertAdjacentHTML(
        "beforeend",
        `
        <div class="messages-date-separator">
            <span>${label}</span>
        </div>
        `
    );
}

    /* ------------------------------------------------------------
    Envoi du message agent
    ------------------------------------------------------------ */
    if (formMessageAgent) {
        formMessageAgent.addEventListener("submit", function (e) {
            e.preventDefault();

            const demandeId = this.dataset.demandeId;
            const formData = new FormData(this);

            formData.append("demande_id", demandeId);

            // Support parent_id si tu ajoutes un bouton "Répondre"
            if (this.dataset.parentId) {
                formData.append("parent_id", this.dataset.parentId);
            }

            ajaxAction(
                "../Backend/ajax/Ajouter_message_agent.php",
                formData,
                (result) => {

                    // Réafficher la conversation depuis le serveur (avec le
                    // document joint éventuel), puis descendre en bas du fil
                    chargerMessages(demandeId);
                    setTimeout(() => { timeline.scrollTop = timeline.scrollHeight; }, 300);
                    chargerPiecesJointes(demandeId);

                    // Réinitialiser le formulaire
                    this.reset();
                    this.dataset.parentId = ""; // reset parent_id
                    // Le document du dossier est parti : on retire l'encart
                    if (typeof window.annulerEnvoiDocument === "function") window.annulerEnvoiDocument();

                    // La réponse est enregistrée dans l'historique par le serveur
                    rafraichirHistorique();
                }
            );
        });
    }

    filterButtons.forEach(btn => {
    btn.addEventListener("click", () => {
        filterButtons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        applyMessagesFilters(btn.dataset.filter);
    });
});

    /* ============================================================
    Recherche en temps réel dans les messages (version ptemium WhatsApp-like)
    ============================================================ */
    const searchInput = document.getElementById("messages-search-input");

    if (searchInput) {
        searchInput.addEventListener("input", () => {
            const term = searchInput.value.toLowerCase();

            document.querySelectorAll(".message-item").forEach(item => {
                const text = item.querySelector(".message-body").innerText.toLowerCase();
                item.style.display = text.includes(term) ? "" : "none";
            });
        });
    }

    document.querySelectorAll(".demande-row, .demande-card").forEach(row => {
    const demandeId = row.dataset.demandeId;
    mettreAJourBadgeNouveauxMessages(demandeId);
    });


    
    /* Badge "Nouveaux messages" : afficher/masquer au clic sur la conversation */
    const openBtn = document.getElementById("open-conversation");

    if (!openBtn) return;

    openBtn.addEventListener("click", () => {

        // Masquer les badges
        document.querySelectorAll(".badge-nouveaux").forEach(badge => {
            badge.classList.remove("show");
        });

        // Mémoriser ouverture
        localStorage.setItem("conversation_ouverte", "1");
        conversationDejaOuverte = true;

        //  Marquer les messages comme lus côté backend
        const formData = new FormData();
        formData.append("demande_id", openBtn.dataset.demandeId);

        fetch("../Backend/ajax/Marquer_messages_lus.php", {
            method: "POST",
            headers: enTetesSecurite(),
            body: formData
        });
    });
});

    


