/* =========================================================
   ICÔNES SVG DU SITE côté JavaScript (style Lucide, licence ISC)
   ---------------------------------------------------------
   Même rendu que la fonction PHP icone() (Backend/Fonctions/icones.php).
   - icone("trombone")          -> chaîne SVG (pour un gabarit HTML)
   - iconeElement("trombone")   -> élément <svg> (pour prepend / append)
   ========================================================= */
(function () {
    "use strict";

    const ICONES = {
        "trombone": '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
        "fichier": '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/>',
        "image": '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        "alerte": '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        "erreur": '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
        "valide": '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        "chevron-haut": '<path d="m18 15-6-6-6 6"/>',
        "chevron-bas": '<path d="m6 9 6 6 6-6"/>'
    };

    function icone(nom) {
        if (!ICONES[nom]) return "";
        return '<svg class="icone icone-' + nom + '" aria-hidden="true" focusable="false"'
            + ' width="1.1em" height="1.1em" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            + ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
            + ' style="vertical-align:-0.18em;flex-shrink:0">' + ICONES[nom] + "</svg>";
    }

    function iconeElement(nom) {
        const modele = document.createElement("template");
        modele.innerHTML = icone(nom);
        return modele.content.firstChild; // null si le nom est inconnu
    }

    /* Icône selon le type d'un document (PDF, image, autre) */
    function iconeDocument(type) {
        if (type === "application/pdf") return "fichier";
        if (type && type.startsWith("image/")) return "image";
        return "trombone";
    }

    window.icone = icone;
    window.iconeElement = iconeElement;
    window.iconeDocument = iconeDocument;
})();
