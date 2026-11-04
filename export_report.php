
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$type = $_GET["type"] ?? "all";
$from = $_GET["from"] ?? "";
$to = $_GET["to"] ?? "";

$allowedTypes = ["all", "issued", "returned", "fines"];

if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    exit("Invalid report type.");
}

function validDate($date) {
    if ($date === "") return true;

    $d = DateTimeImmutable::createFromFormat(
        "!Y-m-d", $date
    );

    return $d && $d->format("Y-m-d") === $date;
}

if (
    !validDate($from) ||
    !validDate($to) ||
    ($from !== "" && $to !== "" && $from > $to)
) {
    http_response_code(400);
    exit("Invalid date range.");
}

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

$filename = "library_report_" .
            $type . "_" .
            date("Ymd_His") . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header(
    "Content-Disposition: attachment; filename=\"" .
    $filename . "\""
);
header("Cache-Control: no-store");

// Write CSV directly to download
$output = fopen("php://output", "w");

// UTF-8 BOM for Excel compatibility
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "Issue ID",
    "Student Name",
    "Roll Number",
    "Book Name",
    "Issue Date",
    "Due Date",
    "Return Date",
    "Status",
    "Fine Amount (BDT)",
    "Payment Status"
]);

// Prevent spreadsheet formula injection
function safeCsv($value) {
    $value = (string)($value ?? "");

    if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {
        return "'" . $value;
    }

    return $value;
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    fputcsv($output, [
        (int)$row["id"],
        safeCsv($row["student_name"]),
        safeCsv($row["roll_number"]),
        safeCsv($row["book_name"]),
        safeCsv($row["issue_date"]),
        safeCsv($row["due_date"]),
        safeCsv($row["return_date"] ?? ""),
        safeCsv($row["status"]),
        number_format((float)$row["fine_amount"], 2, ".", ""),
        safeCsv($row["payment_status"])
    ]);
}

fclose($output);
exit;
?>
