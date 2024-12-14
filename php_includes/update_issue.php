<?php
session_start();
require 'db.php';

// Ensure the user is logged in and is an official
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'official') {
    header('Location: login.php');
    exit;
}

if(!isset($_GET['issue_id'])) {
    $error_msg = "No Issue Selected";
    $redirect_link = "view_issues.php";
    header("Location: error.php?msg=$error_msg&redirect=$redirect_link");
    exit();
}

// Get the issue ID from the URL
$issue_id = $_GET['issue_id'];

// Fetch the current issue details
$stmt = $pdo->prepare("SELECT * FROM public_issues WHERE issue_id = ?");
$stmt->execute([$issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'];

    // Update the status of the issue
    $stmt = $pdo->prepare("UPDATE public_issues SET status = ? WHERE issue_id = ?");
    $stmt->execute([$new_status, $issue_id]);

    // Fetch the issue details and the user who reported it
    $issue_stmt = $pdo->prepare("SELECT issue_title, user_id FROM public_issues WHERE issue_id = ?");
    $issue_stmt->execute([$issue_id]);
    $issue = $issue_stmt->fetch(PDO::FETCH_ASSOC);

    // Notify the citizen who reported the issue
    $notification_message = "The status of your issue titled '{$issue['issue_title']}' has been updated to '{$new_status}'.";
    $notification_type = "issue";
    $notification_link = "view_issue.php?issue_id={$issue_id}";

    $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message, link_url, `type`) VALUES (?, ?, ?, ?)");
    $notification_stmt->execute([$issue['user_id'], $notification_message, $notification_link, $notification_type]);


    echo "Issue status updated!";
    header('Location: view_issues.php');
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/fontawesome-free-6.6.0-web/css/all.css">
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
                <div class="panel-section">
                <h2>Update Status for: <?php echo htmlspecialchars($issue['issue_title']); ?></h2>
                <br>
                    <form action="update_issue.php?issue_id=<?php echo $issue_id; ?>" method="post">                    
                        <label for="status">New Status:</label>
                        <select name="status" required>
                            <option value="open" <?php if ($issue['status'] == 'open') echo 'selected'; ?>>Open</option>
                            <option value="under investigation" <?php if ($issue['status'] == 'under investigation') echo 'selected'; ?>>Under Investigation</option>
                            <option value="resolved" <?php if ($issue['status'] == 'resolved') echo 'selected'; ?>>Resolved</option>
                        </select>
                        <button type="submit" class="btn-submit short">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
