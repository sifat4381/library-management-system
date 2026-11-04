
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

    $book_name = trim($_POST["book_name"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $isbn = trim($_POST["isbn"] ?? "");
    $quantity = filter_var(
        $_POST["quantity"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($book_name === "" || $quantity === false ||
        $quantity === null || $quantity < 1) {

        $error = "Enter a book name and valid quantity.";

    } else {

        try {
            $sql = "INSERT INTO books
                    (book_name, author, category, isbn,
                     quantity, available_quantity)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $book_name,
                $author,
                $category,
                $isbn === "" ? null : $isbn,
                $quantity,
                $quantity
            ]);

            $message = "Book added successfully!";

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $error = "This ISBN already exists.";
            } else {
                $error = "Unable to add book.";
            }
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

    <title>Add Book - Library Management</title>

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

        input {
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
            color: #15803d;
            text-align: center;
        }

        .error {
            color: #dc2626;
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

        <h2>Add New Book</h2>

        <?php if ($message): ?>
            <p class="success">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <label>Book Name</label>
            <input type="text" name="book_name"
                   maxlength="200" required>

            <label>Author Name</label>
            <input type="text" name="author"
                   maxlength="150">

            <label>Category</label>
            <input type="text" name="category"
                   maxlength="100">

            <label>ISBN</label>
            <input type="text" name="isbn"
                   maxlength="30">

            <label>Quantity</label>
            <input type="number" name="quantity"
                   min="1" step="1" required>

            <button type="submit">Add Book</button>

        </form>

        <a href="dashboard.php" class="back">
            Back to Dashboard
        </a>

    </div>

</body>
</html>
