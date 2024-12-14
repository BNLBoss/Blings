<?php
session_start();
require 'db.php';
include 'functions.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user role
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch all feedback suggestions with user names
$stmt = $pdo->query("
    SELECT fs.*, u.first_name, u.last_name 
    FROM feedback_suggestions fs
    JOIN users u ON fs.user_id = u.user_id
");
$feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare to fetch responses for each feedback
$response_stmt = $pdo->prepare("SELECT * FROM feedback_responses WHERE suggestion_id = ?");

// Handle upvote/downvote by citizens
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote']) && $user_role == 'citizen') {
    $suggestion_id = $_POST['suggestion_id'];
    $new_vote = $_POST['vote']; // 'upvote' or 'downvote'

    // Check if the user has already voted on this feedback
    $check_vote = $pdo->prepare("SELECT * FROM feedback_votes WHERE suggestion_id = ? AND user_id = ?");
    $check_vote->execute([$suggestion_id, $user_id]);
    $existing_vote = $check_vote->fetch(PDO::FETCH_ASSOC);

    if ($existing_vote) {
        // User has already voted, check if they are changing their vote
        if ($existing_vote['vote_type'] != $new_vote) {
            // Remove previous vote
            if ($existing_vote['vote_type'] == 'upvote') {
                $pdo->prepare("UPDATE feedback_suggestions SET upvotes = upvotes - 1 WHERE suggestion_id = ?")
                    ->execute([$suggestion_id]);
            } else if ($existing_vote['vote_type'] == 'downvote') {
                $pdo->prepare("UPDATE feedback_suggestions SET downvotes = downvotes - 1 WHERE suggestion_id = ?")
                    ->execute([$suggestion_id]);
            }

            // Update vote in feedback_votes table
            $stmt = $pdo->prepare("UPDATE feedback_votes SET vote_type = ? WHERE suggestion_id = ? AND user_id = ?");
            $stmt->execute([$new_vote, $suggestion_id, $user_id]);

            // Apply the new vote
            if ($new_vote == 'upvote') {
                $pdo->prepare("UPDATE feedback_suggestions SET upvotes = upvotes + 1 WHERE suggestion_id = ?")
                    ->execute([$suggestion_id]);
            } else if ($new_vote == 'downvote') {
                $pdo->prepare("UPDATE feedback_suggestions SET downvotes = downvotes + 1 WHERE suggestion_id = ?")
                    ->execute([$suggestion_id]);
            }

            echo "Your vote has been updated!";
        } else {
            echo "You have already voted this way!";
        }
    } else {
        // User has not voted yet, insert their vote
        $stmt = $pdo->prepare("INSERT INTO feedback_votes (suggestion_id, user_id, vote_type) VALUES (?, ?, ?)");
        $stmt->execute([$suggestion_id, $user_id, $new_vote]);

        // Apply the new vote
        if ($new_vote == 'upvote') {
            $pdo->prepare("UPDATE feedback_suggestions SET upvotes = upvotes + 1 WHERE suggestion_id = ?")
                ->execute([$suggestion_id]);
        } else if ($new_vote == 'downvote') {
            $pdo->prepare("UPDATE feedback_suggestions SET downvotes = downvotes + 1 WHERE suggestion_id = ?")
                ->execute([$suggestion_id]);
        }

        echo "Vote submitted!";
    }

    // Redirect to avoid form resubmission
    header('Location: view_feedback.php');
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
                <div class="panel-section feedback">
                    <h1>Feedback Suggestions</h1>
                    <p>Explore suggestions and ideas shared by the community. Feel free to browse, leave your feedback, or engage with other citizens' thoughts. Your input helps shape a better community!</p>
                    <hr>

                    <div class="feed">
                        <?php if (empty($feedback)): ?>
                            <p>No feedback has been submitted yet.</p>
                        <?php else: ?>
                            <?php foreach ($feedback as $suggestion): ?>
                                <div class="post-item">
                                    <div class="post">
                                        <div class="post-head">
                                            <i class="fa fa-user-circle circle"></i>
                                            <h4><?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?> | <small class="highlight"><?php echo timeAgo($suggestion['submission_date']); ?></small></h4>
                                        </div>
                                        <div class="post-body">
                                            <h3><?php echo htmlspecialchars($suggestion['title']); ?></h3>
                                            <?php
                                            $post_paragraphs = explode('\n', $suggestion['description']);
                                            foreach($post_paragraphs as $p) {
                                                if ($p != '') {
                                                    echo "<p> " .$p. "</p>";
                                                }
                                            }
                                            ?>

                                            <?php
                                                $suggestion_id = $suggestion['suggestion_id'];

                                                // Query to get the number of upvotes for the specific suggestion_id
                                                $upvotes_query = $pdo->prepare("SELECT * FROM feedback_votes WHERE vote_type = 'upvote' AND suggestion_id = ?");
                                                $upvotes_query->execute([$suggestion_id]);  // Pass an array to execute()
                                                $upvote_count = $upvotes_query->rowCount();

                                                // Query to get the number of downvotes for the specific suggestion_id
                                                $downvotes_query = $pdo->prepare("SELECT * FROM feedback_votes WHERE vote_type = 'downvote' AND suggestion_id = :suggestion_id");
                                                $downvotes_query->execute(['suggestion_id' => $suggestion_id]);
                                                $downvote_count = $downvotes_query->rowCount();
                                            ?>
                                            <br>
                                            <p>Status: <span class="highlight"><?php echo htmlspecialchars($suggestion['status'])?></span></p>
                                            <div class="votes">
                                                <div class="upvotes"> Upvotes: <?php echo htmlspecialchars($upvote_count); ?> </div>
                                                <div class="downvotes"> Downvotes: <?php echo htmlspecialchars($downvote_count); ?> </div>
                                            </div>
                                        </div>
                                        <div class="post-footer post-actions">
                                            <?php if ($user_role == 'citizen'): ?>
                                                <?php
                                                $check_vote = $pdo->prepare("SELECT vote_type FROM feedback_votes WHERE suggestion_id = ? AND user_id = ?");
                                                $check_vote->execute([$suggestion['suggestion_id'], $user_id]);
                                                $user_vote = $check_vote->fetch(PDO::FETCH_ASSOC);
                                                ?>
                                                <form action="view_feedback.php" method="post" style="display:inline;">
                                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['suggestion_id']; ?>">
                                                    <button type="submit" name="vote" value="upvote" <?php if ($user_vote && $user_vote['vote_type'] == 'upvote') echo 'disabled'; ?> class="btn-submit short"> <i class="fa fa-thumbs-up"></i> Upvote</button>
                                                </form>
                                                <form action="view_feedback.php" method="post" style="display:inline;">
                                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['suggestion_id']; ?>">
                                                    <button type="submit" name="vote" value="downvote" <?php if ($user_vote && $user_vote['vote_type'] == 'downvote') echo 'disabled'; ?> class="btn-submit-outline short"><i class="fa fa-thumbs-down"></i> Downvote</button>
                                                </form>
                                                <a href="comments.php?suggestion_id=<?php echo $suggestion['suggestion_id']; ?>" class="btn-submit short sub">Comment</a>
                                            <?php endif; ?>

                                            <?php if ($user_role == 'official'): ?>
                                                <a href="respond_feedback.php?suggestion_id=<?php echo $suggestion['suggestion_id']; ?>" class="btn-submit short">Respond to Feedback</a>
                                            <?php endif; ?>

                                            <?php if ($user_role == 'moderator'): ?>
                                                <form action="view_feedback.php" method="post">
                                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['suggestion_id']; ?>">
                                                    <button type="submit" name="action" value="remove" class="btn-submit short">Manage Feedback</button>
                                                    <button type="submit" class="btn-submit short" name="delete_feedback" onclick="return confirm('Are you sure you want to delete this feedback?');"><i class="fa fa-trash"></i> Delete Feedback</button>

                                                    <button type="submit" class="btn-submit short" name="close_feedback" onclick="return confirm('Are you sure you want to close this feedback?');"> <i class="fa fa-close"></i> Close Feedback</button>

                                                    <button type="submit" class="btn-submit short" name="mark_inappropriate"><i class="fa fa-warning"></i> Mark as Inappropriate</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
    