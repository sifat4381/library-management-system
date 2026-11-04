
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

// Fine per late day
$fine_per_day = 5;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: view_issued_books.php");
    exit;
}

// CSRF verification
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    exit("Invalid request.");
}

// Validate Issue ID
$issue_id = filter_var(
    $_POST["issue_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$issue_id || $issue_id < 1) {
    exit("Invalid Issue ID!");
}

try {

    $conn->beginTransaction();

    // Get issue record and lock it
    $stmt = $conn->prepare(
        "SELECT book_id, issue_date, due_date, status
         FROM issued_books
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch();

    if (!$issue) {
        throw new Exception("notfound");
    }

    if ($issue["status"] === "Returned") {
        throw new Exception("already");
    }

    $return_date = date("Y-m-d");

    if ($return_date < $issue["issue_date"]) {
        throw new Exception("date");
    }

    // Calculate late days
    $dueDate = new DateTimeImmutable($issue["due_date"]);
    $returnDate = new DateTimeImmutable($return_date);

    $late_days = 0;

    if ($returnDate > $dueDate) {
        $late_days = (int)$dueDate
            ->diff($returnDate)->days;
    }

    $fine_amount = $late_days * $fine_per_day;

    // Update issue status
    $stmt = $conn->prepare(
        "UPDATE issued_books
         SET status = 'Returned',
             return_date = ?
         WHERE id = ?
           AND status = 'Issued'"
    );

    $stmt->execute([
        $return_date,
        $issue_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("already");
    }

    // Increase available book quantity
    $stmt = $conn->prepare(
        "UPDATE books
         SET available_quantity = available_quantity + 1
         WHERE id = ?
           AND available_quantity < quantity"
    );

    $stmt->execute([$issue["book_id"]]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("stock");
    }

    // Save fine if late
    if ($fine_amount > 0) {

        $stmt = $conn->prepare(
            "INSERT INTO fines
             (issue_id, fine_amount, payment_status)
             VALUES (?, ?, 'Unpaid')"
        );

        $stmt->execute([
            $issue_id,
            $fine_amount
        ]);
    }

    $conn->commit();

    header(
        "Location: view_issued_books.php?returned=1&fine=" .
        urlencode((string)$fine_amount)
    );
    exit;

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    $allowedErrors = [
        "notfound",
        "already",
        "date",
        "stock"
    ];

    $error = $e->getMessage();

    if (!in_array($error, $allowedErrors, true)) {
        $error = "failed";
    }

    header(
        "Location: view_issued_books.php?error=" .
        urlencode($error)
    );
    exit;
}
?>
