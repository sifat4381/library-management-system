

<?php
session_start();
require_once "config/db.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please fill in all fields.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin &&
            password_verify($password, $admin["password"])) {

            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Library Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef2f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-box {
            background: white;
            width: 380px;
            max-width: 92%;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #17365d;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #17365d;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #24558b;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <div class="login-box">

        <h2>Library Management System</h2>
        <p class="subtitle">Admin Login Panel</p>

        
<?php if (!empty($error)): ?>
    <p style="color: red; text-align: center;">
        <?php echo htmlspecialchars($error); ?>
    </p>
<?php endif; ?>

        <form action="" method="POST">

            <label>Username</label>
            <input type="text"
                   name="username"
                   placeholder="Enter username"
                   required>

            <label>Password</label>
            <input type="password"
                   name="password"
                   placeholder="Enter password"
                   required>

            <button type="submit">Login</button>

        </form>

        
<div class="footer">
    <p>Library Management System</p>
    <p>
        Developed by
        <strong>Sifat Abdullah Mithu</strong>
    </p>
    <p>&copy; 2026 All Rights Reserved.</p>
</div>

    </div>

</body>
</html>
