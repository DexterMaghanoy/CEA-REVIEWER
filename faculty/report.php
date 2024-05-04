<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../login.php");
    exit();
}

$recordsPerPage = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query
// Build the SQL query
$sql = "SELECT c.*, p.program_name
        FROM tbl_course AS c
        JOIN tbl_program AS p ON c.program_id = p.program_id";


if (!empty($search)) {
    $searchParam = '%' . $search . '%';
    $sql .= " WHERE (c.course_code LIKE :search OR c.course_name LIKE :search OR p.program_name LIKE :search) AND c.user_id = :user_id";
} else {
    $sql .= " WHERE c.user_id = :user_id";
}

$result = $conn->prepare($sql);
$result->bindParam(':user_id', $user_id, PDO::PARAM_INT);
if (!empty($search)) {
    $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
$result->execute();

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_course WHERE user_id = :user_id";
if (!empty($search)) {
    $countSql .= " AND (course_code LIKE :search OR course_name LIKE :search)";
}

$countStmt = $conn->prepare($countSql);
$countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
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

<style>
    .card-link,
    .card-link:hover,
    .card-link:focus {
        color: black !important;
        text-decoration: none;
    }
</style>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Report</h1>
                        </div>
                        <!-- Centered row for the cards -->
                        <div class="row justify-content-center">
                            <?php
                            // Define card data
                            $cards = [
                                ['title' => 'Module Tests', 'description' => 'View module tests results.', 'link' => 'test_results.php'],
                                ['title' => 'Subject Quizzes', 'description' => 'View subject quizzes results.', 'link' => 'quiz_results.php'],
                                ['title' => 'Course Exams', 'description' => 'View course exams results.', 'link' => 'exam_results.php']
                            ];

                            // Loop through card data and generate cards dynamically
                            foreach ($cards as $card) {
                            ?>
                                <div class="col-md-8">
                                    <a href="<?php echo $card['link']; ?>" class="card-link">
                                        <div class="card mb-3">
                                            <div class="card-body text-black">
                                                <h5 class="card-title"><?php echo $card['title']; ?></h5>
                                                <p class="card-text"><?php echo $card['description']; ?></p>
                                                <a href="<?php echo $card['link']; ?>" class="btn btn-primary float-end">
                                                    <i class="lni lni-arrow-right-circle"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php
                            }
                            ?>
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