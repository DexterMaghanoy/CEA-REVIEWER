<?php
session_start();
require("../api/db-connect.php");

// Check if user is logged in and program ID is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

$selectedQuizType = $_GET['quiz_type'] ?? 1;
$selectedProgramId = $_GET['program_id'] ?? 1;
$selectedYear = $_GET['created_at'] ?? date('Y');

// Fetch top students
$sql = "SELECT 
            s.stud_id,
            s.stud_fname,
            s.stud_lname,
            s.stud_mname,
            COUNT(r.result_id) AS result_status_count,
            SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) AS passed_modules_count
        FROM tbl_student s
        LEFT JOIN tbl_result r ON s.stud_id = r.stud_id AND r.quiz_type = :quiz_type
        WHERE s.program_id = :program_id AND YEAR(r.created_at) = :created_year
        GROUP BY s.stud_id
        ORDER BY passed_modules_count DESC";

$stmtTopStudents = $conn->prepare($sql);
$stmtTopStudents->bindParam(':quiz_type', $selectedQuizType, PDO::PARAM_INT);
$stmtTopStudents->bindParam(':program_id', $selectedProgramId, PDO::PARAM_INT);
$stmtTopStudents->bindParam(':created_year', $selectedYear, PDO::PARAM_INT);
$stmtTopStudents->execute();

$totalRows = $stmtTopStudents->rowCount();

// Fetch total modules/quizzes count for the current year
$sqlTotalCourses = "SELECT COUNT(DISTINCT module_id) AS total_courses_count 
                    FROM tbl_result 
                    WHERE quiz_type = :quiz_type AND YEAR(created_at) = :created_year";
$stmtTotalCourses = $conn->prepare($sqlTotalCourses);
$stmtTotalCourses->bindParam(':quiz_type', $selectedQuizType, PDO::PARAM_INT);
$stmtTotalCourses->bindParam(':created_year', $selectedYear, PDO::PARAM_INT);
$stmtTotalCourses->execute();
$totalCoursesResult = $stmtTotalCourses->fetch(PDO::FETCH_ASSOC);

// Set `$all_modules_count` to 1 if no data is returned to avoid division by zero
$all_modules_count = $totalCoursesResult['total_courses_count'] ?? 1;

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
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>
        <?php
        include 'back.php';
        ?>
        <div class="container mt-5">
            <h2 class="text-center mb-4">Leaderboard</h2>

            <!-- Filtration Form -->
            <div class="container mt-3">
                <form method="GET" action="leaderboards-tests.php" class="form-inline">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="yearDropdown">Year</label>
                            <select id="yearDropdown" name="created_at" class="form-control" onchange="this.form.submit()">
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= 2020; $year--) {
                                    echo "<option value=\"$year\" " . ($year == $selectedYear ? 'selected' : '') . ">$year</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="quizTypeDropdown">Quiz Type</label>
                            <select id="quizTypeDropdown" name="quiz_type" class="form-control" onchange="this.form.submit()">
                                <option value="1" <?php echo $selectedQuizType == 1 ? 'selected' : ''; ?>>Test</option>
                                <option value="2" <?php echo $selectedQuizType == 2 ? 'selected' : ''; ?>>Quiz</option>
                                <option value="3" <?php echo $selectedQuizType == 3 ? 'selected' : ''; ?>>Exam</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="programDropdown">Program</label>
                            <select id="programDropdown" name="program_id" class="form-control" disabled>
                                <?php
                                // Assuming a list of programs from the database
                                $sqlPrograms = "SELECT program_id, program_name FROM tbl_program";
                                $stmtPrograms = $conn->prepare($sqlPrograms);
                                $stmtPrograms->execute();
                                while ($program = $stmtPrograms->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value=\"" . $program['program_id'] . "\" " . ($selectedProgramId == $program['program_id'] ? 'selected' : '') . ">" . htmlspecialchars($program['program_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-custom" id="courseTable">
                    <caption>List of Students</caption>
                    <thead class="table-dark text-center">
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Fullname</th>
                            <th scope="col">Total Attempts</th>
                            <th scope="col">
                                <?php
                                if ($selectedQuizType == 1) echo "Module Passed";
                                elseif ($selectedQuizType == 2) echo "Quiz Passed";
                                elseif ($selectedQuizType == 3) echo "Completed";
                                ?>
                            </th>
                            <th scope="col">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: #d2f0d6;">
                        <?php if ($totalRows > 0): ?>
                            <?php
                            $students = [];

                            while ($row = $stmtTopStudents->fetch(PDO::FETCH_ASSOC)) {
                                $attempts = $row['result_status_count'] ?? 1;
                                $passedCount = $row['passed_modules_count'] ?? 0;

                                if ($selectedQuizType == 2) {
                                    $sqlTotalPassed = "SELECT COUNT(DISTINCT module_id) AS total_quizzes_passed 
                        FROM tbl_result 
                        WHERE quiz_type = 2 AND result_status = 1 AND stud_id = :stud_id";
                                    $stmt = $conn->prepare($sqlTotalPassed);
                                    $stmt->bindParam(':stud_id', $row['stud_id'], PDO::PARAM_INT);
                                    $stmt->execute();
                                    $passedCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_quizzes_passed'] ?? 0;
                                }

                                if ($selectedQuizType == 3) {
                                    $sqlTotalPassed = "SELECT COUNT(DISTINCT module_id) AS total_exams_passed 
                        FROM tbl_result 
                        WHERE quiz_type = 3 AND result_status = 1 AND stud_id = :stud_id";
                                    $stmt = $conn->prepare($sqlTotalPassed);
                                    $stmt->bindParam(':stud_id', $row['stud_id'], PDO::PARAM_INT);
                                    $stmt->execute();
                                    $passedCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_exams_passed'] ?? 0;
                                }

                                // Calculate adjusted rate
                                if ($selectedQuizType == 1 || $selectedQuizType == 2) {
                                    $rate = ($passedCount / $all_modules_count) * 100;
                                    $efficiency = $passedCount / max($attempts, 1);
                                    $adjustedRate = $rate * $efficiency;
                                } elseif ($selectedQuizType == 3) {
                                    $adjustedRate = 100 / max($attempts, 1);
                                }

                                $row['adjusted_rate'] = $adjustedRate;
                                $row['attempts'] = $attempts;
                                $row['passedCount'] = $passedCount;

                                $students[] = $row;
                            }

                            // Sort by pass rate descending
                            usort($students, function ($a, $b) {
                                return $b['adjusted_rate'] <=> $a['adjusted_rate'];
                            });

                            $rank = 1;
                            foreach ($students as $row):
                            ?>
                                <tr class="text-center" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <td>
                                        <?php if ($rank <= 3): ?>
                                            <?php
                                            switch ($rank) {
                                                case 1:
                                                    echo '<img width="100" src="./GIF/gold.gif" alt="Gold">';
                                                    break;
                                                case 2:
                                                    echo '<img width="100" src="./GIF/silver.gif" alt="Silver">';
                                                    break;
                                                case 3:
                                                    echo '<img width="100" src="./GIF/bronze.gif" alt="Bronze">';
                                                    break;
                                            }
                                            ?>
                                        <?php else: ?>
                                            <strong><?= $rank . 'th'; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: larger;">
                                        <strong><?= htmlspecialchars($row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']); ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['attempts']); ?></td>
                                    <td>
                                        <?php
                                        if ($selectedQuizType == 1 || $selectedQuizType == 2) {
                                            echo htmlspecialchars($row['passedCount']) . " / " . htmlspecialchars($all_modules_count);
                                        } elseif ($selectedQuizType == 3) {
                                            echo ($row['passedCount'] > 0 ? "Yes (" . $row['passedCount'] . ")" : "No");
                                        }
                                        ?>
                                    </td>
                                    <td><?= number_format($row['adjusted_rate'], 2) . "%"; ?></td>
                                </tr>
                                <?php $rank++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No records found for students.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>