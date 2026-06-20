document.addEventListener("DOMContentLoaded", () => {

    document.getElementById("send").addEventListener("click", sendMessage);

    function sendMessage() {
        const input = document.getElementById("message");
        const text = input.value.trim();

        if (text.length === 0) {
            return;
        }

        addMessage(text, "user");
        input.value = "";

        fetch("../Backend/chatbot/send_message.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            addMessage(data.response ?? "❌ Réponse invalide du serveur.", "bot");
        })
        .catch(() => {
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
