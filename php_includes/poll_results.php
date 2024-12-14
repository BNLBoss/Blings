<?php
session_start();
require 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user role
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get poll_id from the URL
if (!isset($_GET['poll_id'])) {
    $error_msg = "No Poll Selected";
    $redirect_link = "view_polls.php";
    header("Location: error.php?msg=$error_msg&redirect=$redirect_link");
    exit();
}
$poll_id = $_GET['poll_id'];

// Fetch poll details
$stmt = $pdo->prepare("SELECT * FROM polls WHERE poll_id = ?");
$stmt->execute([$poll_id]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch voting results
$votes_stmt = $pdo->prepare("
    SELECT vote_option, COUNT(*) as vote_count
    FROM votes
    WHERE poll_id = ?
    GROUP BY vote_option
");
$votes_stmt->execute([$poll_id]);
$results = $votes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total votes
$total_votes = array_sum(array_column($results, 'vote_count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poll Results</title>
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
                <div class="panel-section polls">
                    <h1>Poll Results: <?php echo htmlspecialchars($poll['title']); ?></h1>
                    <p><?php echo htmlspecialchars($poll['description']); ?></p>
                    <p>Poll closed on: <span class="highlight"><?php echo date('d F Y', strtotime($poll['closing_date'])); ?></span></p>
                    <hr>
                    <div class="results-section">
                        <h2>Results:</h2>
                        <?php if ($total_votes > 0): ?>
                            <?php foreach ($results as $result): ?>
                                <div class="result-item">
                                    <p>
                                        <strong><?php echo htmlspecialchars(ucfirst($result['vote_option'])); ?>:</strong> 
                                        <?php echo htmlspecialchars($result['vote_count']); ?> votes 
                                        (<?php echo round(($result['vote_count'] / $total_votes) * 100, 2); ?>%)
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            <p><strong>Total Votes:</strong> <?php echo $total_votes; ?></p>
                        <?php else: ?>
                            <p>No votes were cast for this poll.</p>
                        <?php endif; ?>
                        <a href="finished_polls.php" class="btn-submit">Back to Completed Polls</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
