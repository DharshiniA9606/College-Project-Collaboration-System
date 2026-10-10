<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== "student") {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$sql = "SELECT
            t.id,
            t.title,
            t.description,
            t.deadline,
            t.status,
            p.title AS project_title,
            p.id AS project_id
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE t.assigned_to = ?
        ORDER BY t.deadline ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks</title>

    <style>
        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            color: #222;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #0000000d;
            overflow-x: auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .empty {
            color: #64748b;
        }
    </style>
</head>

<body>
<div class="container">

    <a class="back" href="student_dashboard.php">
        &larr; Back to Dashboard
    </a>

    <div class="panel">
        <h1>My Tasks</h1>
        <p>View tasks assigned to you across your projects.</p>

        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($tasks->num_rows > 0): ?>
                    <?php while ($task = $tasks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($task["title"]); ?></td>
                            <td><?php echo e($task["project_title"]); ?></td>
                            <td><?php echo e($task["deadline"] ?: "Not set"); ?></td>
                            <td><?php echo e($task["status"]); ?></td>
                            <td>
                                <a class="btn"
                                   href="tasks.php?project_id=<?php echo (int)$task["project_id"]; ?>">
                                    Open Project Tasks
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">
                            No tasks have been assigned to you yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>