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

// Fetch upcoming town hall meetings
$stmt = $pdo->query("
    SELECT th.*, u.first_name, u.last_name 
    FROM town_hall_meetings th 
    JOIN users u ON th.created_by = u.user_id 
    WHERE th.meeting_date >= NOW()
");

$townhalls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form actions (registration or deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register']) && $user_role == 'citizen') {
        $meeting_id = $_POST['meeting_id'];
        
        // Insert registration into the database
        $stmt = $pdo->prepare("INSERT INTO meeting_registrations (meeting_id, user_id) VALUES (?, ?)");
        $stmt->execute([$meeting_id, $user_id]);

        // Redirect to avoid form re-submission
        header("Location: view_townhalls.php?registered=success");
        exit;
    }

    if (isset($_POST['delete_meeting']) && $user_role == 'moderator') {
        $meeting_id = $_POST['meeting_id'];
        
        // Delete the meeting from the database
        $stmt = $pdo->prepare("DELETE FROM town_hall_meetings WHERE meeting_id = ?");
        $stmt->execute([$meeting_id]);

        // Redirect to avoid form re-submission
        header("Location: view_townhalls.php?deleted=success");
        exit;
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
                <div class="panel-section projects">
                    <h1>Upcoming Town Hall Meetings</h1>
                    <p>Upcoming town hall meetings provide a valuable opportunity for community members to engage directly with local leaders, discuss important issues, and voice concerns or suggestions. These meetings are typically held by government officials, city councils, or other local authorities to foster transparency, encourage public participation, and gather feedback on current policies or upcoming initiatives.</p>
                    <hr>

                    <div class="feed">
                        <?php if (empty($townhalls)): ?>
                            <p>No town hall meetings have been scheduled yet.</p>
                        <?php else: ?>
                            <?php foreach ($townhalls as $townhall): ?>
                                <div class="post-item government-post">
                                    <div class="post">
                                        <div class="post-head">
                                            <i class="fa fa-user-circle circle"></i>
                                            <h4><?php echo htmlspecialchars($townhall['first_name']) . ' ' . htmlspecialchars($townhall['last_name']); ?></h4>
                                        </div>
                                        <div class="post-body government-post-body">
                                            <div class="government-post-content">
                                                <h2><?php echo htmlspecialchars($townhall['title']); ?></h2>
                                                <p><?php echo htmlspecialchars($townhall['description']); ?></p>
                                            </div>
                                            <div class="government-post-details">
                                                <h3>Meeting Details</h3>
                                                <div class="list">
                                                    <p>Meeting Date: <span class="highlight"><?php echo htmlspecialchars($townhall['meeting_date']); ?></span></p>
                                                    <p>Meeting links: <a href="<?php echo htmlspecialchars($townhall['location_url']); ?>" target="_blank"><?php echo htmlspecialchars($townhall['location_url']); ?></a></p>
                                                </div>

                                                <div class="government-post-actions">
                                                    <?php if ($user_role == 'citizen'): ?>
                                                        <!-- Registration form for citizens -->
                                                        <form action="view_townhalls.php" method="post" style="display:inline;">
                                                            <input type="hidden" name="meeting_id" value="<?php echo $townhall['meeting_id']; ?>">
                                                            <?php if (!isset($_GET['registered'])): ?>
                                                            <button type="submit" name="register" class="btn-submit">Register for Meeting</button>
                                                            <?php else: echo "<p> You have registed for this meetng </p>"; ?>
                                                            <?php endif ?>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if ($user_role == 'moderator'): ?>
                                                        <!-- Delete button for moderators -->
                                                        <form action="view_townhalls.php" method="post" style="display:inline;">
                                                            <input type="hidden" name="meeting_id" value="<?php echo $townhall['meeting_id']; ?>">
                                                            <button type="submit" name="delete_meeting" class="btn-submit-outline short" onclick="return confirm('Are you sure you want to delete this meeting?');">
                                                                <i class="fa fa-trash"></i> Delete Meeting
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
