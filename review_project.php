
<?php
session_start();
require_once "db.php";

// Allow only faculty
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Get project ID from URL
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Project ID is missing. Please open this page from the faculty project view.");
}

$project_id = (int) $_GET["id"];
$message = "";

// Process faculty review
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $comment = trim($_POST["comment"] ?? "");
    $status = $_POST["status"] ?? "";

    $allowed_statuses = ["Approved", "Needs Changes"];

    if (!in_array($status, $allowed_statuses, true)) {
        $message = "Please select a valid review status.";
    } elseif ($comment === "") {
        $message = "Please enter your feedback.";
    } else {
        // Update project status
        $stmt = $conn->prepare(
            "UPDATE projects SET status = ? WHERE id = ?"
        );
        $stmt->bind_param("si", $status, $project_id);

        if ($stmt->execute()) {
            // Save the review comment
            $faculty_id = (int) $_SESSION["user_id"];

            $review_stmt = $conn->prepare(
                "INSERT INTO reviews
                (project_id, faculty_id, comment, status, reviewed_at)
                VALUES (?, ?, ?, ?, NOW())"
            );

            $review_stmt->bind_param(
                "iiss",
                $project_id,
                $faculty_id,
                $comment,
                $status
            );

            if ($review_stmt->execute()) {
                $message = "Review submitted successfully!";
            } else {
                $message = "Project status updated, but the review could not be saved.";
            }

            $review_stmt->close();
        } else {
            $message = "Unable to update the project status.";
        }

        $stmt->close();
    }
}

// Fetch project details
$stmt = $conn->prepare(
    "SELECT p.title, p.description, p.deadline, p.status,
            u.name AS creator_name
     FROM projects p
     JOIN users u ON p.created_by = u.id
     WHERE p.id = ?"
);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    die("Project not found.");
}

// Fetch previous reviews
$reviews_stmt = $conn->prepare(
    "SELECT r.comment, r.status, r.reviewed_at, u.name AS faculty_name
     FROM reviews r
     JOIN users u ON r.faculty_id = u.id
     WHERE r.project_id = ?
     ORDER BY r.reviewed_at DESC"
);
$reviews_stmt->bind_param("i", $project_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Project</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .container {
            max-width: 850px;
            margin: 20px auto;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        h1, h2 {
            color: #263b80;
            margin-top: 0;
        }

        .detail {
            margin: 12px 0;
            line-height: 1.6;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 16px 0 8px;
        }

        textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button, .back-link {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 18px;
            background: #263b80;
            color: white;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover, .back-link:hover {
            background: #4059ad;
        }

        .message {
            background: #e7f5e9;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .review {
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        .review:last-child {
            border-bottom: none;
        }

        .status {
            font-weight: bold;
            color: #263b80;
        }

        .description {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body>
<div class="container">

    <div class="card">
        <h1>Review Student Project</h1>

        <?php if ($message !== ""): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="detail">
            <strong>Project Title:</strong>
            <?php echo htmlspecialchars($project["title"]); ?>
        </div>

        <div class="detail">
            <strong>Submitted By:</strong>
            <?php echo htmlspecialchars($project["creator_name"]); ?>
        </div>

        <div class="detail">
            <strong>Description:</strong>
            <div class="description"><?php
                echo htmlspecialchars($project["description"] ?? "");
            ?></div>
        </div>

        <div class="detail">
            <strong>Deadline:</strong>
            <?php echo htmlspecialchars($project["deadline"] ?? "Not specified"); ?>
        </div>

        <div class="detail">
            <strong>Current Status:</strong>
            <span class="status">
                <?php echo htmlspecialchars($project["status"]); ?>
            </span>
        </div>
    </div>

    <div class="card">
        <h2>Submit Review</h2>

        <form method="POST"
              action="review_project.php?id=<?php echo $project_id; ?>">

            <label for="comment">Faculty Feedback</label>
            <textarea id="comment" name="comment"
                      placeholder="Enter feedback for the student..."
                      required></textarea>

            <label for="status">Review Decision</label>
            <select id="status" name="status" required>
                <option value="">Select decision</option>
                <option value="Approved">Approve Project</option>
                <option value="Needs Changes">Request Changes</option>
            </select>

            <button type="submit">Submit Review</button>
        </form>

        <a class="back-link" href="admin_dashboard.php">
            Back to Dashboard
        </a>
    </div>

    <div class="card">
        <h2>Previous Reviews</h2>

        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="review">
                    <p>
                        <strong>Faculty:</strong>
                        <?php echo htmlspecialchars($review["faculty_name"]); ?>
                    </p>

                    <p>
                        <strong>Decision:</strong>
                        <?php echo htmlspecialchars($review["status"]); ?>
                    </p>

                    <p>
                        <strong>Feedback:</strong><br>
                        <?php echo nl2br(htmlspecialchars($review["comment"])); ?>
                    </p>

                    <small>
                        <?php echo htmlspecialchars($review["reviewed_at"]); ?>
                    </small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews have been submitted yet.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>

<?php
$reviews_stmt->close();
$conn->close();
?>
