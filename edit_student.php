
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid Student ID!");
}

$stmt = $conn->prepare(
    "SELECT * FROM students WHERE id = ?"
);
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    exit("Student not found!");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !is_string($_POST["csrf_token"] ?? null) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    ) {
        exit("Invalid request.");
    }

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

            $sql = "UPDATE students SET
                    student_name = ?,
                    roll_number = ?,
                    department = ?,
                    semester = ?,
                    phone = ?,
                    email = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $student_name,
                $roll_number,
                $department,
                $semester,
                $phone,
                $email,
                $id
            ]);

            header("Location: view_students.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $error = "This Roll Number already exists!";
            } else {
                $error = "Unable to update student.";
            }
        }
    }

    $student["student_name"] = $student_name;
    $student["roll_number"] = $roll_number;
    $student["department"] = $department;
    $student["semester"] = $semester;
    $student["phone"] = $phone;
    $student["email"] = $email;
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

    <title>Edit Student - Library Management</title>

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
            background: white;
            padding: 30px;
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

        .error {
            color: red;
            text-align: center;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
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

        <h2>Edit Student</h2>

        <?php if ($error): ?>
            <p class="error">
                <?php echo e($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo e($_SESSION['csrf_token']); ?>">

            <label>Student Name</label>
            <input type="text"
                   name="student_name"
                   maxlength="100"
                   value="<?php echo e($student['student_name']); ?>"
                   required>

            <label>Roll Number</label>
            <input type="text"
                   name="roll_number"
                   maxlength="50"
                   value="<?php echo e($student['roll_number']); ?>"
                   required>

            <label>Department</label>
            <select name="department">
                <?php
                $departments = [
                    "Computer",
                    "Electrical",
                    "Civil",
                    "Mechanical",
                    "Electronics",
                    "Other"
                ];

                foreach ($departments as $dept):
                ?>
                    <option value="<?php echo e($dept); ?>"
                        <?php
                        echo $student["department"] === $dept
                            ? "selected" : "";
                        ?>>
                        <?php echo e($dept); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Semester</label>
            <select name="semester">
                <?php
                $semesters = [
                    "1st", "2nd", "3rd", "4th",
                    "5th", "6th", "7th", "8th"
                ];

                foreach ($semesters as $sem):
                ?>
                    <option value="<?php echo e($sem); ?>"
                        <?php
                        echo $student["semester"] === $sem
                            ? "selected" : "";
                        ?>>
                        <?php echo e($sem); ?> Semester
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Phone Number</label>
            <input type="tel"
                   name="phone"
                   maxlength="20"
                   value="<?php echo e($student['phone']); ?>">

            <label>Email Address</label>
            <input type="email"
                   name="email"
                   maxlength="100"
                   value="<?php echo e($student['email']); ?>">

            <button type="submit">
                Update Student
            </button>

        </form>

        <a href="view_students.php" class="back">
            Back to Students
        </a>

    </div>

</body>
</html>
