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

$sql = "SELECT tbl_result.result_score, tbl_result.total_questions, tbl_module.module_name, tbl_result.created_at as date_created, tbl_result.attempt_id
        FROM tbl_result
        INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
        WHERE tbl_result.quiz_type = 3 AND tbl_result.stud_id = :stud_id
        ORDER BY tbl_result.created_at DESC";
$result = $conn->prepare($sql);
$result->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
$result->execute();

$results = $result->fetchAll(PDO::FETCH_ASSOC);

$courseName = "";
foreach ($courses as $course) {
    if ($course['course_id'] == $course_id) {
        $courseName = $course['course_name'];
        break;
    }
}

$sqlCourseNames = "SELECT course_id, course_name
                   FROM tbl_course
                   WHERE program_id = :program_id and course_status = 1";
$stmtCourseNames = $conn->prepare($sqlCourseNames);
$stmtCourseNames->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtCourseNames->execute();
$courseNames = $stmtCourseNames->fetchAll(PDO::FETCH_ASSOC);


$queryModuleQuiz = "SELECT DISTINCT m.module_id, m.module_name, qa.attempt_id
                    FROM tbl_module m
                    INNER JOIN tbl_quiz_answers qa ON m.module_id = qa.module_id
                    WHERE qa.course_id = :course_id";
$stmtModuleQuiz = $conn->prepare($queryModuleQuiz);
$stmtModuleQuiz->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtModuleQuiz->execute();
$moduleQuiz = $stmtModuleQuiz->fetchAll(PDO::FETCH_ASSOC);

// Count the attempts for the current course and student
$attemptSql = "SELECT COUNT(*) FROM tbl_result WHERE quiz_type = 3 AND stud_id = :stud_id";
$stmtAttempt = $conn->prepare($attemptSql);
$stmtAttempt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
$stmtAttempt->execute();
$attemptCount = $stmtAttempt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <link rel="stylesheet" href="mobile-desktop.css" type="text/css">
</head>


<body>

    <div class="mt-5" id="topBar">

        <?php
        include 'topNavBar.php';
        ?>

    </div>

    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-2">
                    <div class="col-md-12">
                        <div class="text-center mb-4">
                            <h1><?php
                                echo "Exam Result" . $courseName;
                                ?></h1>
                        </div>
                        <!-- Search Bar -->
                        <form action="" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table id="resultTable" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <th scope="col">Attempt</th>
                                        <?php foreach ($courseNames as $course) : ?>
                                            <th scope="col"><?php echo htmlspecialchars($course['course_name']); ?></th>
                                        <?php endforeach; ?>


                                        <th scope="col">ResultScore</th>
                                        <th scope="col">Remarks</th>
                                        <th scope="col">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($results) : ?>
                                        <?php foreach ($results as $row) : ?>
                                            <tr style="text-align: center;">
                                                <td><?php

                                                    echo $attemptCount; ?></td>
                                                <?php
                                                $attemptCount--;
                                                ?>
                                                <?php foreach ($courseNames as $course) : ?>
                                                    <td>
                                                        <?php
                                                        // Display Course ID
                                                        $course_id = (int)$course['course_id'];

                                                        // Calculate and display Attempt count
                                                        $attempt = $attemptCount + 1;

                                                        // Fetch total quiz questions
                                                        $quizAnswersSql = "SELECT COUNT(*) AS total_quiz_questions
                           FROM `tbl_quiz_answers`
                           WHERE course_id = :course_id 
                           AND quiz_type = 3
                           AND student_id = :stud_id
                           AND attempt_id = :attempt_id";

                                                        $quizAnswersStmt = $conn->prepare($quizAnswersSql);
                                                        $quizAnswersStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                                                        $quizAnswersStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
                                                        $quizAnswersStmt->bindParam(':attempt_id', $attempt, PDO::PARAM_INT);
                                                        $quizAnswersStmt->execute();
                                                        $quizAnswersResult = $quizAnswersStmt->fetch(PDO::FETCH_ASSOC);

                                                        // Fetch total correct answers
                                                        $quizCorrectAnswersSql = "SELECT COUNT(*) AS total_correct_answers
                                  FROM `tbl_quiz_answers`
                                  WHERE course_id = :course_id 
                                  AND quiz_type = 3
                                  AND student_id = :stud_id
                                  AND answer_status = 1
                                  AND attempt_id = :attempt_id";

                                                        $quizCorrectAnswersStmt = $conn->prepare($quizCorrectAnswersSql);
                                                        $quizCorrectAnswersStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                                                        $quizCorrectAnswersStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
                                                        $quizCorrectAnswersStmt->bindParam(':attempt_id', $attempt, PDO::PARAM_INT);
                                                        $quizCorrectAnswersStmt->execute();
                                                        $quizCorrectAnswersResult = $quizCorrectAnswersStmt->fetch(PDO::FETCH_ASSOC);

                                                        // Display the result with proper styling
                                                        $colorStyle = ""; // Define your $colorStyle variable as needed
                                                        ?>
                                                        <span style="<?php echo htmlspecialchars($colorStyle) . ' font-size: 15px;'; ?>">
                                                            <?php echo htmlspecialchars("{$quizCorrectAnswersResult['total_correct_answers']}/{$quizAnswersResult['total_quiz_questions']}"); ?>
                                                        </span>

                                                    </td>
                                                <?php endforeach; ?>


                                                <td>
                                                    <?php
                                                    $resultScore = $row['result_score'];
                                                    $totalQuestions = $row['total_questions'];
                                                    $percentage = $totalQuestions > 0 ? ($resultScore / $totalQuestions) * 100 : 0;
                                                    $colorStyle = ($percentage < 50) ? 'color: red;' : 'color: green;';
                                                    ?>
                                                    <strong><span style="<?php echo $colorStyle; ?>"><?php echo $resultScore; ?> / <?php echo $totalQuestions; ?></span></strong>
                                                </td>

                                                <td scope="col">
                                                    <?php
                                                    $res = ($row['result_score'] / $row['total_questions']) * 100;
                                                    if ($res >= 50) {
                                                        echo '<b><span style="color: green;">Passed</span></b>';
                                                    } else {
                                                        echo '<b><span style="color: red;">Failed</span></b>';
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