
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT name, email, role FROM users WHERE id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User profile not found.");
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #333;
        }

        .container {
            max-width: 650px;
            margin: 30px auto;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #1f3c88;
            margin-top: 0;
        }

        .profile-item {
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-item strong {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #1f3c88;
            text-decoration: none;
        }

        .back:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">

        <a class="back" href="student_dashboard.php">
            &larr; Back to Dashboard
        </a>

        <div class="card">
            <h1>My Profile</h1>

            <div class="profile-item">
                <strong>Name</strong>
                <?php echo e($user["name"]); ?>
            </div>

            <div class="profile-item">
                <strong>Email</strong>
                <?php echo e($user["email"]); ?>
            </div>

            <div class="profile-item">
                <strong>Role</strong>
                <?php echo e(ucfirst($user["role"])); ?>
            </div>
        </div>

    </div>
</body>
</html>
