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
$user_id = $_SESSION["user_id"];

$message = "";
$error = "";


// Get project
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


// Upload file
if (isset($_POST["upload"])) {

    if (!isset($_FILES["project_file"]) ||
        $_FILES["project_file"]["error"] != 0) {

        $error = "Please select a file.";
    } else {

        $file = $_FILES["project_file"];

        $original_name = $file["name"];
        $tmp_name = $file["tmp_name"];
        $file_size = $file["size"];

        $extension = strtolower(
            pathinfo($original_name, PATHINFO_EXTENSION)
        );


        // Allowed file types
        $allowed = [
            "pdf",
            "doc",
            "docx",
            "ppt",
            "pptx",
            "zip"
        ];


        // Maximum size: 10 MB
        if (!in_array($extension, $allowed)) {

            $error = "Only PDF, DOC, DOCX, PPT, PPTX and ZIP files are allowed.";

        } elseif ($file_size > 10 * 1024 * 1024) {

            $error = "File size must be less than 10 MB.";

        } else {

            $upload_folder = "uploads/";

            if (!is_dir($upload_folder)) {
                mkdir($upload_folder, 0777, true);
            }


            // Create unique file name
            $new_name = time() . "_" . basename($original_name);

            $file_path = $upload_folder . $new_name;


            if (move_uploaded_file($tmp_name, $file_path)) {

                $stmt = $conn->prepare(
                    "INSERT INTO files
                    (project_id, uploaded_by, file_name, file_path)
                    VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iiss",
                    $project_id,
                    $user_id,
                    $original_name,
                    $file_path
                );


                if ($stmt->execute()) {

                    $message = "File uploaded successfully!";

                } else {

                    $error = "File information could not be saved.";
                }

                $stmt->close();

            } else {

                $error = "File upload failed.";
            }
        }
    }
}


// Get uploaded files
$stmt = $conn->prepare(
    "SELECT f.*, u.name
     FROM files f
     JOIN users u ON f.uploaded_by = u.id
     WHERE f.project_id = ?
     ORDER BY f.uploaded_at DESC"
);

$stmt->bind_param("i", $project_id);
$stmt->execute();

$files_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>

<head>

    <title>Project Files</title>

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
            max-width: 1000px;
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

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            box-sizing: border-box;
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

        .error {
            background: #fee2e2;
            color: #991b1b;
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

        .download {
            color: #2563eb;
            text-decoration: none;
        }

    </style>

</head>


<body>

<div class="header">

    <h2>
        Files - <?php echo htmlspecialchars($project["title"]); ?>
    </h2>

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


    <?php if ($error != "") { ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>


    <!-- UPLOAD FORM -->

    <div class="card">

        <h3>📤 Upload Project File</h3>

        <form method="POST"
      action="upload_file.php?project_id=<?php echo $project_id; ?>"
      enctype="multipart/form-data">

            <input
                type="file"
                name="project_file"
                required
            >

            <button
                type="submit"
                name="upload">

                Upload File

            </button>

        </form>

        <p>
            Allowed: PDF, DOC, DOCX, PPT, PPTX, ZIP
            <br>
            Maximum size: 10 MB
        </p>

    </div>


    <!-- FILE LIST -->

    <div class="card">

        <h3>📁 Uploaded Files</h3>

        <?php if ($files_result->num_rows > 0) { ?>

            <table>

                <tr>

                    <th>File Name</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Action</th>

                </tr>


                <?php while ($file = $files_result->fetch_assoc()) { ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $file["file_name"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $file["name"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $file["uploaded_at"]
                            );
                            ?>
                        </td>


                        <td>

                            <a
                                class="download"
                                href="<?php
                                echo htmlspecialchars(
                                    $file["file_path"]
                                );
                                ?>"
                                target="_blank"
                            >

                                Open

                            </a>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php } else { ?>

            <p>No files uploaded yet.</p>

        <?php } ?>

    </div>

</div>

</body>

</html>