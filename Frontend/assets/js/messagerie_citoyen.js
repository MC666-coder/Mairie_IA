/* =========================================================
   Conversation mairie -> citoyen (page details_demande.php)
   ---------------------------------------------------------
   - Affiche ce que l'agent a écrit et les documents qu'il a envoyés
     (attestation...), à télécharger.
   - Le citoyen RÉPOND seulement après un message de l'agent : document
     et/ou court message (Backend/citoyen/messagerie.php).
   - Se met à jour toutes les 15 s tant que l'onglet est visible, et
     marque comme lus les messages de la mairie affichés.
   Tout texte est inséré avec textContent : aucun HTML n'est interprété.
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {

    const section = document.getElementById("messagerie-citoyen");
    if (!section) return;

    const demandeId = section.dataset.demandeId;
    const fil = document.getElementById("fil-messages");
    const form = document.getElementById("form-reponse-citoyen");
    const indisponible = document.getElementById("reponse-indisponible");
    const zoneTexte = document.getElementById("texte-message");
    const champFichier = document.getElementById("fichier-citoyen");
    const aideFichier = document.getElementById("aide-fichier");
    const boutonEnvoyer = document.getElementById("envoyer-message");
    const compteurCaracteres = document.getElementById("compteur-caracteres");
    const statut = document.getElementById("messagerie-statut");
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content ?? "";

    const URL = "../Backend/citoyen/messagerie.php";
    const INTERVALLE_MS = 15000;
    let signature = "";
    let minuterie = null;

    /* ---------- Outils ---------- */

    function element(tag, classe, texte) {
        const el = document.createElement(tag);
        if (classe) el.className = classe;
        if (texte !== undefined) el.textContent = texte;
        return el;
    }

    function lienDocument(doc) {
        const a = element("a", "bulle-document", " " + doc.nom);
        a.prepend(iconeElement(iconeDocument(doc.type)));
        a.href = doc.url;
        return a;
    }

    function tailleLisible(octets) {
        return octets >= 1048576 ? (octets / 1048576).toFixed(1) + " Mo" : Math.max(1, Math.round(octets / 1024)) + " Ko";
    }

    function afficherStatut(texte, type = "info") {
        statut.textContent = texte;
        statut.dataset.type = type;
    }

    /* ---------- Affichage ---------- */

    function afficherMessages(messages) {
        const enBas = fil.scrollHeight - fil.scrollTop - fil.clientHeight < 60;
        fil.replaceChildren();

        if (!messages.length) {
            fil.append(element("p", "fil-vide", "Aucun message pour le moment."));
            return;
        }

        let jourCourant = null;
        messages.forEach(m => {
            if (m.jour !== jourCourant) {
                jourCourant = m.jour;
                fil.append(element("div", "fil-jour", m.jour_libelle));
            }
            const bulle = element("div", `bulle bulle-${m.auteur}`);
            bulle.dir = "auto";
            bulle.append(element("span", "bulle-auteur", m.nom));
            bulle.append(element("p", "bulle-texte", m.message));
            if (m.document) bulle.append(lienDocument(m.document));
            bulle.append(element("span", "bulle-heure", m.heure));
            fil.append(bulle);
        });

        if (enBas || !fil.dataset.charge) fil.scrollTop = fil.scrollHeight;
        fil.dataset.charge = "1";
    }

    function afficherListe(idListe, idCompteur, documents, texteVide) {
        const liste = document.getElementById(idListe);
        document.getElementById(idCompteur).textContent = documents.length;
        liste.replaceChildren();
        if (!documents.length) {
            liste.append(element("li", "documents-vide", texteVide));
            return;
        }
        documents.forEach(d => {
            const li = element("li");
            const a = element("a", "", " " + d.nom);
            a.prepend(iconeElement(iconeDocument(d.type)));
            a.href = d.url;
            li.append(a, element("span", "document-infos", ` · ${tailleLisible(d.taille)} · ${d.date}`));
            liste.append(li);
        });
    }

    function afficherDroit(droit) {
        form.hidden = !droit.peut;
        indisponible.hidden = droit.peut;
        indisponible.textContent = droit.raison || "";
    }

    /* ---------- Chargement ---------- */

    async function charger() {
        try {
            const res = await fetch(`${URL}?demande_id=${encodeURIComponent(demandeId)}`, {
                headers: { "Accept": "application/json" },
                credentials: "same-origin",
                cache: "no-store"
            });
            if (res.status === 401) {
                arreter();
                // Session expirée : connexion, puis retour sur cette demande
                const retour = "details_demande.php" + window.location.search;
                window.location.href = "connexion_citoyen.php?session=expiree&retour=" + encodeURIComponent(retour);
                indisponible.hidden = false;
                indisponible.textContent = "Votre session a expiré. Rechargez la page pour vous reconnecter.";
                form.hidden = true;
                return;
            }
            const data = await res.json();
            if (!data.success) return;

            aideFichier.textContent = `PDF, JPG, PNG, DOCX ou XLSX, ${data.taille_max_mo} Mo maximum.`;
            if (data.signature !== signature) {
                signature = data.signature;
                afficherMessages(data.messages);
                afficherListe("liste-docs-mairie", "compteur-docs-mairie", data.documents.mairie,
                              "Aucun document envoyé par la mairie pour le moment.");
                afficherListe("liste-docs-citoyen", "compteur-docs-citoyen", data.documents.citoyen,
                              "Vous n’avez envoyé aucun document.");
                afficherDroit(data.droit);
            }
            // Messages de la mairie affichés à l'écran : on les marque comme lus
            if (data.non_lus > 0 && !document.hidden) marquerLus();
        } catch (e) {
            // réseau indisponible : nouvel essai au prochain cycle
        }
    }

    function marquerLus() {
        const corps = new FormData();
        corps.append("action", "lu");
        corps.append("demande_id", demandeId);
        fetch(URL, { method: "POST", credentials: "same-origin",
                     headers: { "Accept": "application/json", "X-CSRF-Token": jeton }, body: corps })
            .catch(() => {});
    }

    function demarrer() {
        if (!minuterie) minuterie = setInterval(charger, INTERVALLE_MS);
    }
    function arreter() {
        clearInterval(minuterie);
        minuterie = null;
    }

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            arreter();
        } else {
            charger();
            demarrer();
        }
    });

    /* ---------- Réponse du citoyen ---------- */

    zoneTexte.addEventListener("input", () => {
        compteurCaracteres.textContent = `${zoneTexte.value.length} / ${zoneTexte.maxLength}`;
    });

    form.addEventListener("submit", async e => {
        e.preventDefault();
        const fichier = champFichier.files[0];
        const texte = zoneTexte.value.trim();
        if (!fichier && !texte) {
            afficherStatut("Ajoutez un document ou écrivez un message.", "erreur");
            return;
        }

        const corps = new FormData();
        corps.append("action", "reponse");
        corps.append("demande_id", demandeId);
        corps.append("message", texte);
        if (fichier) corps.append("fichier", fichier);

        boutonEnvoyer.disabled = true;
        afficherStatut(fichier ? `Envoi de « ${fichier.name} »…` : "Envoi…");

        let data;
        try {
            const res = await fetch(URL, {
                method: "POST",
                credentials: "same-origin",
                headers: { "Accept": "application/json", "X-CSRF-Token": jeton },
                body: corps
            });
            try {
                data = await res.json();
            } catch {
                data = { success: false, message: `Erreur technique (HTTP ${res.status}).` };
            }
        } catch {
            data = { success: false, message: "Connexion impossible. Vérifiez votre connexion internet." };
        }
        boutonEnvoyer.disabled = false;

        if (data.success) {
            form.reset();
            compteurCaracteres.textContent = `0 / ${zoneTexte.maxLength}`;
            afficherStatut(data.message || "Envoyé à la mairie.", "succes");
            await charger();
            fil.scrollTop = fil.scrollHeight;
        } else {
            afficherStatut(data.message || "L’envoi n’a pas abouti.", "erreur");
        }
    });

    charger();
    demarrer();
});
