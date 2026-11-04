
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$allowedTypes = ["all", "issued", "returned", "fines"];
$type = $_GET["type"] ?? "all";

if (!in_array($type, $allowedTypes, true)) {
    $type = "all";
}

$from = $_GET["from"] ?? "";
$to = $_GET["to"] ?? "";

function validDate($date) {
    if ($date === "") return true;

    $d = DateTimeImmutable::createFromFormat(
        "!Y-m-d", $date
    );

    return $d && $d->format("Y-m-d") === $date;
}

$error = "";

if (!validDate($from) || !validDate($to)) {
    $error = "Invalid date format.";
} elseif ($from !== "" && $to !== "" && $from > $to) {
    $error = "From Date cannot be after To Date.";
}

$records = [];

if ($error === "") {

    $sql = "SELECT
                ib.id,
                s.student_name,
                s.roll_number,
                b.book_name,
                ib.issue_date,
                ib.due_date,
                ib.return_date,
                ib.status,
                COALESCE(f.fine_amount, 0) AS fine_amount,
                COALESCE(f.payment_status, 'N/A') AS payment_status
            FROM issued_books ib
            INNER JOIN students s
                ON ib.student_id = s.id
            INNER JOIN books b
                ON ib.book_id = b.id
            LEFT JOIN (
                SELECT issue_id,
                       SUM(fine_amount) AS fine_amount,
                       CASE
                         WHEN SUM(payment_status = 'Unpaid') > 0
                         THEN 'Unpaid'
                         ELSE 'Paid'
                       END AS payment_status
                FROM fines
                GROUP BY issue_id
            ) f ON ib.id = f.issue_id
            WHERE 1=1";

    $params = [];

    if ($type === "issued") {
        $sql .= " AND ib.status = 'Issued'";
    } elseif ($type === "returned") {
        $sql .= " AND ib.status = 'Returned'";
    } elseif ($type === "fines") {
        $sql .= " AND f.issue_id IS NOT NULL";
    }

    if ($from !== "") {
        $sql .= " AND ib.issue_date >= ?";
        $params[] = $from;
    }

    if ($to !== "") {
        $sql .= " AND ib.issue_date <= ?";
        $params[] = $to;
    }

    $sql .= " ORDER BY ib.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}

$totalRecords = count($records);
$totalFines = 0;

foreach ($records as $record) {
    $totalFines += (float)$record["fine_amount"];
}

$query = http_build_query([
    "type" => $type,
    "from" => $from,
    "to" => $to
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Library Reports</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef2f7;
            color: #1e293b;
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

        h2 { color: #17365d; }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
            margin: 25px 0;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        label { font-weight: bold; }

        select, input {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        button, .btn {
            padding: 12px 17px;
            border: none;
            border-radius: 6px;
            background: #17365d;
            color: white;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
        }

        .export { background: #16a34a; }

        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 25px 0;
        }

        .stat {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 9px;
            min-width: 200px;
            border-left: 5px solid #17365d;
        }

        .stat h3 {
            font-size: 15px;
            color: #475569;
            margin: 0 0 10px;
        }

        .stat p {
            font-size: 25px;
            font-weight: bold;
            margin: 0;
            color: #17365d;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #17365d;
            color: white;
        }

        tr:hover { background: #f8fafc; }

        .error { color: #dc2626; }

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

    <h2>Library Reports</h2>

    <form method="GET" class="filters">

        <div class="field">
            <label>Report Type</label>

            <select name="type">
                <option value="all"
                    <?php if ($type === "all") echo "selected"; ?>>
                    All Records
                </option>

                <option value="issued"
                    <?php if ($type === "issued") echo "selected"; ?>>
                    Currently Issued
                </option>

                <option value="returned"
                    <?php if ($type === "returned") echo "selected"; ?>>
                    Returned Books
                </option>

                <option value="fines"
                    <?php if ($type === "fines") echo "selected"; ?>>
                    Fine Records
                </option>
            </select>
        </div>

        <div class="field">
            <label>From Issue Date</label>
            <input type="date"
                   name="from"
                   value="<?php echo e($from); ?>">
        </div>

        <div class="field">
            <label>To Issue Date</label>
            <input type="date"
                   name="to"
                   value="<?php echo e($to); ?>">
        </div>

        <button type="submit">Generate Report</button>

        <a href="reports.php" class="btn">
            Reset
        </a>

    </form>

    <?php if ($error): ?>
        <p class="error"><?php echo e($error); ?></p>
    <?php else: ?>

        <div class="stats">

            <div class="stat">
                <h3>Report Records</h3>
                <p><?php echo $totalRecords; ?></p>
            </div>

            <div class="stat">
                <h3>Fine Amount in Report</h3>
                <p>
                    <?php echo number_format($totalFines, 2); ?>
                    BDT
                </p>
            </div>

        </div>

        <a href="export_report.php?<?php echo e($query); ?>"
           class="btn export">
            Export CSV
        </a>

        <p>Showing <?php echo $totalRecords; ?> records</p>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Issue ID</th>
                        <th>Student</th>
                        <th>Roll</th>
                        <th>Book</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Fine (BDT)</th>
                        <th>Payment</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($records): ?>

                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?php echo (int)$row["id"]; ?></td>
                            <td><?php echo e($row["student_name"]); ?></td>
                            <td><?php echo e($row["roll_number"]); ?></td>
                            <td><?php echo e($row["book_name"]); ?></td>
                            <td><?php echo e($row["issue_date"]); ?></td>
                            <td><?php echo e($row["due_date"]); ?></td>
                            <td>
                                <?php echo e($row["return_date"] ?? "-"); ?>
                            </td>
                            <td><?php echo e($row["status"]); ?></td>
                            <td>
                                <?php
                                echo number_format(
                                    (float)$row["fine_amount"], 2
                                );
                                ?>
                            </td>
                            <td><?php echo e($row["payment_status"]); ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="10"
                            style="text-align:center;padding:25px;">
                            No records found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back">
        Back to Dashboard
    </a>

</div>

</body>
</html>
