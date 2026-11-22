/* =========================================================
   Agenda municipal : initialisation de FullCalendar 6
   ---------------------------------------------------------
   Les événements viennent de load_events.php (événements publiés et
   visibles des citoyens), uniquement pour la période affichée.
   Le paquet global ne contient pas la traduction française des boutons :
   on la donne ici (les noms de jours et de mois viennent du navigateur).
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {

    const calendarEl = document.getElementById("calendar");
    if (!calendarEl || typeof FullCalendar === "undefined") {
        return;
    }

    const filtreService = document.getElementById("filtre-service");
    const filtreLieu = document.getElementById("filtre-lieu");
    const petitEcran = window.matchMedia("(max-width: 700px)").matches;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: "fr",
        firstDay: 1,                                   // la semaine commence le lundi
        initialView: petitEcran ? "listMonth" : "dayGridMonth",
        height: "auto",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,listMonth"
        },
        buttonText: {
            today: "Aujourd'hui",
            month: "Mois",
            list: "Liste"
        },
        buttonHints: {
            prev: "Mois précédent",
            next: "Mois suivant",
            today: "Revenir à aujourd'hui"
        },
        allDayText: "Toute la journée",
        moreLinkText: n => `+ ${n} autre${n > 1 ? "s" : ""}`,
        noEventsText: "Aucun événement sur cette période",
        eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
        // Vue « Mois » : étiquettes pleines à la couleur du service, avec le titre
        // (l'heure est dans l'infobulle et dans la vue « Liste »)
        eventDisplay: "block",
        views: { dayGridMonth: { displayEventTime: false } },

        events(info, succes, echec) {
            const params = new URLSearchParams({
                start: info.startStr,
                end: info.endStr,
                service: filtreService ? filtreService.value : "",
                lieu: filtreLieu ? filtreLieu.value : ""
            });
            fetch(`load_events.php?${params}`, { headers: { "Accept": "application/json" } })
                .then(res => {
                    if (!res.ok) {
                        throw new Error("HTTP " + res.status);
                    }
                    return res.json();
                })
                .then(succes)
                .catch(echec);
        },

        // Infobulle native : lieu et service au survol
        eventDidMount(arg) {
            const { lieu, service } = arg.event.extendedProps;
            arg.el.title = [arg.event.title, lieu, service].filter(Boolean).join(" · ");
        }
    });

    calendar.render();

    // FullCalendar ne suit que le redimensionnement de la fenêtre. Si la
    // largeur de son conteneur change autrement (polices chargées, mise en
    // page, tablette tournée...), la grille garderait une largeur périmée :
    // on le fait se remesurer à chaque changement de largeur du conteneur.
    if ("ResizeObserver" in window) {
        let largeur = 0;
        new ResizeObserver(([entree]) => {
            const nouvelle = Math.round(entree.contentRect.width);
            if (nouvelle !== largeur) {
                largeur = nouvelle;
                calendar.updateSize();
            }
        }).observe(calendarEl.parentElement);
    }

    [filtreService, filtreLieu].forEach(select => {
        select?.addEventListener("change", () => calendar.refetchEvents());
    });
});
