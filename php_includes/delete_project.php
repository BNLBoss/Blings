<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the role of moderator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'moderator') {
    header('Location: logout.php');
    $error_msg = "You've been logged out";
    $redirect_link = "logout.php";
    header("Location: error.php?msg=$error_msg&redirect=$redirect_link");
    exit();
}


// Get the project ID from the POST request
if (isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];

    // Fetch project details before deletion (optional, for notification purposes)
    $project_stmt = $pdo->prepare("SELECT title FROM projects WHERE project_id = ?");
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        // Delete the project from the database
        $delete_stmt = $pdo->prepare("DELETE FROM projects WHERE project_id = ?");
        $delete_stmt->execute([$project_id]);

        // (Optional) Notify citizens and officials that the project was deleted
        $notification_message = "The project titled '{$project['title']}' has been deleted by a moderator.";
        $notification_type = "project";

        // Notify all citizens and officials
        $users_stmt = $pdo->query("SELECT user_id FROM users WHERE role IN ('citizen', 'official')");
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message, `type`) VALUES (?, ?, ?)");
            $notification_stmt->execute([$user['user_id'], $notification_message, $notification_type]);
        }

        // Redirect to the projects list with a success message
        header('Location: view_projects.php?delete=success');
        exit;
    } else {
        // If project not found, redirect with an error message
        header('Location: view_projects.php?delete=error');
        exit;
    }
} else {
    // If no project ID is passed, redirect with an error message
    $error_msg = "No Project Selected";
    $redirect_link = "view_projects.php";
    header("Location: error.php?msg=$error_msg&redirect=$redirect_link");
    exit();
}
