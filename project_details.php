<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: student_dashboard.php");
    exit();
}

$project_id = intval($_GET["id"]);
$user_id = $_SESSION["user_id"];


/* Get project */

$stmt = $conn->prepare(
    "SELECT p.*, u.name AS creator
     FROM projects p
     INNER JOIN users u ON p.created_by = u.id
     WHERE p.id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Project not found.");
}

$project = $result->fetch_assoc();

$stmt->close();


/* Get team members */

$stmt = $conn->prepare(
    "SELECT tm.id, tm.role, u.name, u.email
     FROM team_members tm
     INNER JOIN users u ON tm.user_id = u.id
     WHERE tm.project_id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$team_members = $stmt->get_result();


/* Get tasks */

$stmt = $conn->prepare(
    "SELECT t.*, u.name AS assigned_name
     FROM tasks t
     LEFT JOIN users u ON t.assigned_to = u.id
     WHERE t.project_id = ?
     ORDER BY t.id DESC"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$tasks = $stmt->get_result();


/* Calculate progress */

$total_tasks = 0;
$completed_tasks = 0;

$task_data = [];

while ($task = $tasks->fetch_assoc()) {

    $task_data[] = $task;

    $total_tasks++;

    if ($task["status"] == "Completed") {
        $completed_tasks++;
    }
}

if ($total_tasks > 0) {
    $progress = round(($completed_tasks / $total_tasks) * 100);
} else {
    $progress = 0;
}


/* Get files */

$stmt = $conn->prepare(
    "SELECT f.*, u.name AS uploader
     FROM files f
     INNER JOIN users u ON f.uploaded_by = u.id
     WHERE f.project_id = ?
     ORDER BY f.id DESC"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$files = $stmt->get_result();

/* Get faculty feedback */
$stmt = $conn->prepare(
    "SELECT r.comment, r.status, r.reviewed_at, u.name AS faculty_name
     FROM reviews r
     INNER JOIN users u ON r.faculty_id = u.id
     WHERE r.project_id = ?
     ORDER BY r.reviewed_at DESC"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();
$reviews = $stmt->get_result();

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>Project Details</title>

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
            width: 230px;
            height: 100vh;
            background: #1f3c88;
            color: white;
            padding: 25px 15px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 5px 0;
            border-radius: 5px;
        }

        .sidebar a:hover {
            background: #162d68;
        }

        .main {
            margin-left: 230px;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #1f3c88;
            margin-top: 0;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            background: #fff3cd;
            color: #856404;
            border-radius: 20px;
            font-size: 13px;
        }

        .progress-container {
            margin-top: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e5e5;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: #1f3c88;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .section h2 {
            color: #1f3c88;
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f4f7fb;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .progress-status {
            background: #cfe2ff;
            color: #084298;
        }

        .completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .btn {
            display: inline-block;
            background: #1f3c88;
            color: white;
            padding: 9px 14px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .btn:hover {
            background: #162d68;
        }

        .file-link {
            color: #1f3c88;
        }

    </style>

</head>

<body>


<div class="sidebar">

    <h2>College Portal</h2>

    <a href="student_dashboard.php">
        Dashboard
    </a>

    <a href="create_project.php">
        Create Project
    </a>

    <a href="team.php?id=<?php echo $project_id; ?>">
        Team Members
    </a>

    <a href="tasks.php?project_id=<?php echo $project_id; ?>">
    Tasks
</a>

    <a href="upload_file.php?project_id=<?php echo $project_id; ?>">
    Upload Files
</a>

    <a href="logout.php">
        Logout
    </a>

</div>


<div class="main">


    <!-- PROJECT INFORMATION -->

    <div class="header">

        <h1>
            <?php echo htmlspecialchars($project["title"]); ?>
        </h1>

        <p>
            <?php echo htmlspecialchars($project["description"]); ?>
        </p>

        <p>
            <strong>Created By:</strong>
            <?php echo htmlspecialchars($project["creator"]); ?>
        </p>

        <p>
            <strong>Deadline:</strong>
            <?php echo htmlspecialchars($project["deadline"]); ?>
        </p>

        <span class="status">
            <?php echo htmlspecialchars($project["status"]); ?>
        </span>


        <div class="progress-container">

            <strong>Project Progress: <?php echo $progress; ?>%</strong>

            <div class="progress-bar">

                <div
                    class="progress"
                    style="width: <?php echo $progress; ?>%;"
                ></div>

            </div>

        </div>

    </div>


    <!-- TEAM MEMBERS -->

    <div class="section">

        <h2>Team Members</h2>

        <a
            href="team.php?id=<?php echo $project_id; ?>"
            class="btn"
        >
            Manage Team
        </a>


        <?php if ($team_members->num_rows > 0) { ?>

            <table>

                <tr>

                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>

                </tr>


                <?php while ($member = $team_members->fetch_assoc()) { ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($member["name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($member["email"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($member["role"]); ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php } else { ?>

            <p>No team members added yet.</p>

        <?php } ?>

    </div>


    <!-- TASKS -->

    <div class="section">

        <h2>Tasks</h2>

       <a href="tasks.php?project_id=<?php echo $project_id; ?>">
    Tasks
</a>


        <?php if (count($task_data) > 0) { ?>

            <table>

                <tr>

                    <th>Task</th>
                    <th>Assigned To</th>
                    <th>Deadline</th>
                    <th>Status</th>

                </tr>


                <?php foreach ($task_data as $task) { ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($task["title"]); ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $task["assigned_name"] ?? "Not Assigned"
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($task["deadline"]); ?>
                        </td>

                        <td>

                            <?php if ($task["status"] == "Completed") { ?>

                                <span class="badge completed">
                                    Completed
                                </span>

                            <?php } elseif ($task["status"] == "In Progress") { ?>

                                <span class="badge progress-status">
                                    In Progress
                                </span>

                            <?php } else { ?>

                                <span class="badge pending">
                                    Pending
                                </span>

                            <?php } ?>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php } else { ?>

            <p>No tasks created yet.</p>

        <?php } ?>

    </div>


    <!-- FILES -->

    <div class="section">

        <h2>Project Files</h2>

        <a
    href="upload_file.php?project_id=<?php echo $project_id; ?>"
    class="btn"
>
    Upload File
</a>

        <?php if ($files->num_rows > 0) { ?>

            <table>

                <tr>

                    <th>File Name</th>
                    <th>Uploaded By</th>
                    <th>Date</th>

                </tr>


                <?php while ($file = $files->fetch_assoc()) { ?>

                    <tr>

                        <td>

                            <a
                                class="file-link"
                                href="<?php echo htmlspecialchars($file["file_path"]); ?>"
                                target="_blank"
                            >
                                <?php echo htmlspecialchars($file["file_name"]); ?>
                            </a>

                        </td>

                        <td>
                            <?php echo htmlspecialchars($file["uploader"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($file["uploaded_at"]); ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php } else { ?>

            <p>No files uploaded yet.</p>

        <?php } ?>

        <!-- FACULTY FEEDBACK -->

    <div class="section">

        <h2>Faculty Feedback & Review Status</h2>

        <?php if ($reviews->num_rows > 0) { ?>

            <?php while ($review = $reviews->fetch_assoc()) { ?>

                <div style="border-bottom: 1px solid #ddd; padding: 15px 0;">

                    <p>
                        <strong>Faculty Name:</strong>
                        <?php echo htmlspecialchars($review["faculty_name"]); ?>
                    </p>

                    <p>
                        <strong>Review Status:</strong>
                        <span class="status">
                            <?php echo htmlspecialchars($review["status"]); ?>
                        </span>
                    </p>

                    <p>
                        <strong>Faculty Comments:</strong>
                        <?php
                        if (!empty($review["comment"])) {
                            echo nl2br(htmlspecialchars($review["comment"]));
                        } else {
                            echo "No comments provided.";
                        }
                        ?>
                    </p>

                    <p>
                        <strong>Review Date:</strong>
                        <?php echo htmlspecialchars($review["reviewed_at"]); ?>
                    </p>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>No faculty feedback available yet. Your project is awaiting review.</p>

        <?php } ?>

    </div>