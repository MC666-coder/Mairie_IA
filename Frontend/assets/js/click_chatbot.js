document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById("ia-toggle-btn");
    const bot = document.getElementById("chatbot");

    if (toggleBtn && bot) {
        toggleBtn.addEventListener("click", function() {
            bot.classList.toggle("chatbot-visible");
            bot.classList.toggle("chatbot-hidden");
        });
    }
});
