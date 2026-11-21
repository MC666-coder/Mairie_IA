document.addEventListener("DOMContentLoaded", () => {

    // Débogage côté navigateur uniquement : les fichiers .log du serveur ne sont pas accessibles par URL
    function jsLog(message) {
        console.log("[JS LOG] " + message);
    }

    jsLog("assistant.js chargé");

    const btn = document.getElementById("ia-agent-button");
    const win = document.getElementById("ia-agent-window");
    const closeBtn = document.getElementById("ia-close-btn");
    const sendBtn = document.getElementById("ia-send-btn");
    const input = document.getElementById("ia-input");
    const messages = document.getElementById("ia-messages");
    const loader = document.getElementById("ia-loader");

    if (!btn || !win || !closeBtn || !sendBtn || !input || !messages || !loader) {
        jsLog("Erreur: un élément HTML est introuvable");
        return;
    }

    btn.onclick = () => win.classList.remove("hidden");
    closeBtn.onclick = () => win.classList.add("hidden");

    /* ---------------------------------------------------------
       AUTO‑RESIZE DU CHAMP
    --------------------------------------------------------- */
    input.addEventListener("input", () => {
        input.style.height = "auto";
        input.style.height = input.scrollHeight + "px";
    });

    /* ---------------------------------------------------------
       FORMATAGE MARKDOWN → HTML
    --------------------------------------------------------- */
    function formatAIText(text) {
        let html = text;

        // **gras**
        html = html.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");

        // - listes
        html = html.replace(/^- (.*)$/gm, "<li>$1</li>");

        if (html.includes("<li>")) {
            html = "<ul>" + html + "</ul>";
        }

        // sauts de ligne
        html = html.replace(/\n/g, "<br>");

        return html;
    }

    /* ---------------------------------------------------------
       AJOUT D’UN MESSAGE
    --------------------------------------------------------- */
    function addMessage(text, type) {
        const div = document.createElement("div");
        div.className = type === "agent" ? "msg-agent" : "msg-ia";

        if (type === "ia") {
            div.innerHTML = formatAIText(text);
        } else {
            div.textContent = text;
        }

        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return div;
    }

    /* ---------------------------------------------------------
       LOCK / UNLOCK
    --------------------------------------------------------- */
    function lockInput() {
        sendBtn.disabled = true;
        input.disabled = true;
        input.classList.add("disabled");
    }

    function unlockInput() {
        sendBtn.disabled = false;
        input.disabled = false;
        input.classList.remove("disabled");
        input.focus();
    }

    /* ---------------------------------------------------------
       EFFET DE FRAPPE IA (Markdown restauré)
    --------------------------------------------------------- */
    async function typeWriterEffect(text) {
        return new Promise(resolve => {
            const div = document.createElement("div");
            div.className = "msg-ia";
            messages.appendChild(div);

            let index = 0;
            let buffer = ""; // texte brut accumulé

            function type() {
                if (index < text.length) {
                    buffer += text[index];
                    div.textContent = buffer; // affichage brut pendant la frappe
                    index++;
                    messages.scrollTop = messages.scrollHeight;
                    setTimeout(type, 12);
                } else {
                    // une fois terminé → appliquer Markdown
                    div.innerHTML = formatAIText(buffer);
                    resolve();
                }
            }

            type();
        });
    }

    /* ---------------------------------------------------------
       ENVOI DU MESSAGE
    --------------------------------------------------------- */
    async function sendMessage() {
        const msg = input.value.trim();
        if (!msg || msg.length < 2) {
            jsLog("Message vide ou trop court");
            return;
        }

        jsLog("Message envoyé (" + msg.length + " caractères)");
        addMessage(msg, "agent");
        input.value = "";
        input.style.height = "40px";

        lockInput();
        loader.classList.remove("hidden");

        // Le serveur détermine lui-même l'agent et son service (session) :
        // on n'envoie que le message et le mode.
        const payload = {
            message: msg,
            action: "free_prompt"
        };

        let res;
        try {
            res = await fetch("../Backend/Agent_API/chat_agent.php", {
                method: "POST",
                credentials: "include",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
        } catch (e) {
            jsLog("Erreur fetch: " + e.message);
            loader.classList.add("hidden");
            addMessage("Erreur IA (fetch)", "ia");
            unlockInput();
            return;
        }

        let data;
        try {
            data = await res.json();
        } catch (e) {
            jsLog("Erreur JSON: " + e.message);
            const text = await res.text();
            jsLog("Réponse brute: " + text);
            loader.classList.add("hidden");
            addMessage("Erreur IA (JSON)", "ia");
            unlockInput();
            return;
        }

        loader.classList.add("hidden");

        if (data.error) {
            const erreur = addMessage(" " + data.error, "ia");
            if (window.iconeElement) erreur.prepend(iconeElement("alerte"));
            unlockInput();
            return;
        }

        await typeWriterEffect(data.response);

        unlockInput();
    }

    sendBtn.onclick = sendMessage;

    /* ---------------------------------------------------------
       ENTRÉE = envoyer / SHIFT+ENTRÉE = nouvelle ligne
    --------------------------------------------------------- */
    input.addEventListener("keydown", function(e) {

        if (input.disabled) return;

        if (e.key === "Enter" && e.shiftKey) {
            return;
        }

        if (e.key === "Enter") {
            e.preventDefault();
            sendMessage();
        }
    });

});