<?php
session_start();
require_once "db.php";

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Only faculty can access this page
if ($_SESSION["role"] !== "faculty") {
    header("Location: student_dashboard.php");
    exit();
}

// Get project summary counts
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM projects");
$totalProjects = $totalResult->fetch_assoc()["total"];

$approvedResult = $conn->query(
    "SELECT COUNT(*) AS total FROM projects WHERE status = 'Approved'"
);
$approvedProjects = $approvedResult->fetch_assoc()["total"];

$changesResult = $conn->query(
    "SELECT COUNT(*) AS total FROM projects WHERE status = 'Needs Changes'"
);
$changesProjects = $changesResult->fetch_assoc()["total"];

// Get all projects and the names of their creators
$sql = "SELECT projects.id, projects.title, projects.deadline,
               projects.status, users.name AS student_name
        FROM projects
        JOIN users ON projects.created_by = users.id
        ORDER BY projects.id DESC";

$projects = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>

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
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header h2 {
            margin: 0;
        }

        header a {
            color: white;
            text-decoration: none;
            background: #dc2626;
            padding: 10px 16px;
            border-radius: 6px;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card, .table-box {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #0000000d;
        }

        .card p {
            color: #64748b;
            margin-bottom: 8px;
        }

        .card h2 {
            margin: 0;
            color: #172554;
            font-size: 30px;
        }

        .table-box {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            min-width: 650px;
        }

        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 5px;
            font-size: 12px;
            background: #e2e8f0;
        }

        .view-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
        }

        @media (max-width: 650px) {
            .cards {
                grid-template-columns: 1fr;
            }

            header {
                padding: 18px;
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
    <div class="welcome">
        <h1>Faculty Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</p>
        <p>Monitor student projects and review their progress.</p>
    </div>

    <div class="cards">
        <div class="card">
            <p>Total Projects</p>
            <h2><?php echo $totalProjects; ?></h2>
        </div>

        <div class="card">
            <p>Approved Projects</p>
            <h2><?php echo $approvedProjects; ?></h2>
        </div>

        <div class="card">
            <p>Projects Needing Changes</p>
            <h2><?php echo $changesProjects; ?></h2>
        </div>
    </div>

    <div class="table-box">
        <h2>All Student Projects</h2>

        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Created By</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($projects && $projects->num_rows > 0): ?>
                    <?php while ($project = $projects->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($project["title"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($project["student_name"]); ?>
                            </td>

                            <td>
                                <?php
                                echo !empty($project["deadline"])
                                    ? htmlspecialchars($project["deadline"])
                                    : "Not set";
                                ?>
                            </td>

                            <td>
                                <span class="status">
                                    <?php echo htmlspecialchars($project["status"]); ?>
                                </span>
                            </td>

                            <td>
                                <a class="view-btn"
                                   href="admin_project_view.php?id=<?php echo (int)$project["id"]; ?>">
                                    View Project
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No student projects found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>