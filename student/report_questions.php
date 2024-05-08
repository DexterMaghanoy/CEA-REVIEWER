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
                r.module_id, -- Include module_id from tbl_result
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
                WHERE quiz_type = 1
                GROUP BY course_id) r 
            ON c.course_id = r.course_id
            WHERE 
                c.program_id = :program_id";

    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    // Ensure that passed_attempts and failed_attempts are set to 0 for courses without records
    foreach ($courses as &$course) {
        $course['passed_attempts'] = isset($course['passed_attempts']) ? $course['passed_attempts'] : 0;
        $course['failed_attempts'] = isset($course['failed_attempts']) ? $course['failed_attempts'] : 0;
    }
    unset($course); // unset reference variable to prevent accidental modification

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
                    <h1>Module Test Report</h1>
                </div>


                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">

                    <div id="myChart" style="width:100%; max-width:100%; height:100%;">
                    </div>

                    <!-- JavaScript code for the chart -->
                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        // Function to calculate pass rate
                        function calculatePassRate(course) {
                            var totalAttempts = course.passed_attempts + course.failed_attempts;
                            var passRate = totalAttempts !== 0 ? (100 * course.passed_attempts / totalAttempts) : 0;
                            return passRate; // Return pass rate as percentage
                        }

                        // Function to draw the chart
                        function drawChart() {
                            
                            const courseData = <?php echo json_encode($courses); ?>;

                            var chartData = [
                                ['Subject', 'Pass Rate', {
                                    role: 'style'
                                }]
                            ]; // Initialize chart data array

                            courseData.forEach(function(course) {
                                var passRate = calculatePassRate(course);
                                var color = getRandomColor();
                                chartData.push([course.course_code, passRate, color]); // Push data for each course
                            });

                            // Set Data
                            const data = google.visualization.arrayToDataTable(chartData);

                            // Set Options
                            const options = {
                                title: 'Subject Ratings',
                                is3D: true,
                                sliceVisibilityThreshold: 0,
                                tooltip: {
                                    isHtml: true,
                                    textStyle: {
                                        fontSize: 14
                                    },
                                    trigger: 'focus'
                                },
                                vAxis: {
                                    format: 'percent' // Display percentages on the vertical axis
                                }
                            };

                            // Draw
                            const chart = new google.visualization.BarChart(document.getElementById('myChart'));
                            chart.draw(data, options);
                        }

                        // Function to generate random color
                        function getRandomColor() {
                            var letters = '0123456789ABCDEF';
                            var color = '#';
                            for (var i = 0; i < 6; i++) {
                                color += letters[Math.floor(Math.random() * 16)];
                            }
                            return color;
                        }
                    </script>


                </div>
                <div class="col-sm">
                    <?php if (!empty($courses)) : ?>
                        <?php foreach ($courses as $index => $course) : ?>
                            <!-- Debug output -->
                            <a href="student_question_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_SESSION['stud_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1">
                                    <div class="card-body" style="padding: 0.5rem;">
                                        <h5 class="card-title" style="font-size: 1rem;"><?php echo $course['course_code'] . ' -  ' . $course['course_name'] . ' (Module ID: ' . $course['module_id'] . ')'; ?></h5>

                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Module Passed: <?php echo $course['passed_attempts']; ?></p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $course['failed_attempts'] + $course['passed_attempts']; ?></p>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");
        const sidebar = document.querySelector("#sidebar");
        const mainContent = document.querySelector(".main");

        hamBurger.addEventListener("click", function() {
            sidebar.classList.toggle("expand");
            mainContent.classList.toggle("expand");
        });
    </script>
</body>

</html>