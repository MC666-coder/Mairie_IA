document.addEventListener('DOMContentLoaded', function() {

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        height: 650,


        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },

        events: function(fetchInfo, successCallback, failureCallback) {

            let categorie = document.getElementById('filtre-categorie').value;
            let lieu = document.getElementById('filtre-lieu').value;
            let publicVal = document.getElementById('filtre-public').value;

            fetch(`load_events.php?categorie=${categorie}&lieu=${lieu}&public=${publicVal}`)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        }
    });

    calendar.render();

    document.querySelectorAll('#filtre-categorie, #filtre-lieu, #filtre-public')
        .forEach(select => {
            select.addEventListener('change', () => {
                calendar.refetchEvents();
            });
        });
});
