<?php
session_start();

require '../api/db-connect.php';

$results = []; // Initialize $results as an empty array

if (isset($_SESSION['program_id']) && isset($_SESSION['year_id'])) {
    $program_id = $_SESSION['program_id'];
    $year_id = $_SESSION['year_id'];

    // Prepare SQL query to fetch courses for the given program and year
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id AND year_id = :year_id AND sem_id = 1";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->bindParam(':year_id', $year_id, PDO::PARAM_INT);
    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect to login page if session data is not set
    header("Location: ../index.php");
    exit();
}

// Check if stud_id and course_id are set
if (isset($_SESSION['stud_id']) && isset($_GET['course_id'])) {
    $stud_id = $_SESSION['stud_id'];
    $course_id = $_GET['course_id'];

    // Define pagination variables
    $recordsPerPage = 10;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $recordsPerPage;

    // Define search term
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Prepare SQL query to fetch student scores for the specified course
    $sql = "SELECT tbl_result.result_score, tbl_result.total_questions, tbl_module.module_number, tbl_module.module_name, tbl_result.created_at as date_created
    FROM tbl_result
    INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
    WHERE tbl_result.stud_id = :stud_id 
    AND tbl_module.course_id = :course_id
    AND tbl_result.quiz_type = 1";

    // Add search condition if applicable
    if (!empty($search)) {
        $sql .= " AND (tbl_module.module_number LIKE :search OR tbl_module.module_name LIKE :search)";
    }

    // Add pagination limit
    $sql .= " LIMIT :offset, :recordsPerPage";

    // Prepare and execute the query
    $result = $conn->prepare($sql);
    $result->bindParam(':stud_id', $stud_id);
    $result->bindParam(':course_id', $course_id);
    $result->bindParam(':offset', $offset, PDO::PARAM_INT);
    $result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);

    // Bind search parameter if applicable
    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }

    $result->execute();

    // Fetch all results
    $results = $result->fetchAll(PDO::FETCH_ASSOC);
}

// Count total number of records if results are not empty
$totalPages = 0;
if (!empty($results)) {
    $countSql = "SELECT COUNT(*) as total FROM tbl_result 
    INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
    WHERE tbl_result.stud_id = :stud_id 
    AND tbl_module.course_id = :course_id
    AND tbl_result.quiz_type = 1";

    // Add search condition if applicable
    if (!empty($search)) {
        $countSql .= " AND (tbl_module.module_number LIKE :search OR tbl_module.module_name LIKE :search)";
    }

    // Prepare and execute the count query
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':stud_id', $stud_id);
    $countStmt->bindParam(':course_id', $course_id);

    // Bind search parameter if applicable
    if (!empty($search)) {
        $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCount / $recordsPerPage);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">

</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-2">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Your Result</h1>
                        </div>

                        <!-- Add the following HTML code inside the <div class="main p-3"> element, before the table -->

                        <!-- Search bar -->
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <!-- Display student scores in a table -->
                        <div class="table-responsive">
                            <table id="resultTable" class="table table-bordered border-secondary">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <th scope="col">Module No.</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Score</th>
                                        <th scope="col">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $row) : ?>
                                        <tr style="text-align: center;">
                                            <td><?php echo $row['module_number']; ?></td>
                                            <td><?php echo $row['module_name']; ?></td>
                                            <td><?php echo $row['result_score']; ?> / <?php echo $row['total_questions']; ?></td>
                                            <td><?php echo date("M d, Y", strtotime($row['date_created'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($results)) : ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No Record</td>
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
                                        <a class="page-link" href="?page=<?php echo $i; ?>&course_id=<?php echo $course_id; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
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
<!-- Add the following script before the closing </body> tag -->

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById("searchInput");
        const resultTable = document.getElementById("resultTable").getElementsByTagName("tbody")[0].getElementsByTagName("tr");

        searchInput.addEventListener("input", function() {
            const searchQuery = this.value.toLowerCase();

            // Loop through each table row and hide/show based on the search query
            Array.from(resultTable).forEach(function(row) {
                const moduleNumber = row.cells[0].textContent.toLowerCase();
                const moduleName = row.cells[1].textContent.toLowerCase();

                // Check if the search query matches any part of module number or module name
                if (moduleNumber.includes(searchQuery) || moduleName.includes(searchQuery)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    });
</script>


</html>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
<script>
    const hamBurger = document.querySelector(".toggle-btn");
    const sidebar = document.querySelector("#sidebar");
    const mainContent = document.querySelector(".main");

    hamBurger.addEventListener("click", function() {
        sidebar.classList.toggle("expand");
        mainContent.classList.toggle("expand");
    });
</script>