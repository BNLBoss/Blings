<?php
session_start();
require 'db.php';

// Ensure the user is an official
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'official') {
    echo "Access denied!";
    exit;
}

if(!isset($_GET['project_id'])) {
    $error_msg = "No Project Selected";
    $redirect_link = "view_projects.php";
    header("Location: error.php?msg=$error_msg&redirect=$redirect_link");
    exit();
}

// Fetch project details
$project_id = $_GET['project_id'];
$stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];

    // Update project status
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE project_id = ?");
    $stmt->execute([$status, $project_id]);

    // Fetch the project details
    $project_stmt = $pdo->prepare("SELECT title FROM projects WHERE project_id = ?");
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);

    // Notify all citizens about the project status update
    $citizens_stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'citizen'");
    $citizens = $citizens_stmt->fetchAll(PDO::FETCH_ASSOC);

    $notification_message = "The status of the project titled '{$project['title']}' has been updated to '{$status}'.";
    $notification_link = "project_feedback.php?project_id={$project_id}";
    $notification_type = 'project';

    foreach ($citizens as $citizen) {
        $notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, notification_message, link_url, `type`) VALUES (?, ?, ?, ?)");
        $notification_stmt->execute([$citizen['user_id'], $notification_message, $notification_link, $notification_type]);
    }

    echo "Project status updated!";
    header("Location: view_projects.php");
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
                    <h2>Update Status for: <?php echo htmlspecialchars($project['title']); ?></h2>
                    <br>
                    <form action="update_project.php?project_id=<?php echo $project_id; ?>" method="post">
                        <select name="status" required>
                            <option value="in progress" <?php if ($project['status'] == 'in progress') echo 'selected'; ?>>In Progress</option>
                            <option value="completed" <?php if ($project['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                            <option value="delayed" <?php if ($project['status'] == 'delayed') echo 'selected'; ?>>Delayed</option>
                        </select>
                        <button type="submit" class="btn-submit short">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>