<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];

    // Fetch courses and their quiz counts along with the module_id
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
                WHERE quiz_type = 2
                GROUP BY course_id, module_id) r 
            ON c.course_id = r.course_id
            WHERE 
                c.program_id = :program_id";

    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($courses)) {
        // Ensure that passed_attempts and failed_attempts are set to 0 for courses without records
        foreach ($courses as &$course) {
            $course['passed_attempts'] = isset($course['passed_attempts']) ? $course['passed_attempts'] : 0;
            $course['failed_attempts'] = isset($course['failed_attempts']) ? $course['failed_attempts'] : 0;
        }
        unset($course); // unset reference variable to prevent accidental modification

        // Check if all pass rates are zero
        $noResultsFound = (array_sum(array_column($courses, 'passed_attempts')) == 0) && (array_sum(array_column($courses, 'failed_attempts')) == 0);

        $overallPassedAttempts = array_sum(array_column($courses, 'passed_attempts'));
        $overallFailedAttempts = array_sum(array_column($courses, 'failed_attempts'));
        $overallTotalAttempts = $overallPassedAttempts + $overallFailedAttempts;
        $overallAveragePassRate = $overallTotalAttempts > 0 ? ($overallPassedAttempts / $overallTotalAttempts) * 100 : 0;
    } else {
        $noResultsFound = true;
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body>

    <div class="wrapper">

        <?php include 'sidebar.php'; ?>

        <div class="container mt-3 mb-3">
            <div class="row justify-content-center mt-2">
                <div class="text-center mb-2 mt-3">
                    <h1>Quiz Report</h1>
                </div>

                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">

                    <div id="myChart" style="border: 1px solid lightblue;
                                    padding: 10px;
                                    box-sizing: border-box;
                                    border-radius: 15px; 
                                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                    height: 525px;">
                    </div>

                </div>
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
                        <?php foreach ($uniqueCourses as $index => $course) : ?>
                            <a href="test_course_modules.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
                                    <div class="card-body" style="padding: 0.5rem;">
                                        <h5 class="card-title" style="font-size: 1rem;"><?php echo $course['course_code'] . ' -  ' . $course['course_name']; ?></h5>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Student who answered:
                                            <?php
                                            $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE course_id = :course_id AND quiz_type = 2");
                                            $stmtAnswered->bindValue(':course_id', $course['course_id']);
                                            if (!$stmtAnswered->execute()) {
                                                echo "Error executing query: " . implode(" ", $stmtAnswered->errorInfo());
                                            } else {
                                                $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
                                                $answeredStudents = $answeredData['answered'];

                                                // Calculate total students enrolled in the program
                                                $totalStudents = 0;
                                                if ($answeredStudents > 0) {
                                                    $stmtTotalStudents = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS total_students FROM tbl_student WHERE program_id = :program_id");
                                                    $stmtTotalStudents->bindValue(':program_id', $program_id);
                                                    if (!$stmtTotalStudents->execute()) {
                                                        echo "Error executing query: " . implode(" ", $stmtTotalStudents->errorInfo());
                                                    } else {
                                                        $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
                                                        $totalStudents = $totalStudentsData['total_students'] ?? 0; // Set to 0 if null
                                                    }
                                                }

                                                echo $answeredStudents . " / " . $totalStudents;
                                            }
                                            ?>
                                        </p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Module Passed: <?php echo $course['passed_attempts']; ?></p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $course['failed_attempts'] + $course['passed_attempts']; ?></p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Rate:
                                            <?php
                                            $totalAttempts = $course['failed_attempts'] + $course['passed_attempts'];
                                            $passRate = ($totalAttempts > 0) ? number_format(($course['passed_attempts'] / $totalAttempts) * 100, 2) : 0;
                                            echo $passRate . "%";
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No courses found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoBP4U4W6V10KsyKr1vZ95x0LIflLfI/tOM4pR1wtANUN1+" crossorigin="anonymous"></script>

    <script>
        google.charts.load('current', {
            'packages': ['corechart']
        });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {

            // Set Data using PHP-generated data
            const data = google.visualization.arrayToDataTable([
                ['Course', 'Pass Rate', {
                    role: 'annotation'
                }],
                <?php
                $totalPassRate = 0;

                foreach ($uniqueCourses as $course) {
                    $totalAttempts = $course['failed_attempts'] + $course['passed_attempts'];
                    $passRate = ($totalAttempts > 0) ? number_format(($course['passed_attempts'] / $totalAttempts) * 100, 2) : 0;

                    $totalPassRate += $passRate;
                ?>['<?php echo $course['course_code']; ?>', <?php echo $passRate; ?>, '<?php echo $passRate; ?>%'],
                <?php } ?>

                <?php
                $overallPassRate = ($totalPassRate / count($uniqueCourses));
                ?>

                // Add Overall section
                ['Overall', <?php echo $overallPassRate; ?>, '<?php echo number_format($overallPassRate, 2); ?>%'],

            ]);

            // Set Options
            const options = {
                title: 'Pass Rates by Course',
                hAxis: {
                    title: 'Pass Rate (%)',
                    minValue: 0,
                    format: 'decimal',
                },
                vAxis: {
                    title: 'Course',
                },
                bars: 'horizontal', // Display bars horizontally
                legend: {
                    position: 'none', // Hide legend
                },
            };

            // Draw
            const chart = new google.visualization.BarChart(document.getElementById('myChart'));
            chart.draw(data, options);
        }
    </script>
</body>

</html>