<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$project_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($project_id == 0) {
    header("Location: student_dashboard.php");
    exit();
}

$message = "";


/* Add team member */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = intval($_POST["user_id"]);
    $role = trim($_POST["role"]);

    if ($user_id > 0 && !empty($role)) {

        $check = $conn->prepare(
            "SELECT id
             FROM team_members
             WHERE project_id = ?
             AND user_id = ?"
        );

        $check->bind_param("ii", $project_id, $user_id);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "This student is already a team member.";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO team_members
                (project_id, user_id, role)
                VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "iis",
                $project_id,
                $user_id,
                $role
            );

            if ($stmt->execute()) {
                $message = "Team member added successfully.";
            } else {
                $message = "Failed to add team member.";
            }

            $stmt->close();
        }

        $check->close();
    }
}


/* Remove team member */

if (isset($_GET["remove"])) {

    $remove_id = intval($_GET["remove"]);

    $stmt = $conn->prepare(
        "DELETE FROM team_members
         WHERE id = ?
         AND project_id = ?"
    );

    $stmt->bind_param("ii", $remove_id, $project_id);

    $stmt->execute();

    $stmt->close();

    header("Location: team.php?id=" . $project_id);
    exit();
}


/* Get all students */

$students = $conn->query(
    "SELECT id, name, email
     FROM users
     WHERE role = 'student'
     ORDER BY name"
);


/* Get current team */

$stmt = $conn->prepare(
    "SELECT tm.id, tm.role, u.name, u.email
     FROM team_members tm
     INNER JOIN users u ON tm.user_id = u.id
     WHERE tm.project_id = ?"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$team = $stmt->get_result();

?>

<!DOCTYPE html>

<html>

<head>

    <title>Team Members</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .container {
            width: 900px;
            max-width: 95%;
            margin: 40px auto;
        }

        .box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        h1,
        h2 {
            color: #1f3c88;
        }

        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select,
        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            margin-top: 15px;
            padding: 11px 20px;
            background: #1f3c88;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #162d68;
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

        .message {
            padding: 10px;
            background: #e7f3e7;
            color: #27632a;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .remove {
            color: #d33;
            text-decoration: none;
        }

        .back {
            color: #1f3c88;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Team Members</h1>

    <a
        href="project_details.php?id=<?php echo $project_id; ?>"
        class="back"
    >
        ← Back to Project
    </a>


    <div class="box">

        <h2>Add Team Member</h2>

        <?php if (!empty($message)) { ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>


        <form method="POST">

            <label>Select Student</label>

            <select name="user_id" required>

                <option value="">
                    -- Select Student --
                </option>

                <?php while ($student = $students->fetch_assoc()) { ?>

                    <option value="<?php echo $student["id"]; ?>">

                        <?php
                        echo htmlspecialchars(
                            $student["name"] . " - " . $student["email"]
                        );
                        ?>

                    </option>

                <?php } ?>

            </select>


            <label>Team Role</label>

            <input
                type="text"
                name="role"
                placeholder="Example: Backend Developer"
                required
            >


            <button type="submit">
                Add Team Member
            </button>

        </form>

    </div>


    <div class="box">

        <h2>Current Team</h2>


        <?php if ($team->num_rows > 0) { ?>

            <table>

                <tr>

                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>

                </tr>


                <?php while ($member = $team->fetch_assoc()) { ?>

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

                        <td>

                            <a
                                class="remove"
                                href="team.php?id=<?php echo $project_id; ?>&remove=<?php echo $member["id"]; ?>"
                                onclick="return confirm('Remove this team member?');"
                            >
                                Remove
                            </a>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php } else { ?>

            <p>No team members added yet.</p>

        <?php } ?>

    </div>

</div>

</body>

</html>