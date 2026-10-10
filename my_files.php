
<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT id, title FROM projects
        WHERE created_by = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Project Files</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }
        .container {
            max-width: 700px;
            margin: auto;
        }
        .project {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
        }
        a {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .back {
            background: #555;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Upload Project Files</h1>
    <p>Select a project to upload or view its files.</p>

    <?php if ($projects->num_rows > 0) { ?>
        <?php while ($project = $projects->fetch_assoc()) { ?>
            <div class="project">
                <h3>
                    <?php echo htmlspecialchars($project["title"]); ?>
                </h3>
                <a href="upload_file.php?project_id=<?php
                    echo $project["id"];
                ?>">Open Upload Page</a>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p>You haven't created any projects yet.</p>
        <a href="create_project.php">Create a Project</a>
    <?php } ?>

    <p>
        <a class="back" href="student_dashboard.php">
            Back to Dashboard
        </a>
    </p>
</div>
</body>
</html>
