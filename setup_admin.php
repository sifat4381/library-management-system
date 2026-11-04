
<?php
require_once "config/db.php";

$username = "admin";
$password = "Admin@12345";

$check = $conn->prepare(
    "SELECT id FROM admins WHERE username = ?"
);
$check->execute([$username]);

if ($check->fetch()) {
    exit("Admin account already exists.");
}

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$sql = "INSERT INTO admins (username, password)
        VALUES (?, ?)";

$stmt = $conn->prepare($sql);
$stmt->execute([$username, $hashedPassword]);

echo "Admin account created successfully!";
?>
