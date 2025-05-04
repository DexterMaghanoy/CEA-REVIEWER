<?php
session_start();

require '../api/db-connect.php';

// Check if program_id is set in the session
if (!isset($_SESSION['program_id'])) {
    // Redirect to login page if session data is not set
    header("Location: ../index.php");
    exit();
}

$program_id = $_SESSION['program_id'];
$stud_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

if (!$stud_id) {
    $error_message = "Student ID is missing.";
} else {
    // Prepare SQL query to fetch results for the given student, program, and include module_name and created_at
    $sql = "SELECT tbl_result.*, tbl_module.module_name, tbl_result.created_at
            FROM `tbl_result` 
            INNER JOIN `tbl_module` ON tbl_result.module_id = tbl_module.module_id
            WHERE tbl_result.stud_id = :stud_id AND tbl_result.program_id = :program_id AND tbl_result.quiz_type = 1";

    // Additional condition for search
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    if ($search) {
        $sql .= " AND (tbl_module.module_name LIKE :search)";
    }

    // Prepare and execute the SQL query
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);

    // Bind search parameter if it exists
    if ($search) {
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }

    $stmt->execute();

    // Fetch results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$results) {
        $error_message = "No results found for the selected student.";
    }

    // Now, let's fetch the student's first name and last name
    $sql_student = "SELECT stud_fname, stud_lname FROM `tbl_student` WHERE stud_id = :stud_id";
    $stmt_student = $conn->prepare($sql_student);
    $stmt_student->bindParam(':stud_id', $_GET['student_id'], PDO::PARAM_INT);
    $stmt_student->execute();

    // Fetch student details
    $student_details = $stmt_student->fetch(PDO::FETCH_ASSOC);
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
        <div class="container">
            <div class="row justify-content-center mt-4">
                <div class="col-md-12">
                    <div class="text-center mb-4">
                        <h1>TEST Results: <span style="font-weight: normal;"><?php echo $student_details['stud_fname'] . " " . $student_details['stud_lname'] ?></span></h1>
                    </div>
                    <?php include 'student_record_dropdown.php'; ?>
                    <form action="" method="GET" class="mb-4" id="searchForm">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                        </div>
                    </form>
                    <script>
                        const searchForm = document.getElementById("searchForm");

                        searchForm.addEventListener("submit", function(event) {
                            event.preventDefault(); // Prevent the form from submitting
                        });
                    </script>



                    <!-- Display all results in a table -->
                    <div class="table-responsive">
                        <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                            <caption>List of Scores</caption>
                            <thead class="table-dark">
                                <tr style="text-align: center;">
                                    <th scope="col"><a href="#" class="sortable" data-column="0">Title</a></th>
                                    <th scope="col"><a href="#" class="sortable" data-column="1">Score</a></th>
                                    <th scope="col"><a href="#" class="sortable" data-column="2">Result</a></th>
                                    <th scope="col"><a href="#" class="sortable" data-column="3">Date</a></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($results)) : ?>
                                    <?php foreach ($results as $row) : ?>
                                        <tr style="text-align: center;">
                                            <td><?php echo isset($row['module_name']) ? $row['module_name'] : 'N/A'; ?></td>
                                            <td><?php echo $row['result_score'] ?? 'N/A'; ?> / <?php echo $row['total_questions'] ?? 'N/A'; ?></td>
                                            <td scope="col">
                                                <?php
                                                if (isset($row['result_score'], $row['total_questions'])) {
                                                    $res = ($row['result_score'] / $row['total_questions']) * 100;
                                                    echo $res >= 50 ? "Pass" : "Failed";
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo isset($row['created_at']) ? date("M d, Y", strtotime($row['created_at'])) : 'N/A'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="4" class="text-center">No results found.</td>
                                    </tr>
                                <?php else : ?>
                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="4" class="text-center">No results found.</td>
                                    </tr>

                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const searchInput = document.getElementById("searchInput");
        const clearSearchButton = document.getElementById("clearSearchButton");
        const noResultsRow = document.getElementById("noResultsRow");

        searchInput.addEventListener("keyup", function() {
            const value = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll("tbody tr:not(#noResultsRow)");
            let visibleRowCount = 0;

            rows.forEach(row => {
                const moduleName = row.children[0].textContent.toLowerCase();
                if (moduleName.includes(value)) {
                    row.style.display = "";
                    visibleRowCount++;
                } else {
                    row.style.display = "none";
                }
            });

            // Show or hide "No results" row
            noResultsRow.style.display = visibleRowCount === 0 ? "" : "none";
        });

        clearSearchButton.addEventListener("click", function() {
            searchInput.value = "";
            const rows = document.querySelectorAll("tbody tr:not(#noResultsRow)");

            rows.forEach(row => {
                row.style.display = "";
            });

            // Hide the "No results" row after clearing
            noResultsRow.style.display = "none";
        });
    </script>
</body>

</html>

<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>