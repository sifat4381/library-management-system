
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

// Total book titles
$totalBooks = (int)$conn->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

// Total students
$totalStudents = (int)$conn->query(
    "SELECT COUNT(*) FROM students"
)->fetchColumn();

// Currently issued books
$issuedBooks = (int)$conn->query(
    "SELECT COUNT(*) FROM issued_books
     WHERE status = 'Issued'"
)->fetchColumn();

// Returned book records
$returnedBooks = (int)$conn->query(
    "SELECT COUNT(*) FROM issued_books
     WHERE status = 'Returned'"
)->fetchColumn();

// Total unpaid fine amount
$unpaidFines = (float)$conn->query(
    "SELECT COALESCE(SUM(fine_amount), 0)
     FROM fines
     WHERE payment_status = 'Unpaid'"
)->fetchColumn();

// Total collected fine amount
$collectedFines = (float)$conn->query(
    "SELECT COALESCE(SUM(fine_amount), 0)
     FROM fines
     WHERE payment_status = 'Paid'"
)->fetchColumn();

$adminName = $_SESSION["admin_username"] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Library Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #eef2f7;
            color: #1e293b;
        }

        .header {
            background: #17365d;
            color: white;
            padding: 20px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h2 {
            margin: 0;
        }

        .header p {
            margin: 7px 0 0;
            color: #dbeafe;
        }

        .logout {
            background: #dc2626;
            color: white;
            padding: 11px 20px;
            text-decoration: none;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            color: #17365d;
            margin-bottom: 8px;
        }

        .cards {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-left: 5px solid #17365d;
        }

        .card h3 {
            margin: 0 0 15px;
            color: #475569;
            font-size: 16px;
        }

        .card p {
            margin: 0;
            font-size: 30px;
            font-weight: bold;
            color: #17365d;
        }

        .card.unpaid {
            border-left-color: #dc2626;
        }

        .card.unpaid p {
            color: #dc2626;
        }

        .card.collected {
            border-left-color: #16a34a;
        }

        .card.collected p {
            color: #16a34a;
        }

        .navigation {
            background: white;
            margin-top: 35px;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .navigation h2 {
            color: #17365d;
            margin-top: 0;
        }

        .nav-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .nav-btn {
            display: block;
            background: #17365d;
            color: white;
            text-decoration: none;
            text-align: center;
            padding: 16px;
            border-radius: 7px;
            font-size: 15px;
            transition: 0.2s;
        }

        .nav-btn:hover {
            background: #24558b;
            transform: translateY(-2px);
        }

        .footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .header {
                padding: 20px;
            }

            .container {
                margin-top: 20px;
            }

            .card p {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

    <div class="header">

        <div>
            <h2>Library Management System</h2>
            <p>Admin Dashboard</p>
        </div>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </div>

    <div class="container">

        <div class="welcome">
            <h2>
                Welcome,
                <?php echo e($adminName); ?>!
            </h2>

            <p>
                Manage your library from one place.
            </p>
        </div>

        <div class="cards">

            <div class="card">
                <h3>Total Books</h3>
                <p>
                    <?php echo $totalBooks; ?>
                </p>
            </div>

            <div class="card">
                <h3>Total Students</h3>
                <p>
                    <?php echo $totalStudents; ?>
                </p>
            </div>

            <div class="card">
                <h3>Issued Books</h3>
                <p>
                    <?php echo $issuedBooks; ?>
                </p>
            </div>

            <div class="card">
                <h3>Returned Books</h3>
                <p>
                    <?php echo $returnedBooks; ?>
                </p>
            </div>

            <div class="card unpaid">
                <h3>Unpaid Fines</h3>
                <p>
                    <?php
                    echo number_format(
                        $unpaidFines, 2
                    );
                    ?> BDT
                </p>
            </div>

            <div class="card collected">
                <h3>Collected Fines</h3>
                <p>
                    <?php
                    echo number_format(
                        $collectedFines, 2
                    );
                    ?> BDT
                </p>
            </div>

        </div>

        <div class="navigation">

            <h2>Quick Navigation</h2>

            <div class="nav-grid">

                <a href="add_book.php" class="nav-btn">
                    + Add New Book
                </a>

                <a href="view_books.php" class="nav-btn">
                    View All Books
                </a>

                <a href="add_student.php" class="nav-btn">
                    + Add New Student
                </a>

                <a href="view_students.php" class="nav-btn">
                    View All Students
                </a>

                <a href="issue_book.php" class="nav-btn">
                    Issue Book
                </a>

                <a href="view_issued_books.php" class="nav-btn">
                    View Issued Books
                </a>

                <a href="view_fines.php" class="nav-btn">
                    Fine Management
                </a>
                <a href="reports.php" class="nav-btn">
    Library Reports
</a>

            </div>

        </div>

    
<div class="footer">
    <p>Library Management System</p>
    <p>
        Developed by
        <strong>Sifat Abdullah Mithu</strong>
    </p>
    <p>&copy; 2026 All Rights Reserved.</p>
</div>

</body>
</html>
