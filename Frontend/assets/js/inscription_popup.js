document.addEventListener("DOMContentLoaded", function() {
    const okBtn = document.getElementById("popupOk");
    const popup = document.getElementById("popupSuccess");

    if (okBtn && popup) {
        okBtn.addEventListener("click", function() {
            popup.style.display = "none";
            window.location.href = "portail_agent.php";
        });
    }
});
