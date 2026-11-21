/* =========================================================
   Règles du mot de passe cochées en direct (formulaire d'inscription citoyen)
   Mêmes règles que Backend/Fonctions/regles_mot_de_passe.php (le serveur revérifie).
   + message si les deux mots de passe ne correspondent pas.
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const TESTS = {
        longueur: (v) => [...v].length >= 10,
        majuscule: (v) => /\p{Lu}/u.test(v),
        minuscule: (v) => /\p{Ll}/u.test(v),
        chiffre: (v) => /[0-9]/.test(v),
        special: (v) => /[^\p{L}0-9]/u.test(v)
    };

    document.querySelectorAll(".regles-mot-de-passe").forEach((liste) => {
        const champ = document.getElementById(liste.dataset.champ);
        if (!champ) return;

        const majRegles = () => {
            liste.querySelectorAll("li[data-regle]").forEach((li) => {
                const test = TESTS[li.dataset.regle];
                const ok = test ? test(champ.value) : false;
                li.classList.toggle("regle-respectee", ok);
                const etat = li.querySelector(".regle-etat");
                if (etat) etat.textContent = ok ? " : respecté" : " : pas encore respecté";
            });
        };
        champ.addEventListener("input", majRegles);
        majRegles();
    });

    /* Confirmation du mot de passe */
    const motDePasse = document.getElementById("password");
    const confirmation = document.getElementById("password_confirm");
    const message = document.getElementById("message-confirmation");
    if (motDePasse && confirmation && message) {
        const verifier = () => {
            const different = confirmation.value !== "" && confirmation.value !== motDePasse.value;
            message.hidden = !different;
            confirmation.setAttribute("aria-invalid", String(different));
        };
        confirmation.addEventListener("input", verifier);
        motDePasse.addEventListener("input", verifier);
    }
});
