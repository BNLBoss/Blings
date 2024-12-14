<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header('Location: login.php');
    exit;
}

// Get user info from session
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$role = $_SESSION['role'];

// Fetch the last 5 notifications for the logged-in citizen
$notifications_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_date DESC LIMIT 5");
$notifications_stmt->execute([$_SESSION['user_id']]);
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];

    // Update the notification to mark it as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    $stmt->execute([$notification_id]);

    // Redirect to avoid form resubmission
    header('Location: welcome.php');
    exit;
}

// Count unread notifications
$unread_stmt = $pdo->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $unread_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

include 'functions.php';

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
        <?php include 'user.php'; ?>
        <div class="display-section">
            <?php include 'side_panel.php'; ?>
            <div class="main-panel">
                <div class="panel-section welcome-user showing">
                    <h1>Welcome, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h1>
                    <p>Role: <strong><?php echo htmlspecialchars($role); ?></strong></p>
                    <p>Welcome to your dashboard. Here you can manage your account and participate in the community.</p>
                    <hr>
                    <h2>For You</h2>
                    <div class="notifications-grid">
                        <?php if (empty($notifications)): ?>
                            <p>No notifications to display.</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item box">
                                    <div class="indicator <?php echo !$notification['is_read'] ? 'unread' : ''; ?>"></div>
                                    <i class="fa <?php echo $iconMapping[$notification['type']] ?? 'fa-bell'; ?> notification-icon"></i>
                                    <div class="notification-content">
                                        <?php echo htmlspecialchars($notification['notification_message']); ?> <br>
                                        <span class="notification-time"><?php echo timeAgo($notification['sent_date']); ?></span>
                                    </div>
                                    <div class="notification-actions">
                                        <?php if (!empty($notification['link_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($notification['link_url']); ?>" class="btn-view">View Details</a>
                                        <?php endif; ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <form action="notification_center.php" method="post" style="display:inline;">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                <button type="submit" name="mark_read" class="btn-submit">Mark as Read</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <p><a href="notification_center.php" class="btn-submit short">View All</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

