<?php
session_start();
require 'db.php';

// Ensure the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['official', 'moderator'])) {
    echo "Access denied!";
    exit;
}

if(isset($_REQUEST['suggestion_id'])) {
    // Get the suggestion ID from the URL or form submission
    $suggestion_id = isset($_GET['suggestion_id']) ? $_GET['suggestion_id'] : $_POST['suggestion_id'];

    // Fetch feedback details (title, user ID, status) from the feedback_suggestions table
    $stmt = $pdo->prepare("SELECT title, user_id, status FROM feedback_suggestions WHERE suggestion_id = ?");
    $stmt->execute([$suggestion_id]);
    $suggestion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($suggestion) {
        $suggestion_title = $suggestion['title'];
        $suggestion_user_id = $suggestion['user_id'];
        $current_status = $suggestion['status'];

        // Handle moderator actions (deleting feedback, closing feedback, etc.)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Handle feedback deletion by moderator
            if (isset($_POST['delete_feedback'])) {
                // Delete the feedback
                $delete_stmt = $pdo->prepare("DELETE FROM feedback_suggestions WHERE suggestion_id = ?");
                $delete_stmt->execute([$suggestion_id]);

                // Notify the user whose feedback was deleted
                $notification_message_user = "Your feedback titled '{$suggestion_title}' was deleted by a moderator.";
                $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message) VALUES (?, ?)");
                $notification_stmt->execute([$suggestion_user_id, $notification_message_user]);

                // Notify officials of the deletion
                $officials_stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'official'");
                $officials = $officials_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($officials as $official) {
                    $notification_message_official = "Feedback titled '{$suggestion_title}' was deleted by a moderator.";
                    $notification_stmt->execute([$official['user_id'], $notification_message_official]);
                }

                echo "Feedback deleted and notifications sent.";
                // Redirect to avoid form resubmission
                header('Location: view_feedback.php');
                exit;
            }

            // Handle closing feedback (marking it as closed)
            if (isset($_POST['close_feedback'])) {
                $close_stmt = $pdo->prepare("UPDATE feedback_suggestions SET status = 'closed' WHERE suggestion_id = ?");
                $close_stmt->execute([$suggestion_id]);

                // Notify the user whose feedback was closed
                $notification_message_user = "Your feedback titled '{$suggestion_title}' has been marked as closed.";
                $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message) VALUES (?, ?)");
                $notification_stmt->execute([$suggestion_user_id, $notification_message_user]);

                echo "Feedback marked as closed.";
                // Redirect to avoid form resubmission
                header('Location: view_feedback.php');
                exit;
            }

            // Handle marking feedback as inappropriate
            if (isset($_POST['mark_inappropriate'])) {
                // Notify the user and officials about inappropriate feedback
                $notification_message_user = "Your feedback titled '{$suggestion_title}' has been flagged as inappropriate by a moderator.";
                $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message) VALUES (?, ?)");
                $notification_stmt->execute([$suggestion_user_id, $notification_message_user]);

                foreach ($officials as $official) {
                    $notification_message_official = "Feedback titled '{$suggestion_title}' has been flagged as inappropriate by a moderator.";
                    $notification_stmt->execute([$official['user_id'], $notification_message_official]);
                }

                echo "Feedback flagged as inappropriate.";
                // Redirect to avoid form resubmission
                header('Location: view_feedback.php');
                exit;
            }
        }
    } else {
        echo "Feedback not found.";
    }
}

if(isset($_REQUEST['issue_id'])) {
    // Assuming $issue_id is the issue being deleted
    $issue_id = isset($_GET['issue_id']) ? $_GET['issue_id'] : $_POST['issue_id'];

    // Fetch the user who reported the issue and the officials
    $issue_stmt = $pdo->prepare("SELECT user_id, issue_title FROM public_issues WHERE issue_id = ?");
    $issue_stmt->execute([$issue_id]);
    $issue = $issue_stmt->fetch(PDO::FETCH_ASSOC);

    if ($issue) {
        $issue_user_id = $issue['user_id'];
        $issue_title = $issue['issue_title'];

        // Delete the issue
        $delete_stmt = $pdo->prepare("DELETE FROM public_issues WHERE issue_id = ?");
        $delete_stmt->execute([$issue_id]);

        // Notify the user whose issue was deleted
        $notification_message_user = "Your issue titled '{$issue_title}' was deleted by a moderator.";
        $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notification_stmt->execute([$issue_user_id, $notification_message_user]);

        // Notify all government officials about the deletion
        $officials_stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'official'");
        $officials = $officials_stmt->fetchAll(PDO::FETCH_ASSOC);

        $notification_message_official = "A moderator deleted the issue titled '{$issue_title}'.";
        foreach ($officials as $official) {
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notification_stmt->execute([$official['user_id'], $notification_message_official]);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/fontawesome-free-6.6.0-web/css/fontawesome.css">
    <link rel="stylesheet" href="../assets/fontawesome-free-6.6.0-web/css/regular.css">
    <link rel="stylesheet" href="../assets/fontawesome-free-6.6.0-web/css/solid.css">
    <link rel="stylesheet" href="../assets/fontawesome-free-6.6.0-web/css/brands.css">
    <link rel="stylesheet" href="../assets/fontawesome-free-6.6.0-web/css/svg-with-js.css">
<!-- Custom Styles for Interactive Effects and Alignment -->
<style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Header and User Info */
        .header {
            background-color: #333;
            color: white;
            padding: 20px;
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo img {
            height: 40px;
        }

        .user {
            display: flex;
            align-items: center;
        }

        .user p {
            margin: 0 10px;
            color: #ddd;
        }

        .circle {
            font-size: 30px;
            color: #ddd;
            margin-left: 10px;
        }

        /* Sidebar Styles */
        .side-panel {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .side-panel .links a {
            display: block;
            margin: 10px 0;
            text-decoration: none;
            color: white;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .side-panel .links a:hover {
            color: #1abc9c;
        }

        /* Main Panel Styles */
        .main-panel {
            flex-grow: 1;
            padding: 20px;
            background-color: #ffffff;
            overflow-y: auto;
        }

        .panel-section {
            margin-bottom: 30px;
        }

        .panel-section h1 {
            font-size: 26px;
            color: #333;
            margin-bottom: 10px;
        }

        .panel-section p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
        }

        /* Welcome Section */
        .welcome-user h1 {
            font-size: 28px;
            color: #333;
        }

        .welcome-user p {
            font-size: 18px;
            color: #666;
            margin-top: 10px;
        }

        .panel-section hr {
            margin-top: 20px;
            border: 1px solid #ddd;
        }

        /* Buttons */
        .btn-submit {
            background-color: #1abc9c;
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #16a085;
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid #1abc9c;
            color: #1abc9c;
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-outline:hover {
            background-color: #1abc9c;
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .side-panel {
                transform: translateX(-250px);
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 100;
            }

            .side-panel.active {
                transform: translateX(0);
            }

            .menu-btn {
                display: block;
                font-size: 30px;
                color: white;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include 'user.php';?>
        <div class="display-section">
            <?php include 'side_panel.php';?>
            <div class="main-panel">
                <div class="panel-section manage-feedack">
                    <h1>Moderate Feedback</h1>
                    <br>

                    <?php if ($suggestion): ?>
                        <h2>Feedback Title: <?php echo htmlspecialchars($suggestion_title); ?></h2>

                        <!-- Moderator Actions -->
                        <form action="manage_feedback.php" method="post">
                            <input type="hidden" name="suggestion_id" value="<?php echo $suggestion_id; ?>">
                            
                            <!-- Delete feedback action (only for moderators) -->
                            <?php if ($_SESSION['role'] == 'moderator'): ?>
                                <button type="submit" class="btn-submit short" name="delete_feedback" onclick="return confirm('Are you sure you want to delete this feedback?');"><i class="fa fa-trash"></i> Delete Feedback</button>
                                
                                <!-- Close feedback action -->
                                <button type="submit" class="btn-submit short" name="close_feedback" onclick="return confirm('Are you sure you want to close this feedback?');"> <i class="fa fa-close"></i> Close Feedback</button>

                                <!-- Mark feedback as inappropriate -->
                                <button type="submit" class="btn-submit short" name="mark_inappropriate"><i class="fa fa-warning"></i> Mark as Inappropriate</button>
                            <?php endif; ?>
                        </form>

                    <?php else: ?>
                        <p>Feedback not found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
</body>
</html>
