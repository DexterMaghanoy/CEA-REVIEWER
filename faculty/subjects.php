<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$recordsPerPage = 7;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality
$sql = "SELECT c.*, p.program_name
        FROM tbl_course AS c
        JOIN tbl_program AS p ON c.program_id = p.program_id
        WHERE c.program_id = :program_id";

if (!empty($search)) {
    $searchParam = '%' . $search . '%';
    $sql .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search OR p.program_name LIKE :search OR s.sem_name LIKE :search)";
}

$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);

if (!empty($search)) {
    $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
$result->execute();

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_course WHERE program_id = :program_id";
if (!empty($search)) {
    $countSql .= " AND (course_code LIKE :search OR course_name LIKE :search)";
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
    <title>Subjects</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <style>
        /* Custom CSS for the green button */
        .add-module-btn {
            margin-bottom: 10px;
            /* Adjust spacing between button and search bar */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Subjects</h1>
                        </div>
                        <!-- Add Module button -->
                        <!-- <a href="add_module.php" class="btn btn-success add-module-btn"><i class="lni lni-plus"></i></a> -->
                        <!-- Search bar -->
                        <form id="searchForm" action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input id="searchInput" type="text" class="form-control" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table id="courseTable" class="table table-bordered border-secondary">
                                <caption>List of Course</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Program</th>
                                        <th scope="col">Code</th>
                                        <th scope="col">Subject</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->rowCount() > 0) : ?>
                                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                            <tr>
                                                <td><?php echo $row['program_name']; ?></td>
                                                <td><?php echo $row['course_code']; ?></td>
                                                <td><?php echo $row['course_name']; ?></td>
                                                <td>
                                                    <a class="btn btn-success btn-sm" href="add_module.php?course_id=<?php echo $row['course_id']; ?>"><i class="lni lni-upload"></i></a>
                                                    <a class="btn btn-primary btn-sm" href="view_module.php?course_id=<?php echo $row['course_id']; ?>"><i class="lni lni-radio-button"></i></a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No records found for course.</td>
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
        const courseTable = document.getElementById('courseTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            Array.from(courseTable).forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
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