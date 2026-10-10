
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT id, title
     FROM projects
     WHERE created_by = ?
     ORDER BY id DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Project Teams</title>
    <style>
        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #333;
        }

        .container {
            max-width: 850px;
            margin: 20px auto;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 18px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        h1, h2 {
            color: #1f3c88;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #1f3c88;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #1f3c88;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">

    <a class="back" href="student_dashboard.php">
        &larr; Back to Dashboard
    </a>

    <h1>My Project Teams</h1>
    <p>Select a project to manage its team members.</p>

    <?php if ($projects->num_rows > 0) { ?>

        <?php while ($project = $projects->fetch_assoc()) { ?>
            <div class="card">
                <h2><?php echo e($project["title"]); ?></h2>

                <a class="btn"
                   href="team.php?id=<?php echo (int)$project["id"]; ?>">
                    Manage Team
                </a>
            </div>
        <?php } ?>

    <?php } else { ?>

        <div class="card">
            <p>You haven't created any projects yet.</p>
            <a class="btn" href="create_project.php">Create Project</a>
        </div>

    <?php } ?>

</div>
</body>
</html>
