
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: view_students.php");
    exit;
}

// Verify CSRF token
if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($_POST["csrf_token"] ?? null) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {
    exit("Invalid request.");
}

// Validate Student ID
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    exit("Invalid Student ID!");
}

try {

    // Check if student has book issue history
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM issued_books
         WHERE student_id = ?"
    );

    $stmt->execute([$id]);

    $historyCount = (int)$stmt->fetchColumn();

    if ($historyCount > 0) {
        header("Location: view_students.php?error=history");
        exit;
    }

    // Delete student
    $stmt = $conn->prepare(
        "DELETE FROM students WHERE id = ?"
    );

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header("Location: view_students.php?deleted=1");
    } else {
        header("Location: view_students.php?error=notfound");
    }

    exit;

} catch (PDOException $e) {

    header("Location: view_students.php?error=delete");
    exit;
}
?>
