<?php

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
<!-- Bootstrap CSS -->



<!-- Bootstrap CSS -->

<div class="dropdown mb-3">

    <div class="row">

        <div class="col">
            <div class="d-flex flex-column align-items-start">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" aria-expanded="false">
                    <?php
                    // Dynamically set the button label based on the current page
                    $currentPage = basename($_SERVER['PHP_SELF']);
                    if ($currentPage == 'test_results.php') {
                        echo 'Module Test';
                    } elseif ($currentPage == 'quiz_results.php') {
                        echo 'Subject Quiz';
                    } elseif ($currentPage == 'exam_results.php') {
                        echo 'Exam';
                    } else {
                        echo 'Select Report';
                    }
                    ?>
                </button>
            </div>
        </div>


    </div>



    <ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenuButton">
        <?php
        // Define report types
        $reports = [
            ['title' => 'Module Tests', 'link' => 'test_results.php'],
            ['title' => 'Subject Quizzes', 'link' => 'quiz_results.php'],
            ['title' => 'Exams', 'link' => 'exam_results.php']
        ];

        // Generate dropdown items dynamically
        foreach ($reports as $report) {
        ?>
            <li><a class="dropdown-item" href="<?php echo $report['link']; ?>"><?php echo $report['title']; ?></a></li>
        <?php
        }
        ?>
    </ul>
</div>

<style>
    .dropdown:hover .dropdown-menu {
        display: block;
    }
</style>