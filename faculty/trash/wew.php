<?php
session_start();

require '../api/db-connect.php';

// Initialize variables
$program_id = isset($_SESSION['program_id']) ? $_SESSION['program_id'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;

// Retrieve modules for the specified course
if ($course_id) {
    $stmt = $conn->prepare("SELECT * FROM tbl_module WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect if course ID is not provided
}

// Pagination
$resultsPerPage = 10; // Number of results per page
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Retrieve module ID from URL parameter
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

if ($user_id) {
    // Query with search functionality
    $sql = "SELECT r.stud_id, s.stud_fname, s.stud_mname, s.stud_lname, c.course_name, m.module_name, r.created_at, SUM(r.result_score) AS total_score
    FROM tbl_result r
    INNER JOIN tbl_student s ON r.stud_id = s.stud_id
    INNER JOIN tbl_module m ON r.module_id = m.module_id
    INNER JOIN tbl_course c ON m.course_id = c.course_id
    WHERE c.program_id = :program_id"; // Filter by program ID


    // Add conditions to filter by course ID and module ID if they are provided
    if ($course_id) {
        $sql .= " AND m.course_id = :course_id";
    }
    if ($module_id) {
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
    if ($course_id) {
        $stmtCount->bindValue(':course_id', $course_id);
    }
    if ($module_id) {
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
    if ($course_id) {
        $stmt->bindValue(':course_id', $course_id);
    }
    if ($module_id) {
        $stmt->bindValue(':module_id', $module_id);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch the results

} else {
    // Redirect if user ID is not set in the session
}
?>


<?php

if (isset($_SESSION['program_id'])) {

    $program_id = $_SESSION['program_id'];

    // Prepare SQL query to fetch courses for the given program and year
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    
    // Redirect to login page if session data is not set
    header("Location: ../index.php");
    exit();

}

// Retrieve values from URL parameters
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;

// Your original SQL query to fetch student performance data
$sql = "SELECT 
            s.stud_id, 
            s.stud_fname, 
            s.stud_mname, 
            s.stud_lname, 
            r.module_id, 
            r.result_score, 
            r.total_questions, 
            r.created_at as date_created,
            m.module_name,
            r.quiz_type
        FROM 
            tbl_student s
        LEFT JOIN 
            tbl_result r ON s.stud_id = r.stud_id
        LEFT JOIN 
            tbl_module m ON r.module_id = m.module_id AND r.course_id = m.course_id
        WHERE 
            s.stud_id IS NOT NULL 
            AND r.quiz_type = 1 
            AND r.course_id = :course_id";

// Execute the SQL query
$stmt = $conn->prepare($sql);
$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->execute();

// Fetch the results
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Performance</title>
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
                            <h1>Student Performance</h1>
                        </div>
                        <?php include 'module_dropdown.php'; ?>
                        <!-- Search Bar -->
                        <form action="" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                            </div>
                        </form>

                        <!-- Table to display student performance -->
                        <div class="table-responsive">
                            <table id="resultTable" class="table table-bordered border-secondary">
                                <caption>List of Student Performance</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <th scope="col">Student Name</th>
                                        <th scope="col">Module Name</th>
                                        <th scope="col">Questions Answered</th>
                                        <th scope="col">Score</th>
                                        <th scope="col">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $row) : ?>
                                        <tr style="text-align: center;">
                                            <td><?php echo $row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']; ?></td>
                                            <td><?php echo $row['module_name']; ?></td>
                                            <td><?php echo $row['total_questions']; ?></td>
                                            <td><?php echo $row['result_score']; ?></td>
                                            <td><?php echo date("M d, Y", strtotime($row['date_created'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });

        // Function to toggle clear button
        function toggleClearButton() {
            if (searchInput.value !== "") {
                clearSearchButton.style.display = "block";
            } else {
                clearSearchButton.style.display = "none";
            }
        }

        // Toggle clear button on page load
        toggleClearButton();

        // JavaScript for filtering table data
        searchInput.addEventListener("keyup", function() {
            toggleClearButton();
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll("#resultTable tbody tr");

            rows.forEach(row => {
                const module_name = row.children[1].textContent.toLowerCase();
                if (module_name.includes(value)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        // Clear search input
        clearSearchButton.addEventListener("click", function() {
            searchInput.value = "";
            toggleClearButton();
            const rows = document.querySelectorAll("#resultTable tbody tr");
            rows.forEach(row => {
                row.style.display = "";
            });
        });
    </script>
</body>

</html>
