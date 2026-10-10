<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["project_id"])) {
    die("Project ID is missing.");
}

$project_id = intval($_GET["project_id"]);
$message = "";

// Add new task
if (isset($_POST["add_task"])) {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $assigned_to = !empty($_POST["assigned_to"]) ? intval($_POST["assigned_to"]) : NULL;
    $deadline = !empty($_POST["deadline"]) ? $_POST["deadline"] : NULL;

    if ($title == "") {
        $message = "Task title is required.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO tasks 
            (project_id, title, description, assigned_to, deadline)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issis",
            $project_id,
            $title,
            $description,
            $assigned_to,
            $deadline
        );

        if ($stmt->execute()) {
            $message = "Task added successfully!";
        } else {
            $message = "Failed to add task.";
        }

        $stmt->close();
    }
}


// Update task status
if (isset($_POST["update_status"])) {

    $task_id = intval($_POST["task_id"]);
    $status = $_POST["status"];

    $allowed_status = ["Pending", "In Progress", "Completed"];

    if (in_array($status, $allowed_status)) {

        $stmt = $conn->prepare(
            "UPDATE tasks SET status = ? WHERE id = ? AND project_id = ?"
        );

        $stmt->bind_param(
            "sii",
            $status,
            $task_id,
            $project_id
        );

        $stmt->execute();
        $stmt->close();
    }
}


// Get project name
$stmt = $conn->prepare(
    "SELECT title FROM projects WHERE id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();
$project = $result->fetch_assoc();

$stmt->close();

if (!$project) {
    die("Project not found.");
}


// Get team members
$stmt = $conn->prepare(
    "SELECT u.id, u.name
     FROM team_members tm
     JOIN users u ON tm.user_id = u.id
     WHERE tm.project_id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$team_result = $stmt->get_result();


// Get tasks
$stmt = $conn->prepare(
    "SELECT t.*, u.name AS assigned_name
     FROM tasks t
     LEFT JOIN users u ON t.assigned_to = u.id
     WHERE t.project_id = ?
     ORDER BY t.id DESC"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$tasks_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>

    <title>Project Tasks</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .header {
            background: #1e293b;
            color: white;
            padding: 18px 30px;
        }

        .header h2 {
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        h3 {
            margin-top: 0;
            color: #1e293b;
        }

        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            box-sizing: border-box;
        }

        textarea {
            height: 90px;
            resize: vertical;
        }

        button {
            margin-top: 15px;
            padding: 11px 18px;
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: bold;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-form {
            display: flex;
            gap: 5px;
        }

        .status-form select {
            width: auto;
        }

        .status-form button {
            margin-top: 0;
        }

        @media(max-width: 700px) {

            table {
                font-size: 13px;
            }

            th,
            td {
                padding: 8px;
            }

            .status-form {
                display: block;
            }

        }

    </style>

</head>

<body>

<div class="header">
    <h2>Tasks - <?php echo htmlspecialchars($project["title"]); ?></h2>
</div>

<div class="container">

    <a class="back"
       href="project_details.php?id=<?php echo $project_id; ?>">
        ← Back to Project
    </a>


    <?php if ($message != "") { ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <!-- ADD TASK -->

    <div class="card">

        <h3>➕ Add New Task</h3>

        <form method="POST">

            <label>Task Title</label>

            <input
                type="text"
                name="title"
                placeholder="Example: Design Login Page"
                required
            >


            <label>Description</label>

            <textarea
                name="description"
                placeholder="Enter task description"
            ></textarea>


            <label>Assign To</label>

            <select name="assigned_to">

                <option value="">-- Select Team Member --</option>

                <?php while ($member = $team_result->fetch_assoc()) { ?>

                    <option value="<?php echo $member["id"]; ?>">

                        <?php echo htmlspecialchars($member["name"]); ?>

                    </option>

                <?php } ?>

            </select>


            <label>Deadline</label>

            <input
                type="date"
                name="deadline"
            >


            <button type="submit" name="add_task">
                Add Task
            </button>

        </form>

    </div>


    <!-- TASK LIST -->

    <div class="card">

        <h3>📋 Project Tasks</h3>

        <?php if ($tasks_result->num_rows > 0) { ?>

        <table>

            <tr>
                <th>Task</th>
                <th>Assigned To</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Update</th>
            </tr>


            <?php while ($task = $tasks_result->fetch_assoc()) { ?>

            <tr>

                <td>

                    <strong>
                        <?php echo htmlspecialchars($task["title"]); ?>
                    </strong>

                    <br>

                    <small>
                        <?php echo htmlspecialchars($task["description"]); ?>
                    </small>

                </td>


                <td>

                    <?php

                    echo $task["assigned_name"]
                        ? htmlspecialchars($task["assigned_name"])
                        : "Not Assigned";

                    ?>

                </td>


                <td>

                    <?php

                    echo $task["deadline"]
                        ? htmlspecialchars($task["deadline"])
                        : "No Deadline";

                    ?>

                </td>


                <td>

                    <span class="status
                        <?php

                        if ($task["status"] == "Pending") {
                            echo "pending";
                        } elseif ($task["status"] == "In Progress") {
                            echo "progress";
                        } else {
                            echo "completed";
                        }

                        ?>">

                        <?php echo htmlspecialchars($task["status"]); ?>

                    </span>

                </td>


                <td>

                    <form method="POST" class="status-form">

                        <input
                            type="hidden"
                            name="task_id"
                            value="<?php echo $task["id"]; ?>"
                        >

                        <select name="status">

                            <option
                                value="Pending"
                                <?php
                                if ($task["status"] == "Pending") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Pending
                            </option>

                            <option
                                value="In Progress"
                                <?php
                                if ($task["status"] == "In Progress") {
                                    echo "selected";
                                }
                                ?>
                            >
                                In Progress
                            </option>

                            <option
                                value="Completed"
                                <?php
                                if ($task["status"] == "Completed") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Completed
                            </option>

                        </select>

                        <button
                            type="submit"
                            name="update_status"
                        >
                            Update
                        </button>

                    </form>

                </td>

            </tr>

            <?php } ?>

        </table>

        <?php } else { ?>

            <p>No tasks have been created yet.</p>

        <?php } ?>

    </div>

</div>

</body>
</html>