var totalRecords = document.getElementById("totalRecords");

const totalChart = new Chart(totalRecords, {
    type: "doughnut",
    data: {
        labels: [
            pending,
            ongoing,
            completed,
            canceled
        ],
        datasets: [
            {
                data:[
                    shiftPlanningWithoutEmployee,
                    shiftPlanningOngoing,
                    shiftPlanningCompleted,
                    shiftPlanningCanceled,
                ],
                backgroundColor: [
                    '#ffcc00',
                    '#7367f0',
                    '#28c76f',
                    '#ea5a5b',
                ],
                hoverBackgroundColor: [
                    'rgba(255,204,0,0.75)',
                    'rgba(115,103,240,0.75)',
                    'rgba(40,199,111,0.75)',
                    'rgba(234,90,91,0.75)',
                ],
            }
        ]
    }
})
