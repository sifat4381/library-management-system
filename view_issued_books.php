
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


$search = trim($_GET["search"] ?? "");

$sql = "SELECT
            ib.id,
            s.student_name,
            s.roll_number,
            b.book_name,
            ib.issue_date,
            ib.due_date,
            ib.return_date,
            ib.status
        FROM issued_books ib
        INNER JOIN students s
            ON ib.student_id = s.id
        INNER JOIN books b
            ON ib.book_id = b.id";

if ($search !== "") {

    $sql .= " WHERE
              s.student_name LIKE ?
              OR s.roll_number LIKE ?
              OR b.book_name LIKE ?";

    $sql .= " ORDER BY ib.id DESC";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->execute([
        $keyword,
        $keyword,
        $keyword
    ]);

} else {

    $sql .= " ORDER BY ib.id DESC";

    $stmt = $conn->query($sql);
}

$issuedBooks = $stmt->fetchAll();

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

    <title>Issued Books - Library Management</title>

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
            max-width: 1250px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        h2 {
            color: #17365d;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 25px 0;
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
            display: inline-block;
            font-size: 14px;
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

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .issued {
            background: #fef3c7;
            color: #92400e;
        }

        .returned {
            background: #dcfce7;
            color: #166534;
        }

        .overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #dc2626;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            color: #17365d;
            text-decoration: none;
        }

        .count {
            color: #555;
            margin-bottom: 15px;
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

        <h2>Issued Books Management</h2>
        

<?php if (isset($_GET["returned"])): ?>

    <p style="color:green;">
        Book returned successfully!
    </p>

    <?php
    $fine = filter_var(
        $_GET["fine"] ?? 0,
        FILTER_VALIDATE_FLOAT
    );

    if ($fine === false || $fine < 0) {
        $fine = 0;
    }
    ?>

    <?php if ($fine > 0): ?>

        <p style="color:red;">
            Late Return Fine:
            <?php echo number_format($fine, 2); ?> BDT
        </p>

    <?php else: ?>

        <p style="color:green;">
            No fine. Book returned on time.
        </p>

    <?php endif; ?>

<?php endif; ?>


<?php if (isset($_GET["error"])): ?>

    <p style="color:red;">

        <?php
        $errors = [
            "notfound" => "Issue record not found!",
            "already" => "This book has already been returned!",
            "date" => "Return Date cannot be before Issue Date!",
            "stock" => "Book stock information is inconsistent.",
            "failed" => "Unable to return book."
        ];

        $code = $_GET["error"];

        echo e(
            $errors[$code] ?? "Unable to return book."
        );
        ?>

    </p>

<?php endif; ?>


        <div class="top-bar">

            <form method="GET" class="search-form">

                <input type="text"
                       name="search"
                       placeholder="Search student or book..."
                       value="<?php echo e($search); ?>">

                <button type="submit">
                    Search
                </button>

                <a href="view_issued_books.php" class="btn">
                    Reset
                </a>

            </form>

            <a href="issue_book.php"
               class="btn btn-green">
                + Issue New Book
            </a>

        </div>

        <p class="count">
            Records Found:
            <strong>
                <?php echo count($issuedBooks); ?>
            </strong>
        </p>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Issue ID</th>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Book Name</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($issuedBooks) > 0): ?>

                    <?php foreach ($issuedBooks as $row): ?>

                    <?php
                    $isOverdue = (
                        $row["status"] === "Issued" &&
                        $row["due_date"] < date("Y-m-d")
                    );
                    ?>

                    <tr>

                        <td>
                            <?php echo (int)$row["id"]; ?>
                        </td>

                        <td>
                            <?php echo e($row["student_name"]); ?>
                        </td>

                        <td>
                            <?php echo e($row["roll_number"]); ?>
                        </td>

                        <td>
                            <?php echo e($row["book_name"]); ?>
                        </td>

                        <td>
                            <?php echo e($row["issue_date"]); ?>
                        </td>

                        <td>
                            <?php echo e($row["due_date"]); ?>
                        </td>

                        <td>
                            <?php
                            echo $row["return_date"]
                                ? e($row["return_date"])
                                : "Not Returned";
                            ?>
                        </td>

                        <td>

                            <?php if ($row["status"] === "Returned"): ?>

                                <span class="badge returned">
                                    Returned
                                </span>

                            <?php elseif ($isOverdue): ?>

                                <span class="badge overdue">
                                    Overdue
                                </span>

                            <?php else: ?>

                                <span class="badge issued">
                                    Issued
                                </span>

                            <?php endif; ?>

                        </td>
                        
<td>

    <?php if ($row["status"] === "Issued"): ?>

        <form action="return_book.php"
              method="POST"
              onsubmit="return confirm('Are you sure you want to return this book?');">

            <input type="hidden"
                   name="issue_id"
                   value="<?php echo (int)$row['id']; ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo e($_SESSION['csrf_token']); ?>">

            <button type="submit"
                    style="background:#16a34a;
                           color:white;
                           padding:9px 12px;
                           border:none;
                           border-radius:5px;
                           cursor:pointer;
                           white-space:nowrap;">
                Return Book
            </button>

        </form>

    <?php else: ?>

        <span style="color:#15803d;">
            Completed
        </span>

    <?php endif; ?>

</td>


                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty">
                            No issued books found!
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
