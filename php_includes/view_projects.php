<?php
session_start();
require 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the user role
$user_role = $_SESSION['role'];

// Handle project filtering by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch projects based on the selected status filter along with user details
if ($status_filter === 'all') {
    $stmt = $pdo->query("
        SELECT p.*, u.first_name, u.last_name 
        FROM projects p 
        JOIN users u ON p.created_by = u.user_id
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name 
        FROM projects p 
        JOIN users u ON p.created_by = u.user_id 
        WHERE p.status = ?
    ");
    $stmt->execute([$status_filter]);
}

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Government Projects</h1>
                    <p>Explore ongoing government projects that impact our community. Stay informed and see how you can contribute or provide feedback.</p>
                    <hr>


                    <!-- Filter Form -->
                    <div>
                        <div class="filtering">
                            <p>Filter By Status:</p>
                            <form action="view_projects.php" method="get">
                                <select name="status" id="status" onchange="this.form.submit()">
                                    <option value="all" <?php if ($status_filter === 'all') echo 'selected'; ?>>All</option>
                                    <option value="open" <?php if ($status_filter === 'open') echo 'selected'; ?>>Open</option>
                                    <option value="in progress" <?php if ($status_filter === 'in progress') echo 'selected'; ?>>In Progress</option>
                                    <option value="completed" <?php if ($status_filter === 'completed') echo 'selected'; ?>>Completed</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <div class="feed">
                        <?php if (empty($projects)): ?>
                            <p>No projects available at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <div class="post-item government-post">
                                    <div class="post">
                                        <div class="post-head">
                                            <i class="fa fa-user-circle circle"></i>
                                            <h4><?php echo htmlspecialchars($project['first_name']) . ' ' . htmlspecialchars($project['last_name']); ?></h4>
                                        </div>
                                        <div class="post-body government-post-body">
                                            <div class="government-post-content">
                                                <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                                                <?php
                                                $paragraphs = explode("\n", $project['description']);
                                                foreach ($paragraphs as $paragraph) {
                                                    if (trim($paragraph) !== '') {  // Ignore empty lines
                                                        echo "<p>" . htmlspecialchars($paragraph) . "</p>";
                                                    }
                                                }?>
                                            </div>
                                            <div class="government-post-details">
                                                <h3>Details</h3>
                                                <div class="list">
                                                    <p>Starts <span class="highlight"><?php echo htmlspecialchars($project['start_date']); ?></span></p>
                                                    <p>Expected to End <span class="highlight"><?php echo htmlspecialchars($project['end_date']); ?></span></p>
                                                    <p>Is Currently <span class="highlight"><?php echo htmlspecialchars($project['status']); ?></span></p>
                                                </div>
                                                <div class="government-post-actions">
                                                    <!-- Displayed actions according to user role -->
                                                    <?php if ($user_role == 'citizen'): ?>
                                                        <a href="project_feedback.php?project_id=<?php echo $project['project_id']; ?>" class="btn-submit">Give Feedback</a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user_role == 'official'): ?>
                                                        <a href="project_feedback.php?project_id=<?php echo $project['project_id']; ?>" class="btn-submit">View Feedback</a>
                                                        <a href="update_project.php?project_id=<?php echo $project['project_id']; ?>" class="btn-submit-outline">Update Status</a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user_role == 'moderator'): ?>
                                                        <a href="project_feedback.php?project_id=<?php echo $project['project_id']; ?>" class="btn-submit">View Feedback</a>
                                                        <form action="delete_project.php" method="post" style="display:inline;">
                                                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                                            <button type="submit" onclick="return confirm('Are you sure you want to delete this project?');" class="btn-submit-outline short"><i class="fa fa-trash"></i> Delete</button>
                                                        </form>
                                                        <br>
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
