
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_name = trim($_POST["student_name"] ?? "");
    $roll_number = trim($_POST["roll_number"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $semester = trim($_POST["semester"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($student_name === "" || $roll_number === "") {

        $error = "Student Name and Roll Number are required.";

    } elseif (
        $email !== "" &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = "Please enter a valid email address.";

    } else {

        try {

            $sql = "INSERT INTO students
                    (student_name, roll_number,
                     department, semester, phone, email)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $student_name,
                $roll_number,
                $department,
                $semester,
                $phone,
                $email
            ]);

            $message = "Student added successfully!";

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $error = "This Roll Number already exists!";
            } else {
                $error = "Unable to add student.";
            }
        }
    }
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Student - Library Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef2f7;
        }

        .header {
            background: #17365d;
            color: white;
            padding: 20px 30px;
        }

        .container {
            max-width: 550px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h2 {
            color: #17365d;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 25px;
            background: #17365d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #24558b;
        }

        .success {
            color: green;
            text-align: center;
        }

        .error {
            color: red;
            text-align: center;
        }

        .back {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #17365d;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="header">
        <h3>Library Management System</h3>
    </div>

    <div class="container">

        <h2>Add New Student</h2>

        <?php if ($message): ?>
            <p class="success">
                <?php echo e($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">
                <?php echo e($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <label>Student Name</label>
            <input type="text"
                   name="student_name"
                   maxlength="100"
                   required>

            <label>Roll Number</label>
            <input type="text"
                   name="roll_number"
                   maxlength="50"
                   required>

            <label>Department</label>
            <select name="department">
                <option value="Computer">Computer</option>
                <option value="Electrical">Electrical</option>
                <option value="Civil">Civil</option>
                <option value="Mechanical">Mechanical</option>
                <option value="Electronics">Electronics</option>
                <option value="Other">Other</option>
            </select>

            <label>Semester</label>
            <select name="semester">
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
                <option value="3rd">3rd Semester</option>
                <option value="4th">4th Semester</option>
                <option value="5th">5th Semester</option>
                <option value="6th">6th Semester</option>
                <option value="7th">7th Semester</option>
                <option value="8th">8th Semester</option>
            </select>

            <label>Phone Number</label>
            <input type="tel"
                   name="phone"
                   maxlength="20">

            <label>Email Address</label>
            <input type="email"
                   name="email"
                   maxlength="100">

            <button type="submit">
                Add Student
            </button>

        </form>

        <a href="dashboard.php" class="back">
            Back to Dashboard
        </a>

    </div>

</body>
</html>
