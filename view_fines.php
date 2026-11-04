
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

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$search = trim($_GET["search"] ?? "");

$sql = "SELECT
            f.id,
            f.issue_id,
            f.fine_amount,
            f.payment_status,
            s.student_name,
            s.roll_number,
            b.book_name,
            ib.issue_date,
            ib.due_date,
            ib.return_date
        FROM fines f
        INNER JOIN issued_books ib
            ON f.issue_id = ib.id
        INNER JOIN students s
            ON ib.student_id = s.id
        INNER JOIN books b
            ON ib.book_id = b.id";

$params = [];

if ($search !== "") {
    $sql .= " WHERE
              s.student_name LIKE ?
              OR s.roll_number LIKE ?
              OR b.book_name LIKE ?";

    $keyword = "%" . $search . "%";

    $params = [
        $keyword,
        $keyword,
        $keyword
    ];
}

$sql .= " ORDER BY f.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$fines = $stmt->fetchAll();

// Calculate fine statistics
$stats = $conn->query(
    "SELECT
        COUNT(*) AS total_fines,
        COALESCE(SUM(fine_amount), 0) AS total_amount,
        COALESCE(SUM(
            CASE WHEN payment_status = 'Unpaid'
            THEN fine_amount ELSE 0 END
        ), 0) AS unpaid_amount,
        COALESCE(SUM(
            CASE WHEN payment_status = 'Paid'
            THEN fine_amount ELSE 0 END
        ), 0) AS paid_amount
     FROM fines"
)->fetch();

$messages = [
    "paid" => "Fine payment recorded successfully!",
    "already" => "This fine is already paid.",
    "notfound" => "Fine record not found.",
    "failed" => "Unable to update fine payment."
];

$message = $_GET["message"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Fine Management</title>

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
            max-width: 1300px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }

        h2 {
            color: #17365d;
        }

        .cards {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(190px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .card {
            padding: 20px;
            border-radius: 9px;
            background: #f1f5f9;
            border-left: 5px solid #17365d;
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #475569;
        }

        .card p {
            margin: 0;
            font-size: 25px;
            font-weight: bold;
            color: #17365d;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 25px 0;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        button, .btn {
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            background: #17365d;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }

        .pay-btn {
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
            background: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .unpaid {
            background: #fee2e2;
            color: #991b1b;
        }

        .paid {
            background: #dcfce7;
            color: #166534;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            background: #e0f2fe;
            color: #075985;
            margin-bottom: 15px;
        }

        .empty {
            text-align: center;
            padding: 25px;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
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

    <h2>Fine Management</h2>

    <?php if (isset($messages[$message])): ?>
        <p class="message">
            <?php echo e($messages[$message]); ?>
        </p>
    <?php endif; ?>

    <div class="cards">

        <div class="card">
            <h3>Total Fine Records</h3>
            <p>
                <?php echo (int)$stats["total_fines"]; ?>
            </p>
        </div>

        <div class="card">
            <h3>Total Fine Amount</h3>
            <p>
                <?php
                echo number_format(
                    (float)$stats["total_amount"], 2
                );
                ?> BDT
            </p>
        </div>

        <div class="card">
            <h3>Unpaid Amount</h3>
            <p>
                <?php
                echo number_format(
                    (float)$stats["unpaid_amount"], 2
                );
                ?> BDT
            </p>
        </div>

        <div class="card">
            <h3>Paid Amount</h3>
            <p>
                <?php
                echo number_format(
                    (float)$stats["paid_amount"], 2
                );
                ?> BDT
            </p>
        </div>

    </div>

    <div class="top-bar">

        <form method="GET" class="search-form">

            <input type="text"
                   name="search"
                   placeholder="Search student or book..."
                   value="<?php echo e($search); ?>">

            <button type="submit">Search</button>

            <a href="view_fines.php" class="btn">
                Reset
            </a>

        </form>

    </div>

    <p>
        Records Found:
        <strong><?php echo count($fines); ?></strong>
    </p>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Fine ID</th>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Book Name</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Fine Amount</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if (count($fines) > 0): ?>

                <?php foreach ($fines as $fine): ?>

                    <tr>

                        <td>
                            <?php echo (int)$fine["id"]; ?>
                        </td>

                        <td>
                            <?php echo e($fine["student_name"]); ?>
                        </td>

                        <td>
                            <?php echo e($fine["roll_number"]); ?>
                        </td>

                        <td>
                            <?php echo e($fine["book_name"]); ?>
                        </td>

                        <td>
                            <?php echo e($fine["due_date"]); ?>
                        </td>

                        <td>
                            <?php echo e($fine["return_date"]); ?>
                        </td>

                        <td>
                            <?php
                            echo number_format(
                                (float)$fine["fine_amount"], 2
                            );
                            ?> BDT
                        </td>

                        <td>

                            <?php if ($fine["payment_status"] === "Paid"): ?>

                                <span class="badge paid">
                                    Paid
                                </span>

                            <?php else: ?>

                                <span class="badge unpaid">
                                    Unpaid
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($fine["payment_status"] === "Unpaid"): ?>

                                <form action="pay_fine.php"
                                      method="POST"
                                      onsubmit="return confirm('Confirm fine payment received?');">

                                    <input type="hidden"
                                           name="fine_id"
                                           value="<?php echo (int)$fine['id']; ?>">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?php echo e($_SESSION['csrf_token']); ?>">

                                    <button type="submit"
                                            class="pay-btn">
                                        Mark as Paid
                                    </button>

                                </form>

                            <?php else: ?>

                                <span style="color:green;">
                                    Completed
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" class="empty">
                        No fine records found!
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
