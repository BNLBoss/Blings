<?php
require 'db.php';

// Ensure the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'official' && $_SESSION['role'] != 'moderator')) {
    echo "Access denied!";
    exit;
}

// Fetch metrics
$feedback_count = $pdo->query("SELECT COUNT(*) FROM feedback_suggestions")->fetchColumn();
$poll_count = $pdo->query("SELECT COUNT(*) FROM polls")->fetchColumn();
$issue_count = $pdo->query("SELECT COUNT(*) FROM public_issues")->fetchColumn();
$completed_polls = $pdo->query("SELECT COUNT(*) FROM polls WHERE closing_date < NOW()")->fetchColumn();
?>

<h2>Overview</h2>
<p>Total Feedback Suggestions: <?php echo $feedback_count; ?></p>
<p>Total Polls Created: <?php echo $poll_count; ?></p>
<p>Total Public Issues Reported: <?php echo $issue_count; ?></p>
<p>Total Completed Polls: <?php echo $completed_polls; ?></p>
<br>

<div class="stats">
    <h2>Charts</h2>
    <p>Citizen Engangement is driven by your participation. Here’s how your involvement has contributed to our collective progress.</p>
    <div class="charts">
        <!-- Pie Chart -->
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
        <!-- Line Chart -->
        <div class="chart-container">
            <canvas id="lineChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
    </div>
</div>

<h2>Export Data</h2>
<form action="export_data.php" method="post">
    <button type="submit"  class="btn-submit short"name="export" value="feedback">Export Feedback Suggestions</button>
    <button type="submit"  class="btn-submit short"name="export" value="polls">Export Polls</button>
    <button type="submit"  class="btn-submit short"name="export" value="issues">Export Public Issues</button>
</form>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch data from the PHP script
fetch('fetch.php')
.then(response => response.json())
.then(data => {
if (data.error) {
    console.error(data.error);
    return;
}

// Data for the Pie Chart
const pieData = {
    labels: ['Projects Completed', 'Projects In-progress'],
    datasets: [{
        data: [data.projects.completedProjects, data.projects.ongoingProjects],
        backgroundColor: ['#6e5796', '#dcd4ec'], // Primary colors
        hoverBackgroundColor: ['#6e5796', '#dcd3ec'] // Primary colors
    }]
};

// Data for the Line Chart
const engagementLabels = data.engagement.map(item => item.period); // Use 'period' instead of 'month'
const engagementCounts = data.engagement.map(item => item.engagementCount);

// Determine the period type based on the data format (e.g., "%Y-%m-%d" for days, "%Y-%u" for weeks, etc.)
let periodType;
if (data.engagement.length > 0) {
    const samplePeriod = data.engagement[0].period;
    if (/^\d{4}-\d{2}-\d{2}$/.test(samplePeriod)) {
        periodType = 'Day';
    } else if (/^\d{4}-\d{2}$/.test(samplePeriod)) {
        periodType = 'Month';
    } else if (/^\d{4}-\d{2}$/.test(samplePeriod)) {
        periodType = 'Week';
    } else {
        periodType = 'Unknown';
    }
}

const lineData = {
    labels: engagementLabels,
    datasets: [{
        label: `User Engagement (${periodType})`,  // Dynamically label the period
        data: engagementCounts,
        fill: false,
        borderColor: '#39285b',
        tension: 0.1
    }]
};


// Data for the Bar Chart
const roleLabels = data.roles.map(item => item.role);
const roleCounts = data.roles.map(item => item.count);

const barData = {
    labels: roleLabels,
    datasets: [{
        label: 'Number of Users',
        data: roleCounts,
        backgroundColor: '#dcd4ec', // Light color for bars
        borderColor: '#6f4eb0', // Primary_other for border color
        borderWidth: 1 // Optional: to enhance the visual
    }]
};

// Create the charts
const ctxPie = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(ctxPie, {
    type: 'pie',
    data: pieData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
        },
    },
});

const ctxLine = document.getElementById('lineChart').getContext('2d');
const lineChart = new Chart(ctxLine, {
    type: 'line',
    data: lineData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
            },
            tooltip: {
                backgroundColor: '#6e5796', // Light primary color for tooltip
            }
        },
        scales: {
            x: {
                grid: {
                    color: '#dcd4ec' // Light color for grid
                }
            },
            y: {
                grid: {
                    color: '#dcd4ec' // Light color for grid
                }
            }
        }
    },
});

const ctxBar = document.getElementById('barChart').getContext('2d');
const barChart = new Chart(ctxBar, {
    type: 'bar',
    data: barData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
            },
            tooltip: {
                backgroundColor: '#6e5796', // Light primary color for tooltip
            }
        },
    },
});
})
.catch(error => console.error('Error fetching data:', error));
</script>
