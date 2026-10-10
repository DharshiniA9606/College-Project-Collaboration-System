<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] != "student") {
    header("Location: admin_dashboard.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $deadline = $_POST["deadline"];
    $user_id = $_SESSION["user_id"];

    if (empty($title) || empty($description) || empty($deadline)) {

        $message = "Please fill all fields.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO projects
            (title, description, created_by, deadline)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssis",
            $title,
            $description,
            $user_id,
            $deadline
        );

        if ($stmt->execute()) {

            header("Location: student_dashboard.php");
            exit();

        } else {

            $message = "Project creation failed.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Create Project</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .container {
            width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1f3c88;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            background: #1f3c88;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #162d68;
        }

        .message {
            text-align: center;
            color: red;
            margin-bottom: 15px;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1f3c88;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Create New Project</h1>

    <?php if (!empty($message)) { ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <form method="POST">

        <label>Project Title</label>

        <input
            type="text"
            name="title"
            placeholder="Example: AI-Based Attendance System"
            required
        >


        <label>Project Description</label>

        <textarea
            name="description"
            placeholder="Enter project description"
            required
        ></textarea>


        <label>Project Deadline</label>

        <input
            type="date"
            name="deadline"
            required
        >


        <button type="submit">
            Create Project
        </button>

    </form>


    <a href="student_dashboard.php" class="back">
        ← Back to Dashboard
    </a>

</div>

</body>

</html>