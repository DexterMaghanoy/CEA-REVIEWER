<?php
session_start();

require '../api/db-connect.php';

// Pagination
$resultsPerPage = 10; // Number of results per page
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Retrieve the program ID, course ID, and module ID from the session or URL parameter
    $program_id = $_SESSION['program_id'];
    $course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
    $module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

    // Query with search functionality
    $sql = "SELECT r.stud_id, s.stud_fname, s.stud_mname, s.stud_lname, c.course_name, m.module_name, SUM(r.result_score) AS total_score
            FROM tbl_result r
            INNER JOIN tbl_student s ON r.stud_id = s.stud_id
            INNER JOIN tbl_module m ON r.module_id = m.module_id
            INNER JOIN tbl_course c ON m.course_id = c.course_id
            WHERE c.program_id = :program_id"; // Filter by program ID

    // Add conditions to filter by course ID and module ID if they are provided
    if (!is_null($course_id)) {
        $sql .= " AND m.course_id = :course_id";
    }
    if (!is_null($module_id)) {
        $sql .= " AND r.module_id = :module_id";
    }

    if (!empty($search)) {
        $sql .= " AND (s.stud_fname LIKE :search OR s.stud_lname LIKE :search OR c.course_name LIKE :search OR m.module_name LIKE :search)";
    }

    $sql .= " GROUP BY r.stud_id, c.course_id ";

    // Count total number of results for pagination
    $countQuery = "SELECT COUNT(*) AS count FROM ($sql) AS sub";
    $stmtCount = $conn->prepare($countQuery);
    if (!empty($search)) {
        $stmtCount->bindValue(':search', '%' . $search . '%');
    }
    $stmtCount->bindValue(':program_id', $program_id);
    if (!is_null($course_id)) {
        $stmtCount->bindValue(':course_id', $course_id);
    }
    if (!is_null($module_id)) {
        $stmtCount->bindValue(':module_id', $module_id);
    }
    $stmtCount->execute();
    $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $totalCount = $countResult['count'];
    $totalPages = ceil($totalCount / $resultsPerPage);

    // Add pagination to the main query
    $sql .= " LIMIT $resultsPerPage OFFSET $offset";

    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    $stmt->bindValue(':program_id', $program_id);
    if (!is_null($course_id)) {
        $stmt->bindValue(':course_id', $course_id);
    }
    if (!is_null($module_id)) {
        $stmt->bindValue(':module_id', $module_id);
    }
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    header("Location: ../index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report</title>
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
                            <h1>Student Report</h1>
                        </div>
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Student</th>
                                        <th scope="col">Course</th>
                                        <th scope="col">Total Score</th>
                                        <th scope="col">Attempts</th>
                                        <th scope="col">Pass Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($result)) : ?>
                                        <?php foreach ($result as $row) : ?>
                                            <tr>
                                                <td><?php echo $row['stud_lname'] . ', ' . $row['stud_fname'] ?></td>
                                                <td><?php echo $row['course_name']; ?></td>
                                                <td><?php echo $row['total_score']; ?></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No records found for students.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        </div>
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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>


</html>

<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>