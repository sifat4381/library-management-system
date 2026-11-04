
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(
        random_bytes(32)
    );
}


// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: view_books.php");
    exit;
}

// Verify CSRF token
if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )
) {
    exit("Invalid request.");
}

$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    exit("Invalid Book ID!");
}

try {
    // Prevent deletion if book has issue history
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM issued_books
         WHERE book_id = ?"
    );

    $stmt->execute([$id]);
    $historyCount = (int)$stmt->fetchColumn();

    if ($historyCount > 0) {
        header("Location: view_books.php?error=history");
        exit;
    }

    // Delete book
    $stmt = $conn->prepare(
        "DELETE FROM books WHERE id = ?"
    );

    $stmt->execute([$id]);

    header("Location: view_books.php?deleted=1");
    exit;

} catch (PDOException $e) {
    header("Location: view_books.php?error=delete");
    exit;
}
?>
