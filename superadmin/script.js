function piechart() {
    const ctx = document.getElementById('myPieChart')?.getContext('2d');
    if (!ctx) {
        console.error("Pie chart canvas not found!");
        return;
    }

    const data = {
        labels: ['Senior Citizen', 'PWD', 'Regular Customers'],
        datasets: [{
            label: 'Customer Distribution',
            data: [50, 30, 120],  /*CONNECT DATABASE HERE */
            backgroundColor: [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)'
            ],
            hoverOffset: 4
        }]
    };

    new Chart(ctx, {
        type: 'pie',
        data: data
    });
}

function renderBarChart() {
    const ctx = document.getElementById('myBarChart')?.getContext('2d');
    if (!ctx) {
        console.error("Bar chart canvas not found!");
        return;
    }

    const labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"];

    const data = {
        labels: labels,
        datasets: [{
            label: 'Monthly Sales',
            data: [65, 59, 80, 81, 56, 55, 40], /*CONNECT DATABASE HERE */
            backgroundColor: [
                'rgba(255, 99, 132)',
                'rgba(255, 159, 64)',
                'rgba(255, 205, 86)',
                'rgba(75, 192, 192)',
                'rgba(54, 162, 235)',
                'rgba(153, 102, 255)',
                'rgba(201, 203, 207)'
            ],
            borderColor: [
                'rgb(255, 99, 132)',
                'rgb(255, 159, 64)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)',
                'rgb(201, 203, 207)'
            ],
            borderWidth: 1
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// ✅ Run both charts on page load
window.onload = function() {
    piechart();
    renderBarChart();
};
