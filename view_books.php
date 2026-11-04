
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $sql = "SELECT * FROM books
            WHERE book_name LIKE ?
            OR author LIKE ?
            OR category LIKE ?
            OR isbn LIKE ?
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->execute([
        $keyword,
        $keyword,
        $keyword,
        $keyword
    ]);

} else {

    $stmt = $conn->query(
        "SELECT * FROM books ORDER BY id DESC"
    );
}

$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>View Books - Library Management</title>

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
            max-width: 1200px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        h2 {
            color: #17365d;
            margin-bottom: 25px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button, .btn {
            padding: 12px 18px;
            background: #17365d;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        }

        .btn-green {
            background: #16a34a;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #17365d;
            color: white;
        }

        tr:hover {
            background: #f1f5f9;
        }

        .available {
            color: #15803d;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #dc2626;
            padding: 20px;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            color: #17365d;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .container {
                margin: 15px;
                padding: 15px;
            }

            .search-form input {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h3>Library Management System</h3>
    </div>

    <div class="container">

        <h2>Book Management</h2>
        
<?php if (isset($_GET["deleted"])): ?>
    <p style="color:green;">
        Book deleted successfully!
    </p>
<?php endif; ?>

<?php if (isset($_GET["updated"])): ?>
    <p style="color:green;">
        Book updated successfully!
    </p>
<?php endif; ?>

<?php if (($_GET["error"] ?? "") === "history"): ?>
    <p style="color:red;">
        Cannot delete a book with issue history!
    </p>
<?php endif; ?>

<?php if (($_GET["error"] ?? "") === "delete"): ?>
    <p style="color:red;">
        Unable to delete book.
    </p>
<?php endif; ?>


        <div class="top-bar">

            <form method="GET" class="search-form">

                <input type="text"
                       name="search"
                       placeholder="Search books..."
                       value="<?php echo htmlspecialchars(
                           $search, ENT_QUOTES, 'UTF-8'
                       ); ?>">

                <button type="submit">
                    Search
                </button>

                <a href="view_books.php" class="btn">
                    Reset
                </a>

            </form>

            <a href="add_book.php"
               class="btn btn-green">
                + Add New Book
            </a>

        </div>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Quantity</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($books) > 0): ?>

                    <?php foreach ($books as $book): ?>

                    <tr>
                        <td>
                            <?php echo (int)$book["id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $book["book_name"]
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $book["author"] ?? ""
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $book["category"] ?? ""
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $book["isbn"] ?? ""
                            ); ?>
                        </td>

                        <td>
                            <?php echo (int)$book["quantity"]; ?>
                        </td>

                        <td class="available">
                            <?php echo (int)$book[
                                "available_quantity"
                            ]; ?>
                        </td>
                        

<td>
    <a href="edit_book.php?id=<?php echo (int)$book['id']; ?>"
       style="background:#2563eb;
              color:white;
              padding:8px 15px;
              border-radius:5px;
              text-decoration:none;
              display:inline-block;
              margin-bottom:5px;">
        Edit
    </a>

    <form action="delete_book.php"
          method="POST"
          style="display:inline;"
          onsubmit="return confirm('Are you sure you want to delete this book?');">

        <input type="hidden"
               name="id"
               value="<?php echo (int)$book['id']; ?>">

        <input type="hidden"
               name="csrf_token"
               value="<?php echo htmlspecialchars(
                   $_SESSION['csrf_token'],
                   ENT_QUOTES,
                   'UTF-8'
               ); ?>">

        <button type="submit"
                style="background:#dc2626;
                       color:white;
                       padding:8px 15px;
                       border:none;
                       border-radius:5px;
                       cursor:pointer;">
            Delete
        </button>
    </form>
</td>


                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            No books found!
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

        <a href="dashboard.php" class="back">
            Back to Dashboard
        </a>

    </div>

</body>
</html>
