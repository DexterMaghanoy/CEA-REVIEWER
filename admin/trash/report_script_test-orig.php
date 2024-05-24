<?php
$prog_id = isset($_GET['program_id']) ? $_GET['program_id'] : null;
foreach ($uniqueCourses as $index => $course) :
?>

    <a href="report_results_test.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>&quiz_type=<?php echo isset($_GET['quiz_type']) ? $_GET['quiz_type'] : 1; ?>">
        <div <?php echo ($quiz_type != 1) ? 'hidden' : ''; ?> class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
            <div class="card-body" style="max-height: 140px;">

                <h5 class="card-title" style="font-size: 0.9rem;">
                    <?php echo '<img height="25" width="35" src="../GIF/book-write.gif"> ' . $course['course_code'] . ' -  ' . $course['course_name']; ?>
                </h5>

                <p style="font-size: 0.8rem; margin-bottom: 0;">Student who answered:
                    <?php
                    $answeredStudents = getAnsweredStudentsCount($course['course_id'], $quiz_type, $conn);
                    $totalStudents = getTotalStudentsCount($program_id, $conn);
                    echo $answeredStudents . " / " . $totalStudents;
                    ?>
                </p>
                <?php
                $total_passed_attempts = getPassedAttemptsCount($course['course_id'], $conn);
                $total_failed_attempts = getFailedAttemptsCountForProgram( $program_id, $course['course_id'], $conn);
                $total_attempts = $total_passed_attempts + $total_failed_attempts;

                if ($total_attempts > 0 && countModulesInCourse($course['course_id'], $conn) > 0 && $totalStudents > 0) {
                    // Calculate the average
                    $average = (($total_passed_attempts / $total_attempts) / countModulesInCourse($course['course_id'], $conn) / $totalStudents) * 100;
                } else {
                    // Handle the case when total_attempts is 0
                    $average = 0;
                }

                ?>

                <p style="font-size: 0.7rem; margin-bottom: 0;">Modules: <?php echo (countModulesInCourse($course['course_id'], $conn) > 0) ? countModulesInCourse($course['course_id'], $conn) : 0; ?></p>
                <p style="font-size: 0.7rem; margin-bottom: 0;">Passed & Attempts: <span style="color: green; font-weight: bold; font-size: 12px; "><?php echo ($total_attempts > 0) ? getPassedAttemptsCount($prog_id, $conn) : 0; ?></span> / <span style="color: red; font-weight: bold;  font-size: 12px;"><?php echo $total_attempts; ?></span> </p>
                <p style="font-size: 0.7rem; margin-bottom: 0;">Average: <?php echo number_format($average, 2) . "%"; ?></p>
            </div>
        </div>
    </a>
<?php endforeach; ?>


<?php

global $course_id;
// Function to get the count of students who answered for a course
function getAnsweredStudentsCount($course_id, $quiz_type, $conn)
{
    $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE course_id = :course_id AND quiz_type = :quiz_type AND YEAR(created_at) = :created_year");
    $stmtAnswered->bindValue(':course_id', $course_id);
    $stmtAnswered->bindValue(':quiz_type', $quiz_type);
    $stmtAnswered->bindValue(':created_year', date('Y'), PDO::PARAM_STR); // Using current year
    $stmtAnswered->execute();
    $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
    return $answeredData['answered'];
}

// Function to get the total count of students in a program
function getTotalStudentsCount($program_id, $conn)
{
    $stmtTotalStudents = $conn->prepare("SELECT COUNT(stud_id) AS total_students FROM tbl_student WHERE program_id = :program_id AND YEAR(created_at) = :created_year");
    $stmtTotalStudents->bindValue(':program_id', $program_id, PDO::PARAM_INT); // Assuming program_id is an integer
    $stmtTotalStudents->bindValue(':created_year', date('Y'), PDO::PARAM_STR); // Using current year
    $stmtTotalStudents->execute();
    $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
    return $totalStudentsData['total_students'];
}


// Function to count modules in a course
function countModulesInCourse($course_id, $conn)
{
    $stmtModules = $conn->prepare("SELECT COUNT(*) AS module_count FROM tbl_module  WHERE course_id = :course_id");
    $stmtModules->bindValue(':course_id', $course_id);
    $stmtModules->execute();
    $moduleCountData = $stmtModules->fetch(PDO::FETCH_ASSOC);
    return $moduleCountData['module_count'];
}


function getFailedAttemptsCountForProgram( $program_id, $course_id, $conn)
{
    $stmtFailedAttempts = $conn->prepare("SELECT COUNT(*) AS failed_attempts FROM tbl_result WHERE program_id = :program_id AND course_id = :course_id AND quiz_type = 1 AND result_status = 0");
    $stmtFailedAttempts->bindValue(':program_id', $program_id);
    $stmtFailedAttempts->bindValue(':course_id', $course_id);
    $stmtFailedAttempts->execute();
    $failedAttemptsData = $stmtFailedAttempts->fetch(PDO::FETCH_ASSOC);
    if ($failedAttemptsData === false) {
        return 0;
    }
    return $failedAttemptsData['failed_attempts'];
}


// Function to get the count of students who answered for a course and result_type = 0 (failed attempts)
function getPassedAttemptsCount($course_id, $conn)
{
    $stmtPassedAttempts = $conn->prepare("SELECT COUNT(*) AS passed_attempts FROM tbl_result WHERE course_id = :course_id AND quiz_type = 1 AND result_status = 1");
    $stmtPassedAttempts->bindValue(':course_id', $course_id);
    $stmtPassedAttempts->execute();
    $passedAttemptsData = $stmtPassedAttempts->fetch(PDO::FETCH_ASSOC);
    if ($passedAttemptsData === false) {
        return 0;
    }
    return $passedAttemptsData['passed_attempts'];
}



// Define the function
function getProgStudentCount($prog_id, $conn)
{
    $stmtTotalStudents = $conn->prepare("SELECT COUNT(stud_id) AS total_students FROM tbl_student WHERE program_id = :program_id AND stud_status = :stud_status");

    $stmtTotalStudents->bindValue(':program_id', $prog_id, PDO::PARAM_INT);
    $stmtTotalStudents->bindValue(':stud_status', 1, PDO::PARAM_INT); // Assuming stud_status for active students is 1
    $stmtTotalStudents->execute();
    $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
    return $totalStudentsData['total_students'];
}


?>

<?php
$prog_id = isset($_GET['program_id']) ? $_GET['program_id'] : null;

$data = [];
$total_average = 0;
$total_courses = count($uniqueCourses);

foreach ($uniqueCourses as $index => $course) {
    $total_passed_attempts = getPassedAttemptsCount($course['course_id'], $conn);
    $total_failed_attempts = getFailedAttemptsCountForProgram( $program_id, $course_id, $conn);
    $All_Students_By_Program = $totalStudents;
    $total_attempts = $total_passed_attempts + $total_failed_attempts;

    if ($total_attempts > 0) {
        $average = (($total_passed_attempts / $total_attempts) / $All_Students_By_Program) * 100;
        $average = (($total_passed_attempts / $total_attempts) / countModulesInCourse($course['course_id'], $conn) / $totalStudents) * 100;
    } else {
        $average = 0;
    }

    $average = number_format($average, 2); // Format average to 2 decimal places

    $answeredStudents = getAnsweredStudentsCount($course['course_id'], $quiz_type, $conn);
    $totalStudents = getTotalStudentsCount($program_id, $conn);

    $data[] = [
        'course_code' => $course['course_code'],
        'average' => $average,
        'total_students' => $totalStudents
    ];

    $total_average += $average;
}

$overall_average = $total_courses > 0 ? $total_average / $total_courses : 0;
$overall_average = number_format($overall_average, 2); // Format overall average to 2 decimal places

?>


<script>
    // Load the Visualization API and the corechart package.
    google.charts.load('current', {
        'packages': ['corechart']
    });

    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        // Prepare data for Google Charts
        const chartData = [
            ['Course Code', 'Average', {
                role: 'annotation'
            }],
            <?php foreach ($data as $courseData) : ?>['<?php echo $courseData['course_code']; ?>', <?php echo $courseData['average']; ?>, '<?php echo $courseData['average']; ?>%'],
            <?php endforeach; ?>['Overall Average', <?php echo $overall_average; ?>, '<?php echo $overall_average; ?>%']
        ];

        const googleData = google.visualization.arrayToDataTable(chartData);

        // Set chart options
        const options = {
            title: 'Course Average Scores',
            hAxis: {
                title: 'Average'
            },
            vAxis: {
                title: 'Course Code'
            },
            legend: 'none'
        };

        // Instantiate and draw our chart, passing in some options.
        const chart = new google.visualization.BarChart(document.getElementById('myChartTest'));
        chart.draw(googleData, options);
    }
</script>