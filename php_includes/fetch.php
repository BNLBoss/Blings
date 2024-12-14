<?php
header('Content-Type: application/json');

require 'db.php';

// Fetch completed and ongoing projects
$projectQuery = $pdo->prepare("SELECT 
                                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completedProjects,
                                    SUM(CASE WHEN status = 'in progress' THEN 1 ELSE 0 END) AS ongoingProjects 
                                FROM projects");
$projectQuery->execute();
$projectData = $projectQuery->fetch(PDO::FETCH_ASSOC);

// Fetch the range of submission dates
$dateRangeQuery = $pdo->prepare("SELECT MIN(submission_date) AS earliest, MAX(submission_date) AS latest FROM feedback_suggestions");
$dateRangeQuery->execute();
$dateRange = $dateRangeQuery->fetch(PDO::FETCH_ASSOC);

$earliestDate = new DateTime($dateRange['earliest']);
$latestDate = new DateTime($dateRange['latest']);
$interval = $earliestDate->diff($latestDate)->days; // Calculate the number of days between the two dates

if ($interval <= 30) {
    // Group by day if the data is within a month
    $dateFormat = '%Y-%m-%d';
} elseif ($interval <= 365) {
    // Group by week if the data spans several months but is within a year
    $dateFormat = '%Y-%u'; // Week-based format
} else {
    // Group by month if the data spans more than a year
    $dateFormat = '%Y-%m';
}

// Fetch user engagement dynamically based on the date range
$engagementQuery = $pdo->prepare("
    SELECT 
        DATE_FORMAT(submission_date, :dateFormat) AS period,
        COUNT(*) AS engagementCount 
    FROM feedback_suggestions 
    GROUP BY period 
    ORDER BY period
");
$engagementQuery->execute([':dateFormat' => $dateFormat]);
$engagementData = $engagementQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch role distribution
$roleQuery = $pdo->prepare("SELECT 
                                role,
                                COUNT(*) AS count 
                            FROM users 
                            GROUP BY role");
$roleQuery->execute();
$roleData = $roleQuery->fetchAll(PDO::FETCH_ASSOC);

// Prepare the response
echo json_encode([
    'projects' => $projectData,
    'engagement' => $engagementData,
    'roles' => $roleData
]);

?>