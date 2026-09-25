document.addEventListener('DOMContentLoaded', function() {
    // View Shift Planning Modal
    document.querySelectorAll('[data-shift-planning-view]').forEach(function(element) {
        element.addEventListener('click', function() {
            const shiftPlanning = JSON.parse(this.dataset.shiftPlanningView);
            const modalTitle = document.querySelector('#shiftPlanningModal .modal-title');
            const modalBody = document.querySelector('#shiftPlanningModal .modal-body');

            modalTitle.textContent = shiftPlanning.name;
            modalBody.innerHTML = `
                <p>${shiftPlanning.date}</p>
                <p><span class="fw-bold">${window.translations.startingHour}:</span> ${shiftPlanning.starting_hour}</p>
                <p><span class="fw-bold">${window.translations.endingHour}:</span> ${shiftPlanning.ending_hour}</p>
            `;

            $('#shiftPlanningModal').modal('show');
        });
    });

    // Update Shift Planning Modal
    document.querySelectorAll('[data-shift-planning-update]').forEach(function(element) {
        element.addEventListener('click', function() {
            const shiftPlanning = JSON.parse(this.dataset.shiftPlanningUpdate);
            const modalTitle = document.querySelector('#updateShiftPlanningModal .modal-title');
            const modalBody = document.querySelector('#updateShiftPlanningModal .modal-body');

            modalTitle.textContent = shiftPlanning.date;
            modalBody.innerHTML = `
                <form action="${window.routes.updateShiftPlanning.replace(':id', shiftPlanning.id)}" method="POST">
                    @method('PUT')
                    @csrf
                    <!-- Assuming you'll pass the shift planning form component here -->
                    <x-planning::modules.shift_planning.shift_plannings_form :shiftPlanning="$shiftPlanning" />
                </form>
            `;

            $('#updateShiftPlanningModal').modal('show');
        });
    });
});
