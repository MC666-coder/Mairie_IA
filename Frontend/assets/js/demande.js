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
        <div class="alert ${type}">
            <i class="fa-solid ${type === "success" ? "fa-circle-check" : "fa-circle-exclamation"}"></i>
            ${escapeHTML(message)}
        </div>
    `;

    setTimeout(() => container.innerHTML = "", 4000);
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
                        badge.textContent = newStatut;
                        badge.className = "statut statut-" + newStatut;
                    }

                    if (result.date_mise_a_jour) {
                        const dateSpan = document.getElementById("date-mise-a-jour");
                        if (dateSpan) dateSpan.textContent = result.date_mise_a_jour;
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

                    if (result.date_mise_a_jour) {
                        const dateSpan = document.getElementById("date-mise-a-jour");
                        if (dateSpan) dateSpan.textContent = result.date_mise_a_jour;
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

    function chargerPiecesJointes(demandeId) {
        fetch(`../Backend/ajax/get_pj.php?demande_id=${encodeURIComponent(demandeId)}`, { headers: enTetesSecurite() })
            .then(res => res.json())
            .then(data => {

                const liste = document.getElementById("liste-pj");
                const badge = document.getElementById("pj-count");

                if (badge) badge.textContent = data.count ?? 0;

                if (!data.count || data.count === 0) {
                    liste.innerHTML = `<p><em>Aucune pièce jointe.</em></p>`;
                    return;
                }

                let html = "";

                Object.keys(data.grouped).forEach(date => {
                    const dateFr = new Date(date).toLocaleDateString("fr-FR", {
                        weekday: "long",
                        year: "numeric",
                        month: "long",
                        day: "numeric"
                    });

                    html += `
                        <div class="pj-date-group">
                            <h4 class="pj-date-title">📅 ${dateFr}</h4>
                    `;

                    data.grouped[date].forEach(f => {
                        const isImage = f.type && f.type.startsWith("image/");
                        const isPDF = f.type === "application/pdf";

                        // Le nom du fichier est choisi par celui qui l'envoie : on l'échappe (anti-XSS)
                        const url = escapeHTML(f.url);
                        const nom = escapeHTML(f.nom);
                        const idPJ = parseInt(f.id, 10);
                        const idDemande = parseInt(demandeId, 10);

                        html += `
                            <div class="pj-item">
                                <div class="pj-left">
                                    ${isImage ? `<img src="${url}" class="pj-thumb" alt="">` : ""}
                                    ${isPDF ? `<i class="fa-solid fa-file-pdf pj-pdf"></i>` : ""}
                                    <a href="${url}" target="_blank" rel="noopener" download="${nom}">
                                        📎 ${nom}
                                    </a>
                                </div>
                                <button class="btn-delete" onclick="supprimerPJ(${idPJ}, ${idDemande})">
                                    Supprimer
                                </button>
                            </div>
                        `;
                    });

                    html += `</div>`;
                });

                liste.innerHTML = html;
            })
            .catch(err => {
                console.error("Erreur chargement PJ:", err);
                showError("Impossible de charger les pièces jointes.");
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
        pjSearchInput.addEventListener("input", function() {
            const term = this.value.toLowerCase();

            document.querySelectorAll(".pj-item").forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(term) ? "" : "none";
            });

            document.querySelectorAll(".pj-date-group").forEach(group => {
                const hasVisible = Array.from(group.querySelectorAll(".pj-item"))
                    .some(i => i.style.display !== "none");
                group.style.display = hasVisible ? "" : "none";
            });
        });
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

                    // Ajouter séparateur de date si nécessaire
                    ensureDateGroup(result.created_at);

                    // Insérer le message agent dans la timeline
                    insertMessageHTML(result.html);

                    // Réinitialiser le formulaire
                    this.reset();
                    this.dataset.parentId = ""; // reset parent_id

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

    


