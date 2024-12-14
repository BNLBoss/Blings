<?php
session_start();
require 'db.php';
include 'functions.php';

// Check if the project ID is provided in the URL
if (!isset($_GET['project_id'])) {
    $error_msg = "No Project Selected";
    $redirect_link = "view_projects.php";
    header("Location: error.php?msg=$error_msg&redirect=$redirect_link");
    exit();
}

$project_id = $_GET['project_id'];

// Fetch project details
$stmt = $pdo->prepare("SELECT * FROM projects JOIN users ON projects.created_by = users.user_id WHERE project_id = ?");

$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the user role (assuming session stores this information)
$role = $_SESSION['role'];

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['feedback'])) {
    $user_id = $_SESSION['user_id'];
    $feedback = $_POST['feedback'];

    // Insert feedback into the database
    $stmt = $pdo->prepare("INSERT INTO project_feedback (project_id, user_id, feedback) VALUES (?, ?, ?)");
    $stmt->execute([$project_id, $user_id, $feedback]);

    echo "Feedback submitted successfully!";
    // Redirect to avoid form resubmission
    header("Location: project_feedback.php?project_id=$project_id");
    exit;
}

// Fetch feedback for this project
$stmt = $pdo->prepare("SELECT feedback, users.first_name, users.last_name, submission_date FROM project_feedback JOIN users ON project_feedback.user_id = users.user_id WHERE project_feedback.project_id = ?");
$stmt->execute([$project_id]);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <div class="panel-section projects">
                <div class="post">
                    <div class="post-head">
                        <i class="fa fa-user-circle circle"></i>
                        <h4><?php echo htmlspecialchars($project['first_name']) . ' ' . htmlspecialchars($project['last_name']); ?></h4>
                    </div>
                </div>    
                <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                <?php
                echo nl2br(htmlspecialchars($project['description']));
                ?>
                <br>
                <br>
                <h3>Details</h3>
                <p>Starts <span class="highlight"><?php echo htmlspecialchars($project['start_date']); ?></span></p>
                <p>Expected to End <span class="highlight"><?php echo htmlspecialchars($project['end_date']); ?></span></p>
                <p>Is Currently <span class="highlight"><?php echo htmlspecialchars($project['status']); ?></span></p>
                <hr>
                <br>
                <br>
                <div class="add-comment">
                    <!-- Feedback form for citizens -->
                    <?php if ($role == 'citizen'): ?>
                        <form id="feedback-form" action="project_feedback.php?project_id=<?php echo $project_id; ?>" method="post">
                            <div class="flex">
                                <textarea name="feedback" id="feedback-textarea" rows="3" placeholder="Add a comment" required></textarea>
                                <p><input type="submit" value="Comment" class="btn-submit" style="width: fit-content;"></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="interactions">
                    <h3>Comments</h3>
                    <?php if (!empty($feedbacks)): ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="comment">
                                <div class="post-by">
                                    <i class="fa fa-user-circle circle"></i>
                                    <h4><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?><br><small class="highlight"><?php echo timeAgo($feedback['submission_date'])?></small></h4>
                                </div>
                                <div class="bubble">
                                <?php
                                    $comment_paragraphs = explode("\n", $feedback['feedback']);
                                    foreach ($comment_paragraphs as $cp) {
                                        if (trim($cp) !== '') {  // Ignore empty lines
                                            echo "<p>" . htmlspecialchars($cp) . "</p>";
                                        }
                                    }?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No comments yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>