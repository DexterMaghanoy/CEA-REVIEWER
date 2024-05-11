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

$recordsPerPage = 5; // Adjust the number of records per page
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
    $sql .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search OR p.program_name LIKE :search)";
}

$sql .= " LIMIT :offset, :recordsPerPage"; // Add pagination limit

$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);

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
    <title>Course</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar and main content -->
        <?php include 'sidebar.php'; ?>

        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Quiz Report</h1>
                        </div>
                        <?php include 'report_dropdown.php'; ?>
                        <!-- Search bar -->
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div id="courseResults">
                            <!-- Display courses -->
                            <?php if ($result->rowCount() > 0) : ?>
                                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                    <!-- Course card -->
                                    <div class="card mb-1">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title"><?php echo $row['course_name']; ?></h5>
                                                    <p class="card-text"><?php echo $row['course_code']; ?> <?php echo $row['program_name']; ?></p>
                                            </div>
                                            <a href="course_modules.php?program_id=<?php echo $program_id; ?>&course_id=<?php echo $row['course_id']; ?>" class="btn btn-success btn-sm"><i class="lni lni-radio-button"></i> View Modules</a>
                                        </div>
                                    </div>


                                <?php endwhile; ?>
                            <?php else : ?>
                                <!-- No records message -->
                                <div class="card">
                                    <div class="card-body">
                                        <p class="card-text">No records found for course.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
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
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });

        // Function to perform search
        document.getElementById("searchInput").addEventListener("input", function() {
            const searchInput = this.value.toLowerCase();
            const courseResults = document.getElementById("courseResults");
            const courses = courseResults.getElementsByClassName("card");

            // Loop through all course cards, and hide those that do not match the search query
            for (let i = 0; i < courses.length; i++) {
                const courseName = courses[i].getElementsByClassName("card-title")[0].textContent.toLowerCase();
                if (courseName.includes(searchInput)) {
                    courses[i].style.display = "";
                } else {
                    courses[i].style.display = "none";
                }
            }
        });
    </script>
</body>

</html>