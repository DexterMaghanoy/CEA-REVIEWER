<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

// Check if stud_id and course_id are set
if (isset($_GET['stud_id']) && isset($_GET['course_id'])) {
    $stud_id = $_GET['stud_id'];
    $course_id = $_GET['course_id'];

    // Define pagination variables
    $recordsPerPage = 5;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $recordsPerPage;

    // Define search term
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Prepare SQL query to fetch student scores for the specified course
    $sql = "SELECT tbl_result.result_score, tbl_module.course_id, tbl_module.module_number, tbl_module.module_name
            FROM tbl_result
            INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
            WHERE tbl_result.stud_id = :stud_id AND tbl_module.course_id = :course_id";

    // Prepare and execute the query
    $result = $conn->prepare($sql);
    $result->bindParam(':stud_id', $stud_id);
    $result->bindParam(':course_id', $course_id);
    $result->execute();

    // Fetch all results
    $results = $result->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records
    $countSql = "SELECT COUNT(*) as total FROM tbl_result 
                 INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
                 WHERE tbl_result.stud_id = :stud_id AND tbl_module.course_id = :course_id";

    // Add search condition if applicable
    if (!empty($search)) {
        $countSql .= " AND (stud_lname LIKE '%$search%' OR stud_fname LIKE '%$search%')";
    }

    // Prepare and execute the count query
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':stud_id', $stud_id);
    $countStmt->bindParam(':course_id', $course_id);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCount / $recordsPerPage);
} else {
    // Handle case when stud_id or course_id is not set
    // Redirect or display an error message
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result </title>
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
                            <h1>Student Result</h1>
                        </div>
                        <!-- Display student scores in a table -->
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Module No.</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->rowCount() > 0) : ?>
                                        <?php foreach ($results as $row) : ?>
                                            <tr>
                                                <td><?php echo $row['module_number']; ?></td>
                                                <td><?php echo $row['module_name']; ?></td>
                                                <td><?php echo $row['result_score']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No records found for this course.</td>
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
</body>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>

</html>