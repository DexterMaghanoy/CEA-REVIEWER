<?php
session_start();
require("../api/db-connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$program_id = $_SESSION['program_id'];

// Ensure $user_program is defined
if (!isset($_SESSION['program_id'])) {
    // Handle the case where the user's program is not found
    // Redirect or display an error message
}

$recordsPerPage = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality and filtering by program_id
$sql = "SELECT s.*, p.program_name
        FROM tbl_student AS s
        JOIN tbl_program AS p ON s.program_id = p.program_id
        WHERE s.program_id = :program_id";

if (!empty($search)) {
    $sql .= " AND (s.stud_lname LIKE :search OR s.stud_fname LIKE :search OR s.stud_mname LIKE :search OR s.stud_no LIKE :search OR p.program_name LIKE :search)";
}

$sql .= " ORDER BY s.stud_status DESC LIMIT :offset, :recordsPerPage";

$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);

if (!empty($search)) {
    $searchParam = "%$search%";
    $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$result->execute();

// Count total number of records with the same program_id
$countSql = "SELECT COUNT(*) as total FROM tbl_student WHERE program_id = :program_id";
if (!empty($search)) {
    $countSql .= " AND (stud_lname LIKE :search OR stud_fname LIKE :search)";
}

$countStmt = $conn->prepare($countSql);
$countStmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);

if (!empty($search)) {
    $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$countStmt->execute();
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $recordsPerPage);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">

</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>

        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Students<?php $user['program_id'] ?></h1>
                        </div>
                        <!-- Search bar -->
                        <form id="searchForm" action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input id="searchInput" type="text" class="form-control" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of Student</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Student No.</th>
                                        <th scope="col">Program</th>
                                        <th scope="col">Fullname</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->rowCount() > 0) : ?>
                                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                            <tr>
                                                <td><?php echo $row['stud_no']; ?></td>
                                                <td><?php echo $row['program_name']; ?></td>
                                                <td><?php echo $row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No records found for student.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const studentRows = document.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', function() {
            const searchText = this.value.trim().toLowerCase();
            studentRows.forEach(function(row) {
                const studentData = row.textContent.toLowerCase();
                if (studentData.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>
</body>

</html>