<?php
session_start();
require("../api/db-connect.php");

// Check if user is logged in and program ID is set
if (!isset($_SESSION['stud_id']) || !isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['stud_id'];
$program_id = $_SESSION['program_id'];

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
                    GROUP BY 
                        s.stud_id
                    HAVING
                        COUNT(r.result_status) > 0  -- Filter out students without any result_status
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


<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboards</title>
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

        <div class="container">
            <div class="row justify-content-center mt-4">
                <div class="col-md-10">
                    <div class="text-center ">
                        <h1 class="mb-4">Leaderboards: <?php echo htmlspecialchars($program_name); ?></h1>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-custom" id="courseTable">
                            <caption>List of Students</caption>
                            <thead class="table-dark" style="text-align: center;">
                                <tr>
                                    <th scope="col">Rank</th>
                                    <th scope="col">Fullname</th>
                                    <th scope="col">Total Attempts</th>
                                    <th scope="col">Module Passed</th>
                                    <th scope="col">Pass Rate</th>
                                </tr>
                            </thead>
                            <tbody style="background-color: #d2f0d6;">
                                <?php if ($totalRows > 0) : ?>
                                    <?php $rank = 1; ?>
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

                                            <td><?php echo htmlspecialchars($row['passed_modules_count']); ?></td>

                                            <td>
                                                <?php
                                                $passRatePercentage = (($row['passed_result_status_count']) / $row['result_status_count']) * 100;
                                                if ($row['passed_modules_count'] !=  $totalModulesQ) {
                                                    echo number_format(($row['passed_modules_count'] / $totalModulesQ) * 100, 2) . "%";
                                                } else {
                                                    echo number_format($passRatePercentage, 2) . "%";
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hamBurger = document.querySelector(".toggle-btn");
        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>
</body>

</html>