<?php
include 'db.php';

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

// Count unread notifications
$unread_stmt = $pdo->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $unread_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

?>


<div class="header">
    <div class="nav-bar">
        <?php if ($role == 'citizen') : ?>
            <a href="welcome.php" class="logo">
                <img src="../assets/images/logo.png" alt="">
                Citizen Voice
            </a>
        <?php elseif ($role == 'moderator'): ?>
            <a href="moderation_dashboard.php" class="logo">
                <img src="../assets/images/logo.png" alt="">
                Citizen Voice
            </a>
        <?php elseif ($role == 'official'): ?>
            <a href="government_dashboard.php" class="logo">
                <img src="../assets/images/logo.png" alt="">
                Citizen Voice
            </a>
        <?php endif ?>
        <div class="user">
            <div class="icons">
                <?php if ($unread_notifications > 0): ?>
                    <a href="notification_center.php"><i class="fa fa-bell icon notification-active"></i></a>
                <?php elseif ($unread_notifications <= 0): ?>
                    <a href="notification_center.php"><i class="fa fa-bell icon"></i></a>
                <?php endif; ?>
            </div>
            <p>
            <span class="role-info highlight">
                <?php if ($role == 'official'): ?>
                    Government
                <?php elseif ($role == 'citizen'): ?>
                    Citizen
                <?php elseif ($role == 'moderator'): ?>
                    Moderator
                <?php endif; ?>
            </span>
            <?php echo  htmlspecialchars(' | '. $first_name . ' ' . $last_name); ?></p>
            <i class="fa fa-user-circle circle"></i>
        </div>
    </div>
    
    
</div>