document.addEventListener("DOMContentLoaded", () => {

    const loader = document.getElementById("ia-loading");
    const sendBtn = document.getElementById("send");
    const input = document.getElementById("message");

    sendBtn.addEventListener("click", sendMessage);
    input.addEventListener("keypress", e => {
        if (e.key === "Enter") sendMessage();
    });

    function sendMessage() {
        const text = input.value.trim();

        if (text.length === 0) {
            return; // aucune bulle affichée
        }

        addMessage(text, "user");
        input.value = "";

        // 🔥 AFFICHER LE LOADER
        loader.style.display = "flex";

        fetch("../Backend/chatbot/send_message.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {

            // ❌ CACHER LE LOADER
            loader.style.display = "none";

            addMessage(data.response ?? "❌ Réponse invalide du serveur.", "bot");
        })
        .catch(() => {

            // ❌ CACHER LE LOADER
            loader.style.display = "none";

            addMessage("❌ Le service est momentanément indisponible.", "bot");
        });
    }

});


function addMessage(text, sender) {
    const chatWindow = document.getElementById("chat-window");

    const msg = document.createElement("div");
    msg.classList.add("message", sender === "user" ? "user-message" : "bot-message");
    msg.innerHTML = text;

    chatWindow.appendChild(msg);
    chatWindow.scrollTop = chatWindow.scrollHeight;
}
