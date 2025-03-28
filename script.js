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
            data: [50, 30, 120], 
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
            data: [65, 59, 80, 81, 56, 55, 40], 
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 205, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(201, 203, 207, 0.2)'
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

//PAGINATION
document.addEventListener("DOMContentLoaded", function () {
    let page = 1; // Current page
    const recordsPerPage = 10; // Number of records per page
    let customersData = []; // Store all customers data

    // Fetch and display recent customers
    function fetchRecentCustomers() {
        fetch("1.recent_customers.php")
            .then(response => response.json())
            .then(data => {
                customersData = data.customers;
                displayPage(page);
                document.getElementById("totalCustomers").textContent = data.totalDiscounted;
                document.getElementById("totalSenior").textContent = data.totalSenior;
                document.getElementById("totalPWD").textContent = data.totalPWD;
            })
            .catch(error => console.error("Error fetching customer data:", error));
    }

    // Display the correct page of customers
    function displayPage(page) {
        const startIndex = (page - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const currentCustomers = customersData.slice(startIndex, endIndex);

        const tableBody = document.getElementById("tableBody");
        tableBody.innerHTML = "";
        currentCustomers.forEach(customer => {
            const row = `
            <tr>
                <td>${customer.ID}</td>
                <td>${customer.name}</td>
                <td>${customer.citizen}</td>
                <td>${customer.food}</td>
                <td>${customer.date}</td>
                <td>${customer.time}</td>
                <td>${customer.cashier}</td>
                <td>${customer.branch}</td>
                <td>${customer.discount_percentage}</td>
                <td>${customer.price}</td>
                <td>${customer.discounted_price}</td>
                <td>${customer.control_number}</td>
            </tr>
            `;
            tableBody.innerHTML += row;
        });

        updatePaginationControls();
    }

    // Update pagination controls
    function updatePaginationControls() {
        document.getElementById("pageInfo").textContent = `Page ${page} of ${Math.ceil(customersData.length / recordsPerPage)}`;
        document.getElementById("prevPage").disabled = page === 1;
        document.getElementById("nextPage").disabled = page === Math.ceil(customersData.length / recordsPerPage);
    }

    // Event listeners for pagination
    document.getElementById("prevPage").addEventListener("click", function () {
        if (page > 1) {
            page--;
            displayPage(page);
        }
    });

    document.getElementById("nextPage").addEventListener("click", function () {
        if (page < Math.ceil(customersData.length / recordsPerPage)) {
            page++;
            displayPage(page);
        }
    });

    // Fetch data on load
    fetchRecentCustomers();
});