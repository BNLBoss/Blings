<?php
session_start();
require 'db.php';

// Ensure the user is logged in and is an official
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'official') {
    echo "Access denied!";
    exit;
}

// Get the suggestion ID from the URL or form
$suggestion_id = isset($_GET['suggestion_id']) ? $_GET['suggestion_id'] : $_POST['suggestion_id'];

// Fetch feedback details (title, user ID) from the feedback_suggestions table
$stmt = $pdo->prepare("SELECT title, user_id, status, priority FROM feedback_suggestions WHERE suggestion_id = ?");
$stmt->execute([$suggestion_id]);
$suggestion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($suggestion) {
    $suggestion_title = $suggestion['title'];
    $suggestion_user_id = $suggestion['user_id'];
    $current_status = $suggestion['status'];
    $current_priority = $suggestion['priority'];

    // Handle response submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Handle response
        if (isset($_POST['response'])) {
            $response = $_POST['response'];

            // Insert the response into the feedback_responses table
            $response_stmt = $pdo->prepare("INSERT INTO feedback_responses (suggestion_id, official_id, response) VALUES (?, ?, ?)");
            $response_stmt->execute([$suggestion_id, $_SESSION['user_id'], $response]);

            // Insert a notification for the user who submitted the feedback
            $notification_message = "Your feedback titled '{$suggestion_title}' has received a response.";
            $notification_link = "view_feedback.php?suggestion_id={$suggestion_id}";
            $notification_type = "suggestion";
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message, link_url, `type`) VALUES (?, ?, ?, ?)");

            $notification_stmt->execute([$suggestion_user_id, $notification_message, $notification_link, $notification_type]);

            echo "Response submitted and notification sent!";
        }

        // Handle status update
        if (isset($_POST['status'])) {
            $new_status = $_POST['status'];
            $status_stmt = $pdo->prepare("UPDATE feedback_suggestions SET status = ? WHERE suggestion_id = ?");
            $status_stmt->execute([$new_status, $suggestion_id]);

            // Notify the user of the status change
            $notification_message = "The status of your feedback titled '{$suggestion_title}' has been updated to '{$new_status}'.";
            $notification_link = "view_feedback.php?suggestion_id={$suggestion_id}";
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message, link_url) VALUES (?, ?, ?)");
            $notification_stmt->execute([$suggestion_user_id, $notification_message, $notification_link]);

            echo "Feedback status updated and notification sent!";
        }

        // Handle priority update
        if (isset($_POST['priority'])) {
            $priority = isset($_POST['priority']) ? 1 : 0;
            $priority_stmt = $pdo->prepare("UPDATE feedback_suggestions SET priority = ? WHERE suggestion_id = ?");
            $priority_stmt->execute([$priority, $suggestion_id]);

            // Notify the user if the feedback was marked as high priority
            if ($priority == 1) {
                $notification_message = "Your feedback titled '{$suggestion_title}' has been marked as high priority.";
                $notification_link = "view_feedback.php?suggestion_id={$suggestion_id}";
                $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message, link_url) VALUES (?, ?, ?)");
                $notification_stmt->execute([$suggestion_user_id, $notification_message, $notification_link]);
            }

            echo "Feedback priority updated!";
        }

        // Redirect to avoid form resubmission
        header('Location: view_feedback.php');
        exit;
    }
} else {
    echo "Feedback not found.";
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
                <div class="panel-section respond-feedback">
                    <h1>Respond to Feedback</h1>
                    <p>
                    Responding to feedback is a critical aspect of personal and professional growth. Whether the feedback is positive or constructive, it's important to approach it with an open mind and a commitment to improvement.
                    </p>
                    <br>
                    <?php if ($suggestion): ?>
                        <h2>Feedback Title: <?php echo htmlspecialchars($suggestion_title); ?></h2>
                        
                        <!-- Response form -->
                        <form action="respond_feedback.php" method="post">
                            <input type="hidden" name="suggestion_id" value="<?php echo $suggestion_id; ?>">
                            
                            <!-- Submit a response -->
                            <textarea name="response" id="response" rows="5" cols="50" placeholder="Your Response"></textarea>
                            
                            <!-- Update feedback status -->
                            <div class="input-box">
                                <label for="status">Update Status:</label>
                                <select name="status" id="status">
                                    <option value="open" <?php if ($current_status == 'open') echo 'selected'; ?>>Open</option>
                                    <option value="under review" <?php if ($current_status == 'under review') echo 'selected'; ?>>Under Review</option>
                                    <option value="implemented" <?php if ($current_status == 'implemented') echo 'selected'; ?>>Implemented</option>
                                    <option value="rejected" <?php if ($current_status == 'rejected') echo 'selected'; ?>>Rejected</option>
                                    <option value="closed" <?php if ($current_status == 'closed') echo 'selected'; ?>>Closed</option>
                                </select>
                            </div>
                            
                            <!-- Mark as priority -->
                            <div class="input-box">
                                <label for="priority">Mark as High Priority:</label>
                                <input type="checkbox" name="priority" id="priority" value="1" <?php if ($current_priority == 1) echo 'checked'; ?>>
                            </div>
                            
                            <!-- Submit all changes -->
                            <button type="submit" class="btn-submit short">Submit Changes</button>
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

