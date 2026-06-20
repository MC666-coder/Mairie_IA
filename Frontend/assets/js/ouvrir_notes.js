document.addEventListener("DOMContentLoaded", () => {
    const notesContainer = document.getElementById("liste-notes");
    const btnNotes = document.getElementById("show-full-notes");

    if (!notesContainer || !btnNotes) return;

    const notes = notesContainer.querySelectorAll(".note-item");

    // Si moins de 6 notes → pas besoin de bouton
    if (notes.length <= 5) {
        btnNotes.style.display = "none";
        return;
    }

    // Masquer toutes sauf les 5 dernières
    notes.forEach((note, index) => {
        if (index < notes.length - 5) {
            note.classList.add("hidden-item");
        }
    });

    // Masquer les groupes de dates vides
    const groups = notesContainer.querySelectorAll(".notes-date-group");

    groups.forEach(group => {
        const visibleNotes = group.querySelectorAll(".note-item:not(.hidden-item)");
        if (visibleNotes.length === 0) {
            group.classList.add("hidden-item");
        }
    });

    // Afficher toutes les notes au clic
    btnNotes.addEventListener("click", () => {
        notes.forEach(note => note.classList.remove("hidden-item"));
        groups.forEach(group => group.classList.remove("hidden-item"));
        btnNotes.style.display = "none"; // Le bouton disparaît définitivement
    });
});