document.addEventListener("DOMContentLoaded", function () {
    const okBtn = document.getElementById("popup-ok");

    if (okBtn) {
        okBtn.addEventListener("click", function () {
            window.location.href = "index.php";
        });
    }
});
