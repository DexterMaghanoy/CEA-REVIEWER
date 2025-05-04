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
        WHERE tbl_result.quiz_type = 2 AND tbl_result.course_id = :course_id AND tbl_result.stud_id = :stud_id
        ORDER BY tbl_result.created_at DESC";

// Prepare and execute the SQL query
$result = $conn->prepare($sql);
$result->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$result->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
$result->execute();

// Fetch the results
$results = $result->fetchAll(PDO::FETCH_ASSOC);

// Find the course name corresponding to the given course_id
$courseName = "";
foreach ($courses as $course) {
    if ($course['course_id'] == $course_id) {
        $courseName = $course['course_name'];
        break;
    }
}

// Fetch distinct module names for the course
$sqlModuleNames = "SELECT DISTINCT tbl_module.module_id, tbl_module.module_name
                   FROM tbl_module
                   INNER JOIN tbl_result ON tbl_module.module_id = tbl_result.module_id
                   WHERE tbl_result.course_id = :course_id AND module_status = 1";
$stmtModuleNames = $conn->prepare($sqlModuleNames);
$stmtModuleNames->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtModuleNames->execute();
$moduleNames = $stmtModuleNames->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct module quizzes for the course along with attempt_id
$queryModuleQuiz = "SELECT DISTINCT m.module_id, m.module_name, qa.attempt_id
                    FROM tbl_module m
                    INNER JOIN tbl_quiz_answers qa ON m.module_id = qa.module_id
                    WHERE qa.course_id = :course_id";
$stmtModuleQuiz = $conn->prepare($queryModuleQuiz);
$stmtModuleQuiz->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtModuleQuiz->execute();
$moduleQuiz = $stmtModuleQuiz->fetchAll(PDO::FETCH_ASSOC);

// Count the attempts for the current course and student
$attemptSql = "SELECT COUNT(*) FROM tbl_result WHERE quiz_type = 2 AND course_id = :course_id AND program_id = :program_id AND stud_id = :stud_id";
$stmtAttempt = $conn->prepare($attemptSql);
$stmtAttempt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtAttempt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtAttempt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
$stmtAttempt->execute();
$attemptCount = $stmtAttempt->fetchColumn();


// Fetch the latest pass rate
$passRateSql = "SELECT pass_rate FROM tbl_passrate ORDER BY created_at DESC LIMIT 1";
$passRateStmt = $conn->prepare($passRateSql);
$passRateStmt->execute();
$passRateData = $passRateStmt->fetch(PDO::FETCH_ASSOC);
$passRate = $passRateData['pass_rate'] ?? 0; // Fallback to 0 if no rate is found



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
                    <div class="col-md-12">
                        <div class="text-center mb-4">
                            <h1><?php
                                echo "Subject: " . $courseName;
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
                                        <?php foreach ($moduleNames as $moduleName) : ?>
                                            <th scope="col"><?php echo htmlspecialchars($moduleName['module_name']); ?></th>
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
                                                <?php foreach ($moduleNames as $moduleName) : ?>

                                                    <td>
                                                        <?php
                                                        // Display Module ID
                                                        $module_id = (int)$moduleName['module_id'];
                                                        // echo 'Module#: ' . $module_id;
                                                        // echo '<br>';

                                                        // Calculate and display Attempt count
                                                        $attempt = $attemptCount + 1;
                                                        // echo 'Attempt: ' . $attempt;
                                                        // echo '<br>';
                                                        // echo 'Stud_id: ' . $stud_id;
                                                        // echo '<br>';

                                                        // Fetch total quiz questions
                                                        $quizAnswersSql = "SELECT COUNT(*) AS total_quiz_questions
                       FROM `tbl_quiz_answers`
                       WHERE module_id = :module_id 
                       AND quiz_type = 2
                       AND student_id = :stud_id
                       AND attempt_id = :attempt_id";

                                                        $quizAnswersStmt = $conn->prepare($quizAnswersSql);
                                                        $quizAnswersStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                                                        $quizAnswersStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
                                                        $quizAnswersStmt->bindParam(':attempt_id', $attempt, PDO::PARAM_INT);
                                                        $quizAnswersStmt->execute();
                                                        $result = $quizAnswersStmt->fetch(PDO::FETCH_ASSOC);

                                                        // Fetch total correct answers
                                                        $quizCorrectAnswersSql = "SELECT COUNT(*) AS total_correct_answers
                              FROM `tbl_quiz_answers`
                              WHERE module_id = :module_id 
                              AND quiz_type = 2
                              AND student_id = :stud_id
                              AND answer_status = 1
                              AND attempt_id = :attempt_id";

                                                        $quizCorrectAnswersStmt = $conn->prepare($quizCorrectAnswersSql);
                                                        $quizCorrectAnswersStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                                                        $quizCorrectAnswersStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
                                                        $quizCorrectAnswersStmt->bindParam(':attempt_id', $attempt, PDO::PARAM_INT);
                                                        $quizCorrectAnswersStmt->execute();
                                                        $CorrectResult = $quizCorrectAnswersStmt->fetch(PDO::FETCH_ASSOC);

                                                        // Display the result with proper styling
                                                        $colorStyle = ""; // Define your $colorStyle variable as needed
                                                        ?>
                                                        <span style="<?php echo htmlspecialchars($colorStyle) . ' font-size: 15px;'; ?>">
                                                            <?php echo htmlspecialchars("{$CorrectResult['total_correct_answers']}/{$result['total_quiz_questions']}"); ?>
                                                        </span>
                                                    </td>

                                                <?php endforeach; ?>

                                                <td>
                                                    <?php
                                                    $resultScore = $row['result_score'];
                                                    $totalQuestions = $row['total_questions'];
                                                    $percentage = $totalQuestions > 0 ? ($resultScore / $totalQuestions) * 100 : 0;
                                                    $colorStyle = ($percentage < $passRate) ? 'color: red;' : 'color: green;';
                                                    ?>
                                                    <strong><span style="<?php echo $colorStyle; ?>"><?php echo $resultScore; ?> / <?php echo $totalQuestions; ?></span></strong>
                                                </td>

                                                <td scope="col">
                                                    <?php
                                                    $res = ($row['result_score'] / $row['total_questions']) * 100;
                                                    if ($res >= $passRate) {
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