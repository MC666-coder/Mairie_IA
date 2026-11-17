/* =========================================================
   Chatbot citoyen : l'accueil IA de la mairie
   ---------------------------------------------------------
   - Tous les textes sont insérés avec textContent (jamais innerHTML) :
     ni le citoyen ni l'IA ne peuvent injecter de code dans la page.
   - Quand l'IA prépare une demande, une carte s'affiche : le citoyen
     relit, modifie si besoin, puis confirme ou annule.
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {

    const loader = document.getElementById("ia-loading");
    const sendBtn = document.getElementById("send");
    const input = document.getElementById("message");
    const chatWindow = document.getElementById("chat-window");
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content ?? "";

    if (!loader || !sendBtn || !input || !chatWindow) {
        return;
    }

    const entetes = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-Token": jeton
    };

    sendBtn.addEventListener("click", sendMessage);
    input.addEventListener("keydown", e => {
        if (e.key === "Enter") {
            e.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        const text = input.value.trim();
        if (text.length === 0 || sendBtn.disabled) {
            return;
        }

        addMessage(text, "user");
        input.value = "";
        attente(true);

        fetch("../Backend/chatbot/send_message.php", {
            method: "POST",
            headers: entetes,
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            attente(false);
            addMessage(data.response ?? data.message ?? "Réponse invalide du serveur.", "bot", data.langue);
            if (data.brouillon) {
                afficherBrouillon(data.brouillon);
            }
        })
        .catch(() => {
            attente(false);
            addMessage("Le service est momentanément indisponible.", "bot");
        });
    }

    function attente(active) {
        loader.style.display = active ? "flex" : "none";
        sendBtn.disabled = active;
    }

    function addMessage(text, sender, langue) {
        const msg = document.createElement("div");
        msg.classList.add("message", sender === "user" ? "user-message" : "bot-message");
        msg.textContent = text;               // texte brut, retours à la ligne gérés en CSS
        msg.style.whiteSpace = "pre-line";
        msg.dir = "auto";                     // arabe, hébreu, persan... de droite à gauche
        if (langue) {
            msg.lang = langue;                // bonne prononciation par les lecteurs d'écran
        }
        chatWindow.appendChild(msg);
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    /* ---------------------------------------------------------
       Carte de confirmation d'une demande préparée par l'IA
    --------------------------------------------------------- */
    function afficherBrouillon(b) {
        // Une seule carte active à la fois
        chatWindow.querySelectorAll(".brouillon-demande").forEach(c => c.remove());

        // Libellés traduits par l'IA dans la langue du citoyen (français par défaut)
        const t = Object.assign({
            entete: "Votre demande (à vérifier avant l'envoi)",
            service: "Service",
            priorite: "Priorité : " + b.priorite,
            aide: "Vous pouvez corriger ce texte. Rien n'est envoyé tant que vous n'avez pas confirmé.",
            confirmer: "Confirmer l'envoi",
            annuler: "Annuler"
        }, b.interface || {});

        const carte = document.createElement("div");
        carte.className = "brouillon-demande";
        carte.dir = "auto";
        if (b.langue) {
            carte.lang = b.langue;
        }

        const titre = document.createElement("strong");
        titre.textContent = t.entete;
        carte.appendChild(titre);

        const infos = document.createElement("p");
        infos.className = "brouillon-infos";
        infos.textContent = `${t.service} : ${b.service} · ${t.priorite}`;
        carte.appendChild(infos);

        const zone = document.createElement("textarea");
        zone.value = b.description;
        zone.rows = 5;
        zone.maxLength = 2000;
        zone.dir = "auto";
        zone.setAttribute("aria-label", t.entete);
        carte.appendChild(zone);

        const aide = document.createElement("p");
        aide.className = "brouillon-aide";
        aide.textContent = t.aide;
        carte.appendChild(aide);

        const boutons = document.createElement("div");
        boutons.className = "brouillon-boutons";
        const confirmer = document.createElement("button");
        confirmer.type = "button";
        confirmer.textContent = t.confirmer;
        const annuler = document.createElement("button");
        annuler.type = "button";
        annuler.className = "secondaire";
        annuler.textContent = t.annuler;
        boutons.append(confirmer, annuler);
        carte.appendChild(boutons);

        confirmer.addEventListener("click", () => envoyerConfirmation(carte, { description: zone.value }, b.langue));
        annuler.addEventListener("click", () => envoyerConfirmation(carte, { annuler: true }, b.langue));

        chatWindow.appendChild(carte);
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    function envoyerConfirmation(carte, corps, langue) {
        carte.querySelectorAll("button, textarea").forEach(el => el.disabled = true);

        fetch("../Backend/chatbot/confirmer_demande.php", {
            method: "POST",
            headers: entetes,
            body: JSON.stringify(corps)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                carte.remove();
            } else {
                carte.querySelectorAll("button, textarea").forEach(el => el.disabled = false);
            }
            addMessage(data.message ?? "Réponse invalide du serveur.", "bot", langue);
        })
        .catch(() => {
            carte.querySelectorAll("button, textarea").forEach(el => el.disabled = false);
            addMessage("La demande n'a pas pu être envoyée. Merci de réessayer.", "bot");
        });
    }
});
