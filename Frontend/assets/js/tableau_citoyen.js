/* =========================================================
   Tableau de bord citoyen : mise à jour automatique (AJAX)
   ---------------------------------------------------------
   Rafraîchit les statistiques et la liste des demandes sans recharger
   la page :
   - immédiatement quand le chatbot confirme une demande
     (événement « demande:creee ») ;
   - toutes les 30 secondes tant que l'onglet est visible, pour voir les
     changements faits par les agents (statut, service...) ;
   - au retour sur l'onglet.
   Le serveur ne renvoie le HTML (déjà échappé) que si les données ont
   changé : rien n'est redessiné inutilement.
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {

    const stats = document.getElementById("stats-citoyen");
    const liste = document.getElementById("liste-demandes");
    const annonce = document.getElementById("annonce-tableau");
    if (!stats || !liste) {
        return;
    }

    const URL_TABLEAU = "../Backend/citoyen/tableau_de_bord.php";
    const INTERVALLE_MS = 30000;
    let enCours = false;
    let minuterie = null;

    async function rafraichir() {
        if (enCours) {
            return;                       // pas deux requêtes en même temps
        }
        enCours = true;
        try {
            const signature = liste.dataset.signature || "";
            const res = await fetch(`${URL_TABLEAU}?signature=${encodeURIComponent(signature)}`, {
                headers: { "Accept": "application/json" },
                credentials: "same-origin",
                cache: "no-store"
            });

            if (res.status === 401) {     // session expirée : on arrête
                arreter();
                annoncer("Votre session a expiré. Rechargez la page pour vous reconnecter.");
                return;
            }
            if (!res.ok) {
                return;                   // erreur passagère : prochain essai plus tard
            }

            const data = await res.json();
            if (!data.modifie) {
                return;
            }

            const anciensIds = idsAffiches();
            stats.innerHTML = data.stats;         // HTML échappé côté serveur
            liste.innerHTML = data.demandes;
            liste.dataset.signature = data.signature;

            // Mise en évidence des demandes apparues depuis le dernier affichage
            const nouvelles = (data.ids || []).filter(id => !anciensIds.has(id));
            nouvelles.forEach(id => {
                liste.querySelector(`.request-card[data-id="${id}"]`)?.classList.add("carte-nouvelle");
            });
            annoncer(nouvelles.length
                ? `${nouvelles.length} nouvelle(s) demande(s) dans votre tableau de bord.`
                : "Votre tableau de bord a été mis à jour.");
        } catch (e) {
            // réseau indisponible : on réessaiera au prochain cycle
        } finally {
            enCours = false;
        }
    }

    function idsAffiches() {
        return new Set([...liste.querySelectorAll(".request-card[data-id]")].map(c => Number(c.dataset.id)));
    }

    function annoncer(texte) {
        if (annonce) {
            annonce.textContent = texte;
        }
    }

    function demarrer() {
        if (!minuterie) {
            minuterie = setInterval(rafraichir, INTERVALLE_MS);
        }
    }

    function arreter() {
        clearInterval(minuterie);
        minuterie = null;
    }

    // Une demande vient d'être confirmée dans le chatbot
    document.addEventListener("demande:creee", rafraichir);

    // Pas de requêtes quand l'onglet est caché ; mise à jour au retour
    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            arreter();
        } else {
            rafraichir();
            demarrer();
        }
    });

    demarrer();
});
