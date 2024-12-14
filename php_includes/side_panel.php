<div class="side-panel">
    <i class="fa fa-bars menu-btn"></i>
    <div class="links">
        <h3>
            <a href="<?php 
                // Redirect based on user role
                if ($_SESSION['role'] == 'moderator') {
                    echo 'moderation_dashboard.php';
                } elseif ($_SESSION['role'] == 'official') {
                    echo 'government_dashboard.php';
                } else {
                    echo 'welcome.php';
                }
            ?>">Dashboard</a>
        </h3>
        
        <a href="view_feedback.php" class="dash-link" data-target=".feed">Feedback/Suggestion</a>
        <a href="view_polls.php" class="dash-link" data-target=".polls">Government Polls</a>
        <a href="view_projects.php" class="dash-link" data-target=".projects">Projects</a>
        <a href="view_townhalls.php" class="dash-link" data-target=".meetings">Townhall Meetings</a>
        <a href="view_issues.php" class="dash-link" data-target=".issues">Public Issues</a>
        
        <a href="notification_center.php" class="dash-link active" data-target=".notifications">Notifications</a>

        <?php if ($_SESSION['role'] == 'citizen'): ?>
            <h3>Get Involved</h3>
            <a href="feedback.php" class="dash-link">Submit Feedback</a>
            <a href="report_issue.php" class="dash-link">Report an Issue</a>

        <?php elseif ($_SESSION['role'] != 'citizen'): ?>
            <h3>Administrative Actions</h3>
            <?php if ($_SESSION['role'] == 'moderator'): ?>
                <a href="manage_users.php" class="dash-link">Manage Users</a>
            <?php elseif ($_SESSION['role'] == 'official'): ?>
                <a href="create_poll.php" class="dash-link">Create a Poll</a>
                <a href="create_townhall.php" class="dash-link">Schedule a Town Hall</a>
                <a href="create_project.php" class="dash-link">Create a Project</a>              
            <?php endif; ?>
        <?php endif; ?>
        <h3></h3>
        <a href="logout.php" class="btn-submit sub">Logout</a>
    </div>
</div>

<script type="text/javascript" src="../assets/js/script.js"></script>