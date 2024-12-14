<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'official' && $_SESSION['role'] != 'moderator')) {
    echo "Access denied!";
    exit;
}

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export'])) {
    $export_type = $_POST['export'];

    switch ($export_type) {
        case 'feedback':
            // Fetch feedback data
            $stmt = $pdo->query("SELECT * FROM feedback_suggestions");
            $feedback_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="feedback_suggestions.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Suggestion ID', 'Title', 'Description', 'Upvotes', 'Downvotes', 'Status']);
            foreach ($feedback_data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;

        case 'polls':
            // Fetch poll data
            $stmt = $pdo->query("SELECT * FROM polls");
            $poll_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="polls.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Poll ID', 'Title', 'Description', 'Closing Date']);
            foreach ($poll_data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;

        case 'issues':
            // Fetch public issues data
            $stmt = $pdo->query("SELECT * FROM public_issues");
            $issue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="public_issues.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Issue ID', 'Title', 'Description', 'Status']);
            foreach ($issue_data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;

        default:
            echo "Invalid export type.";
            exit;
    }
}
?>
