<?php
session_start();

require '../api/db-connect.php';

if (!isset($_SESSION['program_id'])) {
    // Redirect to login page if session data is not set
    header("Location: ../index.php");
    exit();
}

$program_id = $_SESSION['program_id'];

// Prepare SQL query to fetch courses for the given program
$sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->execute();

// Fetch the courses
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

// Retrieve values from URL parameters
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$stud_id = isset($_SESSION['stud_id']) ? $_SESSION['stud_id'] : null; // Retrieve stud_id from session

// Prepare and execute the main SQL query
$sql = "SELECT tbl_result.result_score, tbl_result.total_questions, tbl_module.module_name, tbl_result.created_at as date_created
        FROM tbl_result
        INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
        WHERE tbl_result.quiz_type = 2 AND tbl_result.course_id = :course_id AND tbl_result.stud_id = :stud_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve module names based on the course ID
$sqlModuleNames = "SELECT DISTINCT tbl_module.module_id, tbl_module.module_name
                   FROM tbl_module
                   INNER JOIN tbl_result ON tbl_module.module_id = tbl_result.module_id
                   WHERE tbl_result.course_id = :course_id";
$stmtModuleNames = $conn->prepare($sqlModuleNames);
$stmtModuleNames->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtModuleNames->execute();
$moduleNames = $stmtModuleNames->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-2">
                    <div class="col-md-12">
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
                            <table id="resultTable" class="table table-bordered table-custom">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <th scope="col">Attempt No.</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Date</th>
                                        <?php if ($moduleNames && $stmtModuleNames->rowCount() > 0) : ?>
                                            <?php foreach ($moduleNames as $moduleName) : ?>
                                                <th scope="col"><?php echo $moduleName['module_name']; ?></th>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <th scope="col">Module Name</th>
                                        <?php endif; ?>
                                        <th scope="col">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $index = 1;
                                    if ($results) :
                                        foreach ($results as $row) : ?>
                                            <tr style="text-align: center;">
                                                <td><?php echo $index++; ?></td>
                                                <td>Quiz</td>
                                                <td><?php echo date("M d, Y", strtotime($row['date_created'])); ?></td>
                                                <?php if ($moduleNames && $stmtModuleNames->rowCount() > 0) : ?>
                                                    <?php foreach ($moduleNames as $moduleName) : ?>
                                                        <td>
                                                            <?php
                                                            try {
                                                                $module_id = (int)$moduleName['module_id'];

                                                                // SQL query to get attempt IDs
                                                                $quizAttemptSql = "SELECT DISTINCT attempt_id FROM `tbl_quiz_answers` WHERE module_id = :module_id AND quiz_type = 2";
                                                                $quizAttemptSqlStmt = $conn->prepare($quizAttemptSql);
                                                                $quizAttemptSqlStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                                                                $quizAttemptSqlStmt->execute();
                                                                $attemptResults = $quizAttemptSqlStmt->fetchAll(PDO::FETCH_ASSOC);

                                                                // Initialize variables to store overall results
                                                                $overallCorrectAnswers = 0;
                                                                $totalQuizAnswers = 0;

                                                                foreach ($attemptResults as $attemptResult) {
                                                                    $attemptId = $attemptResult['attempt_id'];

                                                                    // SQL query to get total and correct answers for the whole module
                                                                    $quizAnswersSql = "SELECT 
                                                                    COUNT(*) AS total_quiz_answers,
                                                                    SUM(answer_status) AS correct_answers
                                                                    FROM `tbl_quiz_answers`
                                                                    WHERE module_id = :module_id AND quiz_type = 2";

                                                                    // Prepare the SQL statement
                                                                    $quizAnswersStmt = $conn->prepare($quizAnswersSql);
                                                                    $quizAnswersStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                                                                    $quizAnswersStmt->execute();
                                                                    $result = $quizAnswersStmt->fetch(PDO::FETCH_ASSOC);
                                                                    $totalQuizAnswers = $result['total_quiz_answers'];
                                                                    $overallCorrectAnswers = $result['correct_answers'];

                                                                    // Fetch data filtered by attempt_id
                                                                    $attemptDetailsSql = "SELECT * FROM `tbl_quiz_answers` WHERE attempt_id = :attempt_id AND module_id = :module_id AND quiz_type = 2";
                                                                    $attemptDetailsStmt = $conn->prepare($attemptDetailsSql);
                                                                    $attemptDetailsStmt->bindParam(':attempt_id', $attemptId, PDO::PARAM_INT);
                                                                    $attemptDetailsStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                                                                    $attemptDetailsStmt->execute();
                                                                    $attemptDetails = $attemptDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

                                                                    $totalAnswersForAttempt = count($attemptDetails);
                                                                    $correctAnswersForAttempt = 0;
                                                                    foreach ($attemptDetails as $answer) {
                                                                        if ($answer['answer_status'] == 1) {
                                                                            $correctAnswersForAttempt++;
                                                                        }
                                                                    }
                                                                    $percentageCorrectForAttempt = $totalAnswersForAttempt > 0 ? ($correctAnswersForAttempt / $totalAnswersForAttempt) * 100 : 0;
                                                                    // Display the attempt details
                                                                    echo "Module ID: {$module_id} - Attempt ID: $attemptId - Percentage Correct: $percentageCorrectForAttempt%<br>";
                                                                    echo "Correct Answers: $correctAnswersForAttempt / $totalAnswersForAttempt<br>";
                                                                }

                                                                // Calculate overall percentage correct
                                                                $percentageCorrect = $totalQuizAnswers > 0 ? ($overallCorrectAnswers / $totalQuizAnswers) * 100 : 0;

                                                                // Determine the color class based on the overall percentage correct
                                                                $colorClass = ($percentageCorrect < 50) ? 'red' : 'green';
                                                            } catch (PDOException $e) {
                                                                echo "Error: " . $e->getMessage();
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                <?php else : ?>
                                                    <td>Module Name</td>
                                                <?php endif; ?>
                                                <td>
                                                    <?php
                                                    $res = ($row['result_score'] / $row['total_questions']) * 100;
                                                    echo $res >= 50 ? "Passed" : "Failed";
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else : ?>
                                        <tr>
                                            <td colspan="<?php echo $moduleNames ? count($moduleNames) + 4 : 5; ?>" class="text-center">No records found.</td>
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
            clearSearchButton.style.display = "none";
        }
    }

    // Toggle clear button on page load
    toggleClearButton();

    // JavaScript for filtering table data
    searchInput.addEventListener("keyup", function() {
        toggleClearButton();
        const value = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll("#resultTable tbody tr");

        rows.forEach(row => {
            let found = false;
            row.querySelectorAll('td').forEach(cell => {
                const text = cell.textContent.toLowerCase().trim();
                if (text.includes(value)) {
                    found = true;
                }
            });
            row.style.display = found ? "" : "none";
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