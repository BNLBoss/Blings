<?php
session_start();
require 'db.php';
include 'functions.php';

// Get the suggestion ID from the URL
$suggestion_id = $_GET['suggestion_id'];

// Fetch the feedback details along with the user's first name and last name
$stmt = $pdo->prepare("
    SELECT fs.*, u.first_name, u.last_name 
    FROM feedback_suggestions fs
    JOIN users u ON fs.user_id = u.user_id
    WHERE fs.suggestion_id = ?
");
$stmt->execute([$suggestion_id]);
$suggestion = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all comments with user information
$stmt = $pdo->prepare("
    SELECT comments.comment_text, comments.comment_date, users.first_name, users.last_name
    FROM comments
    JOIN users ON comments.user_id = users.user_id
    WHERE comments.suggestion_id = ?
");
$stmt->execute([$suggestion_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_text = $_POST['comment_text'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO comments (suggestion_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([$suggestion_id, $user_id, $comment_text]);

    // Redirect to avoid resubmitting the form
    header("Location: comments.php?suggestion_id=$suggestion_id");
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
            <div class="panel-section feedback">
                <div class="user-feed">
                        <div class="post">
                            <div class="post-head">
                                <i class="fa fa-user-circle circle"></i>
                                <h4><?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?> | <small class="highlight"><?php echo timeAgo($suggestion['submission_date']); ?></small></h4>
                            </div>
                        </div>
                        <h1><?php echo htmlspecialchars($suggestion['title']); ?></h1>
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
                        <hr>
                        <br>
                        <br>
                        <!-- Comment form -->
                        <div class="add-comment">
                            <form action="comments.php?suggestion_id=<?php echo $suggestion_id; ?>" method="post">
                                <div class="flex">
                                    <textarea name="comment_text" rows="3" placeholder="Add a comment" required></textarea>
                                    <p><input type="submit" value="Comment" class="btn-submit" style="width: fit-content;"></p>
                                </div>
                            </form>
                        </div>

                        <!-- Displaying comments -->
                        <div class="interactions">
                            <h3>Comments</h3>
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment">
                                        <div class="post-by">
                                            <i class="fa fa-user-circle circle"></i>
                                            <h4><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?><br><small class="highlight"><?php echo timeAgo($comment['comment_date'])?></small></h4>
                                        </div>
                                        <div class="bubble">
                                            <?php echo htmlspecialchars($comment['comment_text']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</body>
</html>