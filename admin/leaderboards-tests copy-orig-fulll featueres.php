    <?php
    session_start();
    require("../api/db-connect.php");

    // Check if user is logged in and program ID is set
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['program_id'])) {
        header("Location: ../index.php");
        exit();
    }



$user_id = $_SESSION['user_id'];
$program_id = $_SESSION['program_id'];

$sqlTotalModules = "SELECT COUNT(*) AS all_modules_count 
                    FROM tbl_module m
                    WHERE m.course_id IN (
                        SELECT course_id FROM tbl_course WHERE program_id = :program_id
                    ) 
                    AND m.module_status = 1 
                    AND EXISTS (
                        SELECT 1 FROM tbl_question q WHERE q.module_id = m.module_id
                    )"; // Ensures only modules with questions are counted

$stmtTotalModules = $conn->prepare($sqlTotalModules);
$stmtTotalModules->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtTotalModules->execute();
$modulesResult = $stmtTotalModules->fetch(PDO::FETCH_ASSOC);
$all_modules_count = $modulesResult['all_modules_count'] ?? 0; // Default to 0 if no result




// total courses count



// Fetch program name
$sqlProgramName = "SELECT program_name FROM tbl_program WHERE program_id = :program_id";
$stmtProgramName = $conn->prepare($sqlProgramName);
$stmtProgramName->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtProgramName->execute();
$program_name = $stmtProgramName->fetch(PDO::FETCH_ASSOC)['program_name'];

// Count total students
$sqlTotalStudents = "SELECT COUNT(*) as total FROM tbl_student WHERE program_id = :program_id";
$stmtTotalStudents = $conn->prepare($sqlTotalStudents);
$stmtTotalStudents->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtTotalStudents->execute();
$totalCount = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC)['total'];

// Fetch top students

$sqlTopStudents = "SELECT 
                        s.stud_lname,
                        s.stud_fname,
                        s.stud_mname,
                        COUNT(r.result_status) AS result_status_count,
                        SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) AS passed_result_status_count,
                        COUNT(DISTINCT CASE WHEN r.result_status = 1 THEN r.module_id ELSE NULL END) AS passed_modules_count,
                        IF(COUNT(r.result_status) > 0, (SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) / COUNT(r.result_status)) * 100, 0) AS pass_rate
                    FROM 
                        tbl_student AS s
                    INNER JOIN 
                        tbl_result AS r ON s.stud_id = r.stud_id
                    WHERE
                        r.quiz_type = 1
                    AND
                        s.program_id = :program_id
                    AND
                        YEAR(r.created_at) = YEAR(CURDATE())  -- Filter results by current year
                    GROUP BY 
                        s.stud_id
                    HAVING
                        COUNT(r.result_status) > 0  
                    ORDER BY 
                        passed_modules_count DESC, pass_rate DESC 
                    LIMIT 5";





$stmtTopStudents = $conn->prepare($sqlTopStudents);
$stmtTopStudents->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtTopStudents->execute();
$totalRows = $stmtTopStudents->rowCount();

$stmtCourseIds = $conn->prepare("
    SELECT course_id 
    FROM tbl_course 
    WHERE program_id = :program_id
");
$stmtCourseIds->bindValue(':program_id', $program_id, PDO::PARAM_INT);
$stmtCourseIds->execute();
$courseIds = $stmtCourseIds->fetchAll(PDO::FETCH_COLUMN, 0);

$totalModulesQ = 0;

foreach ($courseIds as $course_id) {
    $stmtTotalModulesWithQuestions = $conn->prepare("
        SELECT COUNT(*) AS total_modules_q 
        FROM (
            SELECT module_id 
            FROM tbl_question 
            WHERE module_id IN (
                SELECT module_id 
                FROM tbl_module 
                WHERE course_id = :course_id 
                AND module_status = 1
            )
            GROUP BY module_id
        ) AS subquery
    ");
    $stmtTotalModulesWithQuestions->bindValue(':course_id', $course_id, PDO::PARAM_INT);
    $stmtTotalModulesWithQuestions->execute();
    $stmtTotalModulesWithQ = $stmtTotalModulesWithQuestions->fetch(PDO::FETCH_ASSOC);
    $totalModulesQ += $stmtTotalModulesWithQ['total_modules_q'];
}



?>



<?php

// Check if user is logged in and program ID is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : $_SESSION['program_id'];



// Fetch program name
$sqlProgramName = "SELECT program_name FROM tbl_program WHERE program_id = :program_id";
$stmtProgramName = $conn->prepare($sqlProgramName);
$stmtProgramName->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtProgramName->execute();
$program_name = $stmtProgramName->fetch(PDO::FETCH_ASSOC)['program_name'] ?? 'Unknown';

// Count total students
$sqlTotalStudents = "SELECT COUNT(*) as total FROM tbl_student WHERE program_id = :program_id";
$stmtTotalStudents = $conn->prepare($sqlTotalStudents);
$stmtTotalStudents->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtTotalStudents->execute();
$totalCount = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$currentYear = date("Y"); // Get the current year
$startYear = 2020; // Define the starting year
$selectedYear = isset($_GET['created_at']) ? (int)$_GET['created_at'] : $currentYear;
$selectedQuizType = isset($_GET['quiz_type']) ? (int)$_GET['quiz_type'] : 1;
$selectedProgramId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : $program_id;

// Fetch top students
$orderBy = ($selectedQuizType == 3) ? "passed_modules_count ASC, pass_rate DESC" : "passed_modules_count DESC, pass_rate DESC";

$sqlTopStudents = "SELECT 
                        s.stud_id,
                        s.stud_lname,
                        s.stud_fname,
                        s.stud_mname,
                        COUNT(r.result_status) AS result_status_count,
                        SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) AS passed_result_status_count,
                        COUNT(DISTINCT CASE WHEN r.result_status = 1 THEN r.module_id ELSE NULL END) AS passed_modules_count,
                        IF(COUNT(r.result_status) > 0, (SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) / COUNT(r.result_status)) * 100, 0) AS pass_rate
                    FROM 
                        tbl_student AS s
                    INNER JOIN 
                        tbl_result AS r ON s.stud_id = r.stud_id
                    WHERE
                        r.quiz_type = :quiz_type
                    AND
                        s.program_id = :program_id
                    AND
                        YEAR(r.created_at) = :created_year  
                    GROUP BY 
                        s.stud_id
                    HAVING
                        COUNT(r.result_status) > 0  
                    ORDER BY 
                        $orderBy 
                    LIMIT 5";

$stmtTopStudents = $conn->prepare($sqlTopStudents);
$stmtTopStudents->bindParam(':quiz_type', $selectedQuizType, PDO::PARAM_INT);
$stmtTopStudents->bindParam(':program_id', $selectedProgramId, PDO::PARAM_INT);
$stmtTopStudents->bindParam(':created_year', $selectedYear, PDO::PARAM_INT);
$stmtTopStudents->execute();

// Fetch all programs
$sqlPrograms = "SELECT program_id, program_name FROM tbl_program";
$stmtPrograms = $conn->prepare($sqlPrograms);
$stmtPrograms->execute();
$programs = $stmtPrograms->fetchAll(PDO::FETCH_ASSOC);

// Map quiz types
$quizTypes = [
    1 => "Tests",
    2 => "Quizzes",
    3 => "Exam"
];
$selectedQuizTypeName = $quizTypes[$selectedQuizType] ?? "Unknown";
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboards</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->

</head>

<link rel="stylesheet" href="leaderboard.css" type="text/css">


<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="container">
            <div class="row justify-content-center mt-4">
                <div class="col-md-10">

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="programDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= htmlspecialchars($program_name) ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="programDropdown">
                                    <?php foreach ($programs as $program): ?>
                                        <li>
                                            <a class="dropdown-item <?= ($program['program_id'] == $selectedProgramId) ? 'active' : ''; ?>"
                                                href="?program_id=<?= $program['program_id'] ?>&quiz_type=<?= $selectedQuizType ?>&created_at=<?= $selectedYear ?>">
                                                <?= htmlspecialchars($program['program_name']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-4 text-center">
                            <h1>Leaderboards</h1>
                            <div class="dropdown-container">
                                <select id="yearDropdown">
                                    <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                        <option value="<?= $year ?>" <?= ($year == $selectedYear) ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>



                        <div class="col-md-4 text-end">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="quizDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= htmlspecialchars($selectedQuizTypeName) ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="quizDropdown">
                                    <?php foreach ($quizTypes as $type => $name): ?>
                                        <li>
                                            <a class="dropdown-item <?= ($type == $selectedQuizType) ? 'active' : ''; ?>"
                                                href="?program_id=<?= $selectedProgramId ?>&quiz_type=<?= $type ?>&created_at=<?= $selectedYear ?>">
                                                <?= htmlspecialchars($name) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-custom" id="courseTable">
                            <caption>List of Students</caption>
                            <thead class="table-dark" style="text-align: center;">
                                <tr>
                                    <th scope="col">Rank</th>
                                    <th scope="col">Fullname</th>
                                    <th scope="col">Total Attempts</th>

                                    <?php if ($selectedQuizType == 1) { ?>
                                        <th scope="col">Module Passed</th>
                                    <?php } elseif ($selectedQuizType == 2) { ?>
                                        <th scope="col">Quiz Passed</th>
                                    <?php } elseif ($selectedQuizType == 3) { ?>
                                        <th scope="col">Completed</th> <!-- You can define the column name here -->
                                    <?php } ?>

                                    <th scope="col">Pass Rate</th>
                                </tr>

                            </thead>

                            <tbody style="background-color: #d2f0d6;">


                                <?php

                                if ($totalRows >   0) : ?>
                                    <?php $rank = 1; ?>

                                    <?php if ($stmtTopStudents->rowCount() > 0) : ?>
                                        <?php while ($row = $stmtTopStudents->fetch(PDO::FETCH_ASSOC)) : ?>
                                            <tr style="text-align: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                                                <td>
                                                    <?php if ($rank <= 3) : ?>
                                                        <?php switch ($rank) {
                                                            case 1:
                                                                echo '<img width="100" src="./GIF/gold.gif" alt="Gold">';
                                                                break;
                                                            case 2:
                                                                echo '<img width="100" src="./GIF/silver.gif" alt="Silver">';
                                                                break;
                                                            case 3:
                                                                echo '<img width="100" src="./GIF/bronze.gif" alt="Bronze">';
                                                                break;
                                                        } ?>
                                                    <?php else : ?>
                                                        <strong><?php echo $rank . 'th'; ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align: center; font-size: larger;">
                                                    <strong><?php echo htmlspecialchars($row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['result_status_count']); ?></td>

                                                <td>
                                                    <?php
                                                    if ($selectedQuizType == 1) {
                                                        echo htmlspecialchars($row['passed_modules_count']) . " / " . htmlspecialchars($all_modules_count);
                                                    } elseif ($selectedQuizType == 2) {
                                                        // Debugging: Check if stud_id exists in $row
                                                        if (!isset($row['stud_id'])) {
                                                            echo "Error: 'stud_id' is missing from the query result!";
                                                            var_dump($row); // Debugging: Check what data is available
                                                            exit(); // Stop execution
                                                        }

                                                        // Fetch total number of quizzes with result_status = 1 for the specific student
                                                        $sqlTotalQuizzesPassed = "SELECT COUNT(DISTINCT module_id) AS total_quizzes_passed 
                                                                              FROM tbl_result 
                                                                              WHERE quiz_type = 2 
                                                                              AND result_status = 1
                                                                              AND stud_id = :stud_id"; // Filter by current student

                                                        $stmtTotalQuizzesPassed = $conn->prepare($sqlTotalQuizzesPassed);
                                                        $stmtTotalQuizzesPassed->bindParam(':stud_id', $row['stud_id'], PDO::PARAM_INT); // Bind the current student's ID

                                                        // Debugging: Check if query executes correctly
                                                        if (!$stmtTotalQuizzesPassed->execute()) {
                                                            echo "Query Execution Failed!";
                                                            print_r($stmtTotalQuizzesPassed->errorInfo()); // Show SQL errors
                                                            exit();
                                                        }

                                                        $quizResult = $stmtTotalQuizzesPassed->fetch(PDO::FETCH_ASSOC);
                                                        $total_quizzes_passed = $quizResult['total_quizzes_passed'] ?? 0; // Default to 0 if no result


                                                        $sqlTotalCourses = "SELECT COUNT(DISTINCT module_id) AS total_courses_count 
                                                    FROM tbl_result 
                                                    WHERE quiz_type = :quiz_type
                                                    AND YEAR(created_at) = :created_year";
                                                        $stmtTotalCourses = $conn->prepare($sqlTotalCourses);
                                                        $stmtTotalCourses->bindParam(':quiz_type', $selectedQuizType, PDO::PARAM_INT);
                                                        $stmtTotalCourses->bindParam(':created_year', $selectedYear, PDO::PARAM_INT);
                                                        $stmtTotalCourses->execute();
                                                        $totalCoursesResult = $stmtTotalCourses->fetch(PDO::FETCH_ASSOC);
                                                        $totalCoursesCount = $totalCoursesResult['total_courses_count'] ?? 0; // Default to 0 if no data


                                                        // Debugging: Display stud_id and result
                                                        // echo "Student ID: " . htmlspecialchars($row['stud_id']) . "<br>";
                                                        // echo "Total Quizzes Passed: " . htmlspecialchars($total_quizzes_passed) . "<br>";

                                                        // echo "All Subject Count: " . htmlspecialchars($totalCoursesCount) . "<br>";

                                                        echo htmlspecialchars($total_quizzes_passed) . " / " . htmlspecialchars($totalCoursesCount) . "<br>";
                                                    } elseif ($selectedQuizType == 3) {
                                                        // Prepare the SQL query
                                                        $sqlTotalExamPassed = "SELECT COUNT(DISTINCT module_id) AS total_quizzes_passed 
                                                                           FROM tbl_result 
                                                                           WHERE quiz_type = 3
                                                                           AND result_status = 1
                                                                           AND stud_id = :stud_id";

                                                        $stmtTotalExamPassed = $conn->prepare($sqlTotalExamPassed);
                                                        $stmtTotalExamPassed->bindParam(':stud_id', $row['stud_id'], PDO::PARAM_INT);

                                                        // Execute the query
                                                        if ($stmtTotalExamPassed->execute()) {
                                                            $examResult = $stmtTotalExamPassed->fetch(PDO::FETCH_ASSOC);
                                                            $totalExamsPassed = $examResult['total_quizzes_passed'] ?? 0; // Default to 0 if no data

                                                            // Debugging: Check the fetched value
                                                            echo "Total Exams Passed: " . htmlspecialchars($totalExamsPassed) . "<br>";

                                                            // Show "Yes" if at least one result exists, otherwise "No"
                                                            echo ($totalExamsPassed > 0) ? "Yes" : "No";
                                                        } else {
                                                            echo "Query execution failed!";
                                                            print_r($stmtTotalExamPassed->errorInfo()); // Debugging SQL errors
                                                        }
                                                    }



                                                    ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    if ($selectedQuizType == 1) {
                                                        // Ensure variables exist to prevent division errors
                                                        $totalAttempts = $row['result_status_count'] ?? 1;
                                                        $totalPassedModules = $row['passed_modules_count'] ?? 0;
                                                        $totalModules = $totalModulesQ ?? 1;
                                                        $allModules = $all_modules_count ?? 1;

                                                        // Modules failed
                                                        $failedModules = $totalModules - $totalPassedModules;

                                                        // **Pass Rate Calculation: Penalizing more attempts**
                                                        $passRateByModules = ($totalPassedModules / $totalModules) * 100; // Module-based pass rate
                                                        $efficiencyFactor = ($totalPassedModules / max($totalAttempts, 1)); // Penalize high attempts

                                                        // **Final Adjusted Pass Rate**
                                                        $adjustedPassRate = $passRateByModules * $efficiencyFactor;

                                                        echo number_format($adjustedPassRate, 2) . "%";
                                                    } elseif ($selectedQuizType == 2) {
                                                        // Fetch the total quizzes passed properly
                                                        $totalQuizPassed = $total_quizzes_passed; // Fetch as integer

                                                        // Ensure values are correctly initialized
                                                        $totalQuizAttempts = $row['result_status_count'] ?? 1; // Total attempts made
                                                        $totalCoursesToPass = $totalCoursesCount ?? 1; // Ensure it's not zero

                                                        // **Courses failed**
                                                        $failedCourses = $totalCoursesToPass - $totalQuizPassed;

                                                        // **Pass Rate Calculation: Penalizing more attempts**
                                                        $passRateByCourses = ($totalQuizPassed / $totalCoursesToPass) * 100; // Course-based pass rate
                                                        $efficiencyFactor = ($totalQuizPassed / max($totalQuizAttempts, 1)); // Penalize high attempts

                                                        // **Final Adjusted Pass Rate**
                                                        $adjustedPassRate = $passRateByCourses * $efficiencyFactor;

                                                        echo number_format($adjustedPassRate, 2) . "%";
                                                    } elseif ($selectedQuizType == 3) {

                                                        echo htmlspecialchars(100 / $row['result_status_count']) . "%";
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php $rank++; ?>
                                        <?php endwhile; ?>

                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No records found for students.</td>
                                        </tr>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No records found for students.</td>
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
        const hamBurger = document.querySelector(".toggle-btn");
        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>
    <script>
        document.getElementById("yearDropdown").addEventListener("change", function() {
            window.location.href = "?program_id=<?= $selectedProgramId ?>&quiz_type=<?= $selectedQuizType ?>&created_at=" + this.value;
        });
    </script>
</body>

</html>