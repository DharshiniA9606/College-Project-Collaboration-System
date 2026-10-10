
<?php
session_start();
require_once "db.php";

/* Check login */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

/* Allow students only */
if ($_SESSION["role"] != "student") {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$user_name = $_SESSION["name"];

/* Count projects */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM projects
     WHERE created_by = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$project_count = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

/* Count team members */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM team_members tm
     INNER JOIN projects p ON tm.project_id = p.id
     WHERE p.created_by = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$team_count = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

/* Count pending tasks */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM tasks t
     INNER JOIN projects p ON t.project_id = p.id
     WHERE p.created_by = ?
     AND t.status = 'Pending'"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_tasks = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

/* Count completed tasks */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM tasks t
     INNER JOIN projects p ON t.project_id = p.id
     WHERE p.created_by = ?
     AND t.status = 'Completed'"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_tasks = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

/* Get projects and task progress */
$stmt = $conn->prepare(
    "SELECT p.id, p.title, p.description, p.deadline, p.status,
        (SELECT COUNT(*)
         FROM tasks t
         WHERE t.project_id = p.id) AS total_tasks,
        (SELECT COUNT(*)
         FROM tasks t
         WHERE t.project_id = p.id
         AND t.status = 'Completed') AS completed_tasks
     FROM projects p
     WHERE p.created_by = ?
     ORDER BY p.id DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #333;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #1f3c88;
            color: white;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 13px 15px;
            margin: 6px 0;
            border-radius: 6px;
        }

        .sidebar a:hover {
            background: #162d68;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .topbar h1 {
            margin: 0 0 5px;
            color: #1f3c88;
        }

        .topbar p {
            margin: 0;
            color: #777;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card h3 {
            margin: 0;
            color: #777;
            font-size: 15px;
        }

        .card .number {
            font-size: 32px;
            font-weight: bold;
            color: #1f3c88;
            margin-top: 10px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section h2 {
            margin-top: 0;
            color: #1f3c88;
        }

        .project {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            background: #fff;
        }

        .project h3 {
            margin-top: 0;
            color: #1f3c88;
        }

        .project p {
            color: #666;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #fff3cd;
            color: #856404;
            font-size: 13px;
        }

        /* Project progress bar */
        .progress-container {
            margin: 18px 0;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #1f3c88;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .task-summary {
            font-size: 13px;
            margin-bottom: 0;
        }

        .btn {
            display: inline-block;
            background: #1f3c88;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }

        .btn:hover {
            background: #162d68;
        }

        .empty {
            color: #777;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>College Portal</h2>

    <a href="student_dashboard.php">Dashboard</a>
    <a href="create_project.php">Create Project</a>
    <a href="my_team.php">Team Members</a>
    <a href="my_tasks.php">Tasks</a>
    <a href="my_files.php">Upload Files</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">

    <!-- TOP BAR -->
    <div class="topbar">
        <h1>
            Welcome, <?php echo htmlspecialchars($user_name); ?> 👋
        </h1>

        <p>Manage your academic projects and team activities.</p>
    </div>

    <!-- DASHBOARD SUMMARY CARDS -->
    <div class="cards">

        <div class="card">
            <h3>Projects</h3>
            <div class="number">
                <?php echo (int) $project_count; ?>
            </div>
        </div>

        <div class="card">
            <h3>Team Members</h3>
            <div class="number">
                <?php echo (int) $team_count; ?>
            </div>
        </div>

        <div class="card">
            <h3>Pending Tasks</h3>
            <div class="number">
                <?php echo (int) $pending_tasks; ?>
            </div>
        </div>

        <div class="card">
            <h3>Completed Tasks</h3>
            <div class="number">
                <?php echo (int) $completed_tasks; ?>
            </div>
        </div>

    </div>

    <!-- PROJECT SECTION -->
    <div class="section">
        <h2>My Projects</h2>

        <?php if ($projects->num_rows == 0) { ?>

            <div class="empty">
                <p>You haven't created any projects yet.</p>

                <a href="create_project.php" class="btn">
                    Create Your First Project
                </a>
            </div>

        <?php } else { ?>

            <?php while ($project = $projects->fetch_assoc()) { ?>

                <?php
                /* Calculate progress for this project */
                $total = (int) $project["total_tasks"];
                $completed = (int) $project["completed_tasks"];

                $progress = ($total > 0)
                    ? (int) round(($completed / $total) * 100)
                    : 0;
                ?>

                <div class="project">

                    <h3>
                        <?php echo htmlspecialchars($project["title"]); ?>
                    </h3>

                    <p>
                        <?php echo nl2br(htmlspecialchars($project["description"])); ?>
                    </p>

                    <p>
                        <strong>Deadline:</strong>
                        <?php echo htmlspecialchars($project["deadline"]); ?>
                    </p>

                    <p>
                        <strong>Faculty Review Status:</strong>
                        <span class="status">
                            <?php echo htmlspecialchars($project["status"]); ?>
                        </span>
                    </p>

                    <!-- PROJECT PROGRESS BAR -->
                    <div class="progress-container">

                        <div class="progress-info">
                            <strong>Project Progress</strong>
                            <strong><?php echo $progress; ?>%</strong>
                        </div>

                        <div
                            class="progress-bar"
                            role="progressbar"
                            aria-label="Project task completion"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="<?php echo $progress; ?>"
                        >
                            <div
                                class="progress-fill"
                                style="width: <?php echo $progress; ?>%;"
                            ></div>
                        </div>

                        <p class="task-summary">
                            <?php echo $completed; ?> of
                            <?php echo $total; ?> tasks completed
                        </p>

                    </div>

                    <a
                        href="project_details.php?id=<?php echo (int) $project["id"]; ?>"
                        class="btn"
                    >
                        View Project
                    </a>

                </div>

            <?php } ?>

        <?php } ?>

    </div>

</div>

</body>
</html>
