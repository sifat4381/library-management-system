
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

if ($search !== "") {

    $sql = "SELECT * FROM students
            WHERE student_name LIKE ?
               OR roll_number LIKE ?
               OR department LIKE ?
               OR semester LIKE ?
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
        "SELECT * FROM students ORDER BY id DESC"
    );
}

$students = $stmt->fetchAll();

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

    <title>View Students - Library Management</title>

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

        <h2>Student Management</h2>
        
<?php if (isset($_GET["deleted"])): ?>
    <p style="color:green;">
        Student deleted successfully!
    </p>
<?php endif; ?>

<?php if (($_GET["error"] ?? "") === "history"): ?>
    <p style="color:red;">
        Cannot delete a student with book issue history!
    </p>
<?php endif; ?>

<?php if (($_GET["error"] ?? "") === "notfound"): ?>
    <p style="color:red;">
        Student not found!
    </p>
<?php endif; ?>

<?php if (($_GET["error"] ?? "") === "delete"): ?>
    <p style="color:red;">
        Unable to delete student.
    </p>
<?php endif; ?>

        
<?php if (isset($_GET["updated"])): ?>
    <p style="color:green;">
        Student updated successfully!
    </p>
<?php endif; ?>


        <div class="top-bar">

            <form method="GET" class="search-form">

                <input type="text"
                       name="search"
                       placeholder="Search students..."
                       value="<?php echo e($search); ?>">

                <button type="submit">
                    Search
                </button>

                <a href="view_students.php" class="btn">
                    Reset
                </a>

            </form>

            <a href="add_student.php"
               class="btn btn-green">
                + Add New Student
            </a>

        </div>

        <p class="count">
            Students Found:
            <strong><?php echo count($students); ?></strong>
        </p>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($students) > 0): ?>

                    <?php foreach ($students as $student): ?>

                    <tr>
                        <td>
                            <?php echo (int)$student["id"]; ?>
                        </td>

                        <td>
                            <?php echo e($student["student_name"]); ?>
                        </td>

                        <td>
                            <?php echo e($student["roll_number"]); ?>
                        </td>

                        <td>
                            <?php echo e($student["department"]); ?>
                        </td>

                        <td>
                            <?php echo e($student["semester"]); ?>
                        </td>

                        <td>
                            <?php echo e($student["phone"]); ?>
                        </td>

                        <td>
                            <?php echo e($student["email"]); ?>
                        </td>
                        

<td>
    <a href="edit_student.php?id=<?php echo (int)$student['id']; ?>"
       style="background:#2563eb;
              color:white;
              padding:8px 15px;
              border-radius:5px;
              text-decoration:none;
              display:inline-block;
              margin-bottom:5px;">
        Edit
    </a>

    <form action="delete_student.php"
          method="POST"
          style="display:inline;"
          onsubmit="return confirm('Are you sure you want to delete this student?');">

        <input type="hidden"
               name="id"
               value="<?php echo (int)$student['id']; ?>">

        <input type="hidden"
               name="csrf_token"
               value="<?php echo e($_SESSION['csrf_token']); ?>">

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
                            No students found!
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
