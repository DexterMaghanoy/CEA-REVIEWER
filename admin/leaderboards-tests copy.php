<?php


require("../api/db-connect.php");
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
$all_modules_count = $totalCoursesResult['total_courses_count'] ?? 1;
?>

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
                <?php $rank = 1; ?>
                <?php while ($row = $stmtTopStudents->fetch(PDO::FETCH_ASSOC)): ?>
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
                        <td><?= htmlspecialchars($row['result_status_count']); ?></td>
                        <td>
                            <?php
                            if ($selectedQuizType == 1) {
                                echo htmlspecialchars($row['passed_modules_count']) . " / " . htmlspecialchars($all_modules_count);
                            } elseif ($selectedQuizType == 2) {
                                $sqlTotalPassed = "SELECT COUNT(DISTINCT module_id) AS total_quizzes_passed 
                                                  FROM tbl_result 
                                                  WHERE quiz_type = 2 AND result_status = 1 AND stud_id = :stud_id";
                                $stmt = $conn->prepare($sqlTotalPassed);
                                $stmt->bindParam(':stud_id', $row['stud_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $passed = $stmt->fetch(PDO::FETCH_ASSOC)['total_quizzes_passed'] ?? 0;
                                echo "$passed / $all_modules_count";
                            } elseif ($selectedQuizType == 3) {
                                $sqlTotalPassed = "SELECT COUNT(DISTINCT module_id) AS total_exams_passed 
                                                  FROM tbl_result 
                                                  WHERE quiz_type = 3 AND result_status = 1 AND stud_id = :stud_id";
                                $stmt = $conn->prepare($sqlTotalPassed);
                                $stmt->bindParam(':stud_id', $row['stud_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $passed = $stmt->fetch(PDO::FETCH_ASSOC)['total_exams_passed'] ?? 0;
                                echo ($passed > 0 ? "Yes ($passed)" : "No");
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $attempts = $row['result_status_count'] ?? 1;
                            $passedCount = $row['passed_modules_count'] ?? 0;

                            if ($selectedQuizType == 1 || $selectedQuizType == 2) {
                                $rate = ($passedCount / $all_modules_count) * 100;
                                $efficiency = ($passedCount / max($attempts, 1));
                                $adjustedRate = $rate * $efficiency;
                                echo number_format($adjustedRate, 2) . "%";
                            } elseif ($selectedQuizType == 3) {
                                echo number_format(100 / max($attempts, 1), 2) . "%";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php $rank++; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No records found for students.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    const hamBurger = document.querySelector(".toggle-btn");
    hamBurger?.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });

    document.getElementById("yearDropdown")?.addEventListener("change", function() {
        window.location.href = `?program_id=<?= $selectedProgramId ?>&quiz_type=<?= $selectedQuizType ?>&created_at=${this.value}`;
    });
</script>