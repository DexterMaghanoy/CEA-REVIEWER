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

<style>
    /* Style for the clear button */
    .clear-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        opacity: 0.5;
    }

    /* Style for the clear button icon */
    .clear-btn:hover {
        opacity: 1;
    }
</style>


<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>

        <div class="container">
            <div class="row justify-content-center mt-4">
                <div class="col-md-12">
                    <div class="text-center ">
                        <h1 class="mb-4">Students</h1>
                    </div>
                    <!-- Search bar -->



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

                    <style>
                        .scrollable-tbody {
                            max-height: 300px;
                            overflow-y: auto;
                        }
                    </style>


                    <div class="table-responsive">
                        <!-- <table class="table table-bordered table-custom"> -->
                        <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom" id="courseTable">

                            <caption>List of Students</caption>
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Student No.</th>
                                    <th scope="col">Program</th>
                                    <th scope="col">Fullname</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody class="scrollable-tbody">
                                <?php if ($result->rowCount() > 0) : ?>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <tr>
                                            <td><?php echo $row['stud_no']; ?></td>
                                            <td><?php echo $row['program_name']; ?></td>
                                            <td><?php echo $row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']; ?></td>
                                            <td>
                                                <a href="student_record_test.php?student_id=<?php echo $row['stud_id']; ?>" class="btn btn-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No records found for students.</td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

    <script>
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>


    <script>
        // JavaScript function to clear the search input field and show all student rows
        function clearSearch() {
            document.getElementById('searchInput').value = ''; // Clear the input value
            // Show all student rows
            studentRows.forEach(function(row) {
                row.style.display = '';
            });
        }

        // Add event listener to the clear search button
        document.getElementById('clearSearchButton').addEventListener('click', function() {
            clearSearch(); // Call the clearSearch function when the button is clicked
        });
    </script>


    <script>
        const searchInput = document.getElementById("searchInput");
        const table = document.getElementById("courseTable");
        const tbody = table.querySelector("tbody");

        searchInput.addEventListener("input", function() {
            const filter = searchInput.value.toLowerCase();
            const rows = tbody.querySelectorAll("tr");
            let hasVisibleRow = false;

            rows.forEach(row => {
                // Skip the "no data found" row if it already exists
                if (row.classList.contains("no-data-row")) return;

                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = "";
                    hasVisibleRow = true;
                } else {
                    row.style.display = "none";
                }
            });

            // Remove existing no-data row if it exists
            const existingNoDataRow = tbody.querySelector(".no-data-row");
            if (existingNoDataRow) {
                existingNoDataRow.remove();
            }

            // If no rows are visible, add a "No data found" row
            if (!hasVisibleRow) {
                const noDataRow = document.createElement("tr");
                noDataRow.classList.add("no-data-row");
                noDataRow.innerHTML = `<td colspan="4" class="text-center">No data found.</td>`;
                tbody.appendChild(noDataRow);
            }
        });
    </script>

</body>

</html>