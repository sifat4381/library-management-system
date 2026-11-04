
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        exit("Invalid request.");
    }

    $student_id = filter_var(
        $_POST["student_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $book_id = filter_var(
        $_POST["book_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $issue_date = $_POST["issue_date"] ?? "";
    $due_date = $_POST["due_date"] ?? "";

    $issueObj = DateTimeImmutable::createFromFormat(
        "!Y-m-d", $issue_date
    );

    $dueObj = DateTimeImmutable::createFromFormat(
        "!Y-m-d", $due_date
    );

    $validIssue = $issueObj &&
        $issueObj->format("Y-m-d") === $issue_date;

    $validDue = $dueObj &&
        $dueObj->format("Y-m-d") === $due_date;

    if (
        !$student_id || !$book_id ||
        !$validIssue || !$validDue
    ) {
        $error = "Please enter valid information.";

    } elseif ($dueObj < $issueObj) {

        $error = "Due Date cannot be before Issue Date.";

    } else {

        try {

            $conn->beginTransaction();

            // Check student
            $stmt = $conn->prepare(
                "SELECT id FROM students WHERE id = ?"
            );
            $stmt->execute([$student_id]);

            if (!$stmt->fetch()) {
                throw new Exception("Student not found.");
            }

            // Reduce available quantity only if stock exists
            $stmt = $conn->prepare(
                "UPDATE books
                 SET available_quantity = available_quantity - 1
                 WHERE id = ?
                 AND available_quantity > 0"
            );

            $stmt->execute([$book_id]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    "Book is not available for issue."
                );
            }

            // Save issue record
            $stmt = $conn->prepare(
                "INSERT INTO issued_books
                 (student_id, book_id, issue_date,
                  due_date, status)
                 VALUES (?, ?, ?, ?, 'Issued')"
            );

            $stmt->execute([
                $student_id,
                $book_id,
                $issue_date,
                $due_date
            ]);

            $conn->commit();

            header("Location: issue_book.php?success=1");
            exit;

        } catch (Exception $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = $e instanceof PDOException
                ? "Unable to issue book."
                : $e->getMessage();
        }
    }
}

// Load students
$students = $conn->query(
    "SELECT id, student_name, roll_number
     FROM students
     ORDER BY student_name ASC"
)->fetchAll();

// Load available books
$books = $conn->query(
    "SELECT id, book_name, available_quantity
     FROM books
     WHERE available_quantity > 0
     ORDER BY book_name ASC"
)->fetchAll();

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

    <title>Issue Book - Library Management</title>

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

        select, input {
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

        <h2>Issue Book</h2>

        <?php if (isset($_GET["success"])): ?>
            <p class="success">
                Book issued successfully!
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">
                <?php echo e($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo e($_SESSION['csrf_token']); ?>">

            <label>Select Student</label>
            <select name="student_id" required>
                <option value="">-- Select Student --</option>

                <?php foreach ($students as $student): ?>
                    <option value="<?php echo (int)$student['id']; ?>">
                        <?php
                        echo e(
                            $student["student_name"] .
                            " (" . $student["roll_number"] . ")"
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Select Book</label>
            <select name="book_id" required>
                <option value="">-- Select Book --</option>

                <?php foreach ($books as $book): ?>
                    <option value="<?php echo (int)$book['id']; ?>">
                        <?php
                        echo e(
                            $book["book_name"] .
                            " - Available: " .
                            $book["available_quantity"]
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Issue Date</label>
            <input type="date"
                   name="issue_date"
                   value="<?php echo date('Y-m-d'); ?>"
                   required>

            <label>Due Date</label>
            <input type="date"
                   name="due_date"
                   value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>"
                   required>

            <button type="submit">
                Issue Book
            </button>

        </form>

        <a href="dashboard.php" class="back">
            Back to Dashboard
        </a>

    </div>

</body>
</html>
