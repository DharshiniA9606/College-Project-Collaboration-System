<?php
session_start();
require_once "db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Allow faculty only
if ($_SESSION["role"] !== "faculty") {
    header("Location: student_dashboard.php");
    exit();
}

// Get project ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit();
}

$project_id = (int) $_GET["id"];

// Fetch project details
$stmt = $conn->prepare(
    "SELECT p.*, u.name AS student_name
     FROM projects p
     JOIN users u ON p.created_by = u.id
     WHERE p.id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    die("Project not found.");
}

// Fetch team members
$stmt = $conn->prepare(
    "SELECT u.name, u.email, tm.role
     FROM team_members tm
     JOIN users u ON tm.user_id = u.id
     WHERE tm.project_id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();
$team = $stmt->get_result();
$stmt->close();

// Fetch tasks
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
$stmt->close();

// Fetch uploaded files
$stmt = $conn->prepare(
    "SELECT f.*, u.name AS uploader_name
     FROM files f
     JOIN users u ON f.uploaded_by = u.id
     WHERE f.project_id = ?
     ORDER BY f.uploaded_at DESC"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();
$files = $stmt->get_result();
$stmt->close();

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Faculty - Project Details</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            color: #222;
        }

        header {
            background: #172554;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        header h2 {
            margin: 0;
            font-size: 20px;
        }

        a {
            text-decoration: none;
        }

        header a {
            color: white;
            background: #dc2626;
            padding: 10px 14px;
            border-radius: 6px;
        }

        main {
            max-width: 1100px;
            margin: 25px auto;
            padding: 0 20px;
        }

        .back {
            display: inline-block;
            color: #1d4ed8;
            margin-bottom: 20px;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px #0000000d;
        }

        h1 {
            margin-top: 0;
            color: #172554;
        }

        h2 {
            font-size: 20px;
            margin-top: 0;
        }

        .label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .status {
            display: inline-block;
            padding: 7px 10px;
            background: #dbeafe;
            border-radius: 5px;
            font-size: 13px;
        }

        .description {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
        }

        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 11px 16px;
            border-radius: 6px;
            margin: 4px 6px 4px 0;
        }

        .empty {
            color: #64748b;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px;
            }

            .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>College Project Collaboration System</h2>
    <a href="logout.php">Logout</a>
</header>

<main>

    <a class="back" href="admin_dashboard.php">
        &larr; Back to Faculty Dashboard
    </a>

    <section class="panel">
        <h1><?php echo e($project["title"]); ?></h1>

        <p class="label">Project Description</p>
        <div class="description">
            <?php echo e($project["description"] ?: "No description provided."); ?>
        </div>

        <p>
            <strong>Created by:</strong>
            <?php echo e($project["student_name"]); ?>
        </p>

        <p>
            <strong>Deadline:</strong>
            <?php echo e($project["deadline"] ?: "Not set"); ?>
        </p>

        <p>
            <strong>Project Status:</strong>
            <span class="status"><?php echo e($project["status"]); ?></span>
        </p>

        <a href="review_project.php?id=<?php echo $project_id; ?>">
    Review Project
</a>
    </section>

    <section class="panel">
        <h2>Team Members</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>

                <?php if ($team->num_rows > 0): ?>
                    <?php while ($member = $team->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($member["name"]); ?></td>
                            <td><?php echo e($member["email"]); ?></td>
                            <td><?php echo e($member["role"] ?: "Member"); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No team members added.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Project Tasks</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Task</th>
                    <th>Assigned To</th>
                    <th>Deadline</th>
                    <th>Status</th>
                </tr>

                <?php if ($tasks->num_rows > 0): ?>
                    <?php while ($task = $tasks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($task["title"]); ?></td>
                            <td>
                                <?php echo e($task["assigned_name"] ?: "Unassigned"); ?>
                            </td>
                            <td><?php echo e($task["deadline"] ?: "Not set"); ?></td>
                            <td><?php echo e($task["status"]); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No tasks created yet.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Uploaded Project Files</h2>

        <?php if ($files->num_rows > 0): ?>
            <?php while ($file = $files->fetch_assoc()): ?>
                <p>
                    <strong><?php echo e($file["file_name"]); ?></strong>
                    <br>
                    Uploaded by: <?php echo e($file["uploader_name"]); ?>
                    <br>
                    Date: <?php echo e($file["uploaded_at"]); ?>
                    <br>
                    <a href="<?php echo e($file["file_path"]); ?>"
                       target="_blank" rel="noopener noreferrer">
                        Open File
                    </a>
                </p>
                <hr>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">No files uploaded yet.</p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>