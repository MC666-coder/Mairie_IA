/* ============================================================
   ALERTES PREMIUM
============================================================ */
/* pour afficher le badge "Nouveaux messages" sur la liste des demandes */
let conversationDejaOuverte = false;
/* Fin de la gestion du badge "Nouveaux messages" */

function showAlert(type, message) {
    const container = document.getElementById("alert-container");

    container.innerHTML = `
        <div class="alert ${type}">
            <i class="fa-solid ${type === "success" ? "fa-circle-check" : "fa-circle-exclamation"}"></i>
            ${message}
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
    fetch(`../Backend/ajax/get_messages.php?demande_id=${demandeId}`)
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
                    ? (m.prenom[0] + m.nom[0]).toUpperCase()
                    : "C";

                const author = isAgent
                    ? `${m.prenom} ${m.nom}`
                    : "Citoyen";

                // Ajout du séparateur de date
                ensureDateGroup(m.created_at);

                // Construction du message
                const html = `
                    <div class="message-item ${isAgent ? "agent" : "citoyen"}"
                         data-message-id="${m.id}"
                         data-created-at="${m.created_at}"
                         data-parent-id="${m.parent_id ?? ""}">

                        <div class="message-avatar">${initials}</div>

                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-author">${author}</span>
                                <span class="message-date">${m.created_at}</span>
                            </div>

                            <div class="message-body">
                                ${m.message}
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
   AJOUT AUTOMATIQUE DANS L’HISTORIQUE (SILENCIEUX)
============================================================ */

function ajouterHistorique(demandeId, action, details = "") {
    const formData = new FormData();
    formData.append("demande_id", demandeId);
    formData.append("action", action);
    formData.append("details", details);

    fetch("../Backend/ajax/Ajouter_historique.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (!result.success) return;

        const timeline = document.getElementById("history-timeline");

        if (timeline.querySelector("em")) timeline.innerHTML = "";

        timeline.insertAdjacentHTML("afterbegin", result.html);

        if (typeof window.applyHistoryFilters === "function") {
            window.applyHistoryFilters();
        }
    })
    .catch(err => console.error("Erreur historique:", err));
}

/* Mettre à jour le badge de nouveaux messages sur la liste des demandes */
function mettreAJourBadgeNouveauxMessages(demandeId) {
    fetch(`../Backend/ajax/get_messages.php?demande_id=${demandeId}`)
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

                    ajouterHistorique(
                        demandeId,
                        "Changement de statut",
                        "Nouveau statut : " + newStatut
                    );
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

                    ajouterHistorique(
                        demandeId,
                        "Assignation d'un agent",
                        "Assigné à : " + result.agent_nom
                    );
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
                (result) => {

                    const liste = document.getElementById("liste-notes");

                    if (liste.querySelector("em")) liste.innerHTML = "";

                    liste.insertAdjacentHTML("afterbegin", result.note_html);

                    const lastNote = liste.querySelector(".note-item");
                    if (lastNote && !lastNote.dataset.text) {
                        lastNote.dataset.text = formData.get("note");
                    }

                    document.getElementById("note").value = "";

                    ajouterHistorique(
                        demandeId,
                        "Ajout d'une note interne",
                        formData.get("note")
                    );

                    if (typeof window.applyNotesFilters === "function") {
                        window.applyNotesFilters();
                    }
                }
            );
        });
    }


/* ============================================================
   FILTRES & RECHERCHE — NOTES (VERSION DYNAMIQUE)
============================================================ */

const notesFilterBtns = document.querySelectorAll(".notes-filter-btn");
const notesSearchInput = document.getElementById("notes-search-input");

let notesFilter = "all";

function getNoteItems() {
    return document.querySelectorAll(".note-item");
}

function applyNotesFilters() {
    const search = (notesSearchInput?.value || "").toLowerCase();
    const now = new Date();

    getNoteItems().forEach(note => {
        const text = (note.dataset.text || "").toLowerCase();
        const createdAt = new Date(note.dataset.createdAt);
        const agentId = note.dataset.agentId;

        let visible = true;

        if (notesFilter === "me") {
            if (typeof CURRENT_AGENT_ID !== "undefined") {
                visible = visible && agentId === CURRENT_AGENT_ID;
            }
        }
        if (notesFilter === "month"){
            visible = visible && createdAt.getMonth() === now.getMonth() && 
            createdAt.getFullYear() === now.getFullYear();
        }

        if (notesFilter === "24h") {
            const diff = now - createdAt;
            visible = visible && diff <= 24 * 60 * 60 * 1000;
        }

        if (notesFilter === "week") {
            const diff = now - createdAt;
            visible = visible && diff <= 7 * 24 * 60 * 60 * 1000;
        }

        if (search) {
            visible = visible && text.includes(search);
        }

        note.style.display = visible ? "" : "none";
    });

    document.querySelectorAll(".notes-date-group").forEach(group => {
        const hasVisible = Array.from(group.querySelectorAll(".note-item"))
            .some(i => i.style.display !== "none");

        group.style.display = hasVisible ? "" : "none";
    });
}

window.applyNotesFilters = applyNotesFilters;

notesFilterBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        notesFilterBtns.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        notesFilter = btn.dataset.filter;
        applyNotesFilters();
    });
});

if (notesSearchInput) {
    notesSearchInput.addEventListener("input", applyNotesFilters);
}


/* ============================================================
   MODULE PIÈCES JOINTES
============================================================ */

    const formUploadPJ = document.querySelector(".pieces-jointes .form-upload");

    function chargerPiecesJointes(demandeId) {
        fetch(`../Backend/ajax/get_pj.php?demande_id=${demandeId}`)
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

                        html += `
                            <div class="pj-item">
                                <div class="pj-left">
                                    ${isImage ? `<img src="${f.url}" class="pj-thumb">` : ""}
                                    ${isPDF ? `<i class="fa-solid fa-file-pdf pj-pdf"></i>` : ""}
                                    <a href="${f.url}" target="_blank" download="${f.nom}">
                                        📎 ${f.nom}
                                    </a>
                                </div>
                                <button class="btn-delete" onclick="supprimerPJ(${f.id}, ${demandeId})">
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

                    ajouterHistorique(
                        demandeId,
                        "Ajout d'une pièce jointe",
                        result.nom_fichier
                    );

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

                ajouterHistorique(
                    demandeId,
                    "Suppression d'une pièce jointe",
                    "Nom du fichier : " + data.nom_fichier
                );
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


/* ============================================================
   FILTRES & RECHERCHE — HISTORIQUE
============================================================ */

    const historyFilterBtns = document.querySelectorAll(".history-filter-btn");
    const historySearchInput = document.getElementById("history-search-input");

    let historyFilter = "all";

    function getHistoryItems() {
        return document.querySelectorAll(".history-item");
    }

    function applyHistoryFilters() {
        const search = (historySearchInput?.value || "").toLowerCase();

        getHistoryItems().forEach(item => {
            const type = item.dataset.type;
            const text = item.dataset.text.toLowerCase();

            let visible = true;

            if (historyFilter !== "all") {
                visible = visible && type === historyFilter;
            }

            if (search) {
                visible = visible && text.includes(search);
            }

            item.style.display = visible ? "" : "none";
        });

        document.querySelectorAll(".history-date-group").forEach(group => {
            const hasVisible = Array.from(group.querySelectorAll(".history-item"))
                .some(i => i.style.display !== "none");
            group.style.display = hasVisible ? "" : "none";
        });
    }

    window.applyHistoryFilters = applyHistoryFilters;

    historyFilterBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            historyFilterBtns.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            historyFilter = btn.dataset.filter;
            applyHistoryFilters();
        });
    });

    if (historySearchInput) {
        historySearchInput.addEventListener("input", applyHistoryFilters);
    }


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

                    // Ajouter dans l’historique
                    ajouterHistorique(
                        demandeId,
                        "Réponse agent",
                        formData.get("message")
                    );
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
            body: formData
        });
    });
});

    


