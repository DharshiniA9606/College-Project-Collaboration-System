
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Project Collaboration System</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            color: #202b3c;
        }

        header {
            background: #172554;
            color: white;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            font-size: 22px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 18px;
            font-weight: bold;
        }

        .hero {
            text-align: center;
            padding: 85px 20px;
            background: linear-gradient(135deg, #dbeafe, #eef2ff);
        }

        .hero h1 {
            font-size: 42px;
            margin-bottom: 18px;
            color: #172554;
        }

        .hero p {
            font-size: 17px;
            line-height: 1.8;
            max-width: 700px;
            margin: 0 auto 28px;
        }

        .btn {
            display: inline-block;
            padding: 13px 25px;
            margin: 7px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
            background: #2563eb;
            color: white;
        }

        .btn.secondary {
            background: white;
            color: #2563eb;
            border: 2px solid #2563eb;
        }

        .features {
            padding: 55px 8%;
            text-align: center;
        }

        .features h2 {
            font-size: 30px;
            margin-bottom: 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 22px;
        }

        .card {
            background: white;
            padding: 28px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #00000010;
        }

        .card h3 {
            color: #1d4ed8;
            margin-bottom: 12px;
        }

        .card p {
            line-height: 1.7;
            color: #536174;
        }

        footer {
            background: #172554;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 20px;
        }

        @media (max-width: 600px) {
            header {
                justify-content: center;
                text-align: center;
            }

            nav a {
                margin: 8px;
                display: inline-block;
            }

            .hero h1 {
                font-size: 32px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>College Collaboration</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<section class="hero">
    <h1>College Project Collaboration System</h1>

    <p>
        Plan academic projects, collaborate with your teammates,
        assign tasks, track project progress and share project files
        in one convenient place.
    </p>

    <a href="login.php" class="btn">Login</a>
    <a href="register.php" class="btn secondary">Create Account</a>
</section>

<section class="features">
    <h2>What You Can Do</h2>

    <div class="cards">

        <div class="card">
            <h3>📁 Project Management</h3>
            <p>Create academic projects and manage project information
            from one place.</p>
        </div>

        <div class="card">
            <h3>👥 Team Collaboration</h3>
            <p>Work with your classmates and organize your project team.</p>
        </div>

        <div class="card">
            <h3>✅ Task Tracking</h3>
            <p>Manage assigned tasks, deadlines and task completion status.</p>
        </div>

        <div class="card">
            <h3>📤 File Sharing</h3>
            <p>Upload and organize files related to your academic projects.</p>
        </div>

    </div>
</section>

<footer>
    <p>College Project Collaboration System</p>
    <p>Academic Project Management Made Simple</p>
</footer>

</body>
</html>
