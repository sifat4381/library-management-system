
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: view_fines.php");
    exit;
}

// Verify CSRF token
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    exit("Invalid request.");
}

// Validate Fine ID
$fine_id = filter_var(
    $_POST["fine_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$fine_id || $fine_id < 1) {
    header("Location: view_fines.php?message=failed");
    exit;
}

try {

    // Mark only unpaid fines as paid
    $stmt = $conn->prepare(
        "UPDATE fines
         SET payment_status = 'Paid'
         WHERE id = ?
         AND payment_status = 'Unpaid'"
    );

    $stmt->execute([$fine_id]);

    if ($stmt->rowCount() === 1) {

        header("Location: view_fines.php?message=paid");
        exit;
    }

    // Check existing fine status
    $stmt = $conn->prepare(
        "SELECT payment_status
         FROM fines
         WHERE id = ?"
    );

    $stmt->execute([$fine_id]);
    $fine = $stmt->fetch();

    if (!$fine) {
        header("Location: view_fines.php?message=notfound");
    } else {
        header("Location: view_fines.php?message=already");
    }

    exit;

} catch (PDOException $e) {

    header("Location: view_fines.php?message=failed");
    exit;
}
?>
