<?php
session_start();

require '../api/db-connect.php';
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
$stud_id = isset($_SESSION['stud_id']) ? $_SESSION['stud_id'] : null; // Retrieve stud_id from session


// Your original SQL query
$sql = "SELECT tbl_result.result_score, tbl_result.total_questions, tbl_module.module_name, tbl_result.created_at as date_created
        FROM tbl_result
        INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
        WHERE tbl_result.quiz_type = 1 AND tbl_result.course_id = '$course_id' AND tbl_result.stud_id = '$stud_id'";


// Execute the SQL query
$result = $conn->query($sql);

// Check if there are no records
if ($result->rowCount() == 0) {
} else {
    $results = $result->fetchAll(PDO::FETCH_ASSOC);
}
global $results;
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
    <!-- Body content goes here -->
    <div class="wrapper">
        <?php include 'sidebar.php'; ?> <!-- Assuming sidebar.php contains your sidebar code -->
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-2">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>All Results</h1>
                        </div>

                        <!-- Search Bar -->
                        <form action="" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                            </div>
                        </form>

                        <!-- Display all results in a table -->
                        <div class="table-responsive">
                            <table id="resultTable" class="table table-bordered border-secondary">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <!-- <th scope="col">Module No.</th> -->
                                        <th scope="col">Title</th>
                                        <th scope="col">Score</th>
                                        <th scope="col">Result</th>
                                        <th scope="col">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($results) : ?>
                                        <?php foreach ($results as $row) : ?>
                                            <tr style="text-align: center;">
                                                <td><?php echo $row['module_name']; ?></td>
                                                <td><?php echo $row['result_score']; ?> / <?php echo $row['total_questions']; ?></td>
                                                <td scope="col">

                                                    <?php
                                                    $res = ($row['result_score'] / $row['total_questions']) * 100;
                                                    if ($res >= 50) {
                                                        echo "Pass";
                                                    } else {
                                                        echo "Failed";
                                                    }
                                                    ?>

                                                </td>
                                                <td><?php echo date("M d, Y", strtotime($row['date_created'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
<script>
    const hamBurger = document.querySelector(".toggle-btn");
    const sidebar = document.querySelector("#sidebar");
    const mainContent = document.querySelector(".main");
    const searchInput = document.getElementById("searchInput");
    const clearSearchButton = document.getElementById("clearSearchButton");

    hamBurger.addEventListener("click", function() {
        sidebar.classList.toggle("expand");
        mainContent.classList.toggle("expand");
    });

    // Function to toggle clear button
    function toggleClearButton() {
        if (searchInput.value !== "") {
            clearSearchButton.style.display = "block";
        } else {
            clearSearchButton.style.display = "block";
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