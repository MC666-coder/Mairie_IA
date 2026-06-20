/* ============================================================
   ALERTES PREMIUM
============================================================ */

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


/* ============================================================
   MISE À JOUR DU STATUT
============================================================ */

document.addEventListener("DOMContentLoaded", () => {

    /* ============================================================
       AJOUT ICI : récupérer l’ID de la demande au chargement
    ============================================================ */
    const demandeIdPage = document.getElementById("liste-pj")?.dataset.demandeId;

    /* ============================================================
       AJOUT ICI : charger automatiquement les pièces jointes
    ============================================================ */
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

                    document.getElementById("note").value = "";

                    ajouterHistorique(
                        demandeId,
                        "Ajout d'une note interne",
                        formData.get("note")
                    );
                }
            );
        });
    }

    
    


/* ============================================================
   MODULE PIÈCES JOINTES (UPLOAD + LISTE + SUPPRESSION + GROUPES + MINIATURES + RECHERCHE)
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
            () => {
                chargerPiecesJointes(demandeId);

                ajouterHistorique(
                    demandeId,
                    "Suppression d'une pièce jointe",
                    "ID fichier : " + id
                );
            }
        );
    };


/* ============================================================
   RECHERCHE GLOBALE DANS LES PIÈCES JOINTES (OPTION 1)
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
   FILTRES & RECHERCHE — HISTORIQUE (VERSION DYNAMIQUE)
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

});
