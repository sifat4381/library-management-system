
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid Book ID!");
}

$stmt = $conn->prepare(
    "SELECT * FROM books WHERE id = ?"
);
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    exit("Book not found!");
}

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

    $issuedCopies = (int)$book["quantity"]
                  - (int)$book["available_quantity"];

    if ($book_name === "" ||
        $quantity === false ||
        $quantity === null ||
        $quantity < 0) {

        $error = "Please enter valid book information.";

    } elseif ($quantity < $issuedCopies) {

        $error = "Quantity cannot be less than issued copies.";

    } else {

        $available = $quantity - $issuedCopies;

        try {
            $sql = "UPDATE books SET
                    book_name = ?,
                    author = ?,
                    category = ?,
                    isbn = ?,
                    quantity = ?,
                    available_quantity = ?
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $book_name,
                $author,
                $category,
                $isbn === "" ? null : $isbn,
                $quantity,
                $available,
                $id
            ]);

            header("Location: view_books.php?updated=1");
            exit;

        } catch (PDOException $e) {
            $error = $e->getCode() === "23000"
                ? "This ISBN already exists!"
                : "Unable to update book.";
        }
    }

    $book["book_name"] = $book_name;
    $book["author"] = $author;
    $book["category"] = $category;
    $book["isbn"] = $isbn;
    $book["quantity"] = $quantity;
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

    <title>Edit Book - Library Management</title>

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

        <h2>Edit Book</h2>

        <?php if ($error): ?>
            <p class="error">
                <?php echo e($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <label>Book Name</label>
            <input type="text"
                   name="book_name"
                   maxlength="200"
                   value="<?php echo e($book['book_name']); ?>"
                   required>

            <label>Author Name</label>
            <input type="text"
                   name="author"
                   maxlength="150"
                   value="<?php echo e($book['author']); ?>">

            <label>Category</label>
            <input type="text"
                   name="category"
                   maxlength="100"
                   value="<?php echo e($book['category']); ?>">

            <label>ISBN</label>
            <input type="text"
                   name="isbn"
                   maxlength="30"
                   value="<?php echo e($book['isbn']); ?>">

            <label>Quantity</label>
            <input type="number"
                   name="quantity"
                   min="0"
                   step="1"
                   value="<?php echo e($book['quantity']); ?>"
                   required>

            <button type="submit">
                Update Book
            </button>

        </form>

        <a href="view_books.php" class="back">
            Back to Books
        </a>

    </div>

</body>
</html>
