<?php
session_start();
require '../api/db-connect.php';

$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : (isset($_SESSION['program_id']) ? intval($_SESSION['program_id']) : 0);
$quiz_type = isset($_GET['quiz_type']) ? intval($_GET['quiz_type']) : 1;
$created_at = isset($_GET['created_at']) ? $_GET['created_at'] : date('Y');

global $hideTestGraph;
global $hideQuizGraph;
global $hideExamGraph;
global $hideExamCard;
global $hideTestCard;
global $hideQuizCard;
global $hideAllGraph;
global $course_id;

$sql = "SELECT 
        c.course_id,
        c.course_code,
        c.course_name,
        r.module_id,
        COALESCE(passed_attempts, 0) AS passed_attempts,
        COALESCE(failed_attempts, 0) AS failed_attempts
    FROM 
        tbl_course c
    LEFT JOIN 
        (SELECT 
            course_id,
            module_id,
            COUNT(CASE WHEN result_status = 1 THEN 1 END) AS passed_attempts,
            COUNT(CASE WHEN result_status = 0 THEN 1 END) AS failed_attempts
        FROM tbl_result 
        WHERE quiz_type = :quiz_type 
        AND YEAR(created_at) = :created_year
        GROUP BY course_id, module_id) r 
    ON c.course_id = r.course_id
    WHERE c.program_id = :program_id";

$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
$result->bindParam(':created_year', $created_at, PDO::PARAM_STR);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as &$course) {
    $course['passed_attempts'] = $course['passed_attempts'] ?? 0;
    $course['failed_attempts'] = $course['failed_attempts'] ?? 0;
    $passRate = isset($passRates[$course['course_code']]) ? $passRates[$course['course_code']] : 0;
    $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE course_id = :course_id AND quiz_type = :quiz_type AND YEAR(created_at) = :created_year");
    $stmtAnswered->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
    $stmtAnswered->bindValue(':quiz_type', $quiz_type, PDO::PARAM_INT);
    $stmtAnswered->bindValue(':created_year', date('Y'), PDO::PARAM_STR);
    $stmtAnswered->execute();
    $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
    $answeredStudents = $answeredData['answered'];

    $stmtTotalStudents = $conn->prepare("SELECT COUNT(stud_id) AS total_students FROM tbl_student WHERE program_id = :program_id AND stud_status = :stud_status  AND YEAR(created_at) = :created_year");
    $stmtTotalStudents->bindValue(':program_id', $program_id, PDO::PARAM_INT);
    $stmtTotalStudents->bindValue(':stud_status', 1, PDO::PARAM_INT); // Assuming stud_status for active students is 1
    $stmtTotalStudents->bindValue(':created_year', date('Y'), PDO::PARAM_STR);
    $stmtTotalStudents->execute();
    $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
    $totalStudents = $totalStudentsData['total_students'];


    $stmtTotalModule = $conn->prepare("SELECT COUNT(*) AS total_module_count 
FROM tbl_module 
WHERE course_id = :course_id 
AND program_id = :program_id");
    $stmtTotalModule->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
    $stmtTotalModule->bindValue(':program_id', $program_id, PDO::PARAM_INT);
    $stmtTotalModule->execute();
    $totalModuleData = $stmtTotalModule->fetch(PDO::FETCH_ASSOC);
    $totalModuleCount = $totalModuleData['total_module_count'];
}

unset($course);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php include 'back.php'; ?>
        <div class="container mt-2">
            <div class="row justify-content-center">
                <div class="text-center mt-3 mb-2">
                    <h1>Report</h1>
                </div>
                <?php include 'topbar.php'; ?>



                <div <?php if (isset($_GET['quiz_type'])) {
                            $quiz_type = $_GET['quiz_type'];

                            if ($quiz_type != 1) {
                                $hideTestGraph = 'hidden';
                            } else {
                                $hideTestGraph = '';
                            }
                        } else {
                            $hideTestGraph = 'hidden';
                        }
                        echo $hideTestGraph; ?> style="border: 1px solid lightblue;
                        padding: 10px;
                        box-sizing: border-box;
                        border-radius: 15px; 
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        height: 100vhl;" id="myChartTest" class="col-sm"></div>

                <div <?php
                        if (isset($_GET['quiz_type'])) {
                            $quiz_type = $_GET['quiz_type'];

                            if ($quiz_type != 2) {
                                $hideQuizGraph = 'hidden';
                            } else {
                                $hideQuizGraph = '';
                            }
                        } else {
                            $hideQuizGraph = 'hidden';
                        }
                        echo $hideQuizGraph;
                        ?> style="border: 1px solid lightblue;
                                    padding: 10px;
                                    box-sizing: border-box;
                                    border-radius: 15px; 
                                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                    height: 100vhl;
                " id="myChartQuiz" class="col-sm"></div>



                <div <?php
                        if (isset($_GET['quiz_type'])) {
                            $quiz_type = $_GET['quiz_type'];

                            if ($quiz_type != 3) {
                                $hideExamGraph = 'hidden';
                            } else {


                                $hideExamGraph = '';
                            }
                        } else {
                            $hideExamGraph = 'hidden';
                        }
                        echo $hideExamGraph;
                        ?> style="border: 1px solid lightblue;
                                    padding: 10px;
                                    box-sizing: border-box;
                                    border-radius: 15px; 
                                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                    height: 525px;
                " id="myChartExam" class="col-sm"></div>

                <div <?php
                        if (isset($_GET['quiz_type'])) {
                            $quiz_type = $_GET['quiz_type'];

                            if ($quiz_type != 3 && $quiz_type != 2 && $quiz_type != 1) {
                                $hideAllGraph = '';
                            } else {
                                $hideAllGraph = 'hidden';
                            }
                        }
                        echo $hideAllGraph;
                        ?> style="border: 1px solid lightblue;
                        padding: 10px;
                        box-sizing: border-box;
                        border-radius: 15px; 
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        height: 100vhl;" id="myChartAll" class="col-sm"></div>


                <div class="col-sm">
                    <?php if (!empty($courses)) : ?>
                        <?php
                        $uniqueCourses = [];
                        foreach ($courses as $course) {
                            $courseId = $course['course_id'];

                            if (!isset($uniqueCourses[$courseId])) {
                                $uniqueCourses[$courseId] = $course;
                            } else {
                                $uniqueCourses[$courseId]['passed_attempts'] += $course['passed_attempts'];
                                $uniqueCourses[$courseId]['failed_attempts'] += $course['failed_attempts'];
                            }
                        }
                        ?>

                        <?php
                        // Get the quiz_type parameter from the URL, defaulting to 0 if not set
                        $quiz_type = isset($_GET['quiz_type']) ? $_GET['quiz_type'] : 0;

                        switch ($quiz_type) {
                            case 1:
                                include 'report_script_test.php';
                                break;
                            case 2:
                                include 'report_script_quiz.php';
                                break;
                            case 3:
                                include 'report_script_exam.php';
                                break;
                            default:
                                include 'report_script_test.php';   
                                break;
                        }
                        ?>


                    <?php else : ?>

                    <?php endif; ?>
                </div>


            </div>
        </div>
    </div>
</body>

</html>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>