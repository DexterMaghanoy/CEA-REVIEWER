<?php
session_start();

require '../api/db-connect.php';

// Check if program_id is set in the session
if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
    $stud_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

    if ($stud_id) {
        // Prepare SQL query to fetch results for the given student, program, and include module_name and created_at
        $sql = "SELECT tbl_result.*, tbl_module.module_name, tbl_result.created_at
                FROM `tbl_result` 
                INNER JOIN `tbl_module` ON tbl_result.module_id = tbl_module.module_id
                WHERE tbl_result.stud_id = :stud_id AND tbl_result.program_id = :program_id AND tbl_result.quiz_type = 3";

        // Prepare and execute the SQL query
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
        $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch results
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            $error_message = "No results found for the selected student.";
        }
    } else {
        $error_message = "Student ID is missing.";
    }
} else {
    // Redirect to login page if session data is not set
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
    <title>Result</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<style>
    .table {
    border-radius: 10px; /* Adjust the value as needed */
    overflow: hidden; /* Ensures the border-radius is applied to the table */
}


.table th {
    cursor: pointer;
    transition: background-color 0.1s ease;
    padding: 10px 15px;
    border-radius: 2px;
}

.table th:hover {
    background-color: #f0f0f0;
}

.table th:active {
    background-color: #d0d0d0;
}


.table th:active {
    animation: jelly 0.3s ease;
}

@keyframes jelly {
    0% { transform: scale(1,1); }
    25% { transform: scale(1.1,.9); }
    50% { transform: scale(1.1,.9); }
    75% { transform: scale(1,.9); }
    100% { transform: scale(1,1); }
}
</style>

<body>
    <!-- Body content goes here -->
    <div class="wrapper">
        <?php include 'sidebar.php'; ?> <!-- Assuming sidebar.php contains your sidebar code -->
            <div class="container">
            <?php include 'back.php'; ?>
                <div class="row justify-content-center mt-1">
                    <div class="col-md-12">
                        <div class="text-center mb-2">
                            <h1>All Results</h1>
                        </div>
                        <!-- Search Bar -->

                        

                        <?php include 'student_record_dropdown.php'; ?>
                        <form action="" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                            </div>
                        </form>

                        <!-- Display all results in a table -->
                        <div class="table-responsive" >
                            <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3)); table-layout: auto; width: 100%;" class="table table-bordered table-custom">

                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <!-- Wrap each th inside an <a> tag for clickability -->
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
<!-- Add this JavaScript code within <script> tags at the bottom of your HTML file -->

<script>
    // Function to filter table rows based on input text
    // Function to filter table rows based on input text
    function filterTable() {
        const searchInput = document.getElementById("searchInput");
        const value = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll("#resultTable tbody tr");

        rows.forEach(row => {
            const module_name = row.querySelector("td:nth-child(1)").textContent.toLowerCase();
            const status = row.querySelector("td:nth-child(3)").textContent.toLowerCase(); // Assuming status is in the third column
            if (module_name.includes(value) || status.includes(value)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }


    // Function to clear search input and show all rows
    function clearSearch() {
        const searchInput = document.getElementById("searchInput");
        searchInput.value = "";
        filterTable(); // Call filterTable to show all rows
    }

    // Attach event listeners to search input and clear button
    document.getElementById("searchInput").addEventListener("keyup", filterTable);
    document.getElementById("clearSearchButton").addEventListener("click", clearSearch);
</script>



<script>
    // Function to sort table rows based on the clicked header
    function sortTable(columnIndex) {
        const table = document.getElementById("resultTable");
        const rows = Array.from(table.querySelectorAll("tbody tr"));

        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            // Numeric sorting
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return aValue - bValue;
            }

            // String sorting
            return aValue.localeCompare(bValue);
        });

        // Clear existing table rows
        table.querySelector("tbody").innerHTML = "";

        // Append sorted rows
        rows.forEach(row => {
            table.querySelector("tbody").appendChild(row);
        });
    }

    // Attach event listeners to table headers for sorting
    document.querySelectorAll("#resultTable thead th").forEach((th, index) => {
        th.addEventListener("click", () => {
            sortTable(index);
        });
    });
</script>