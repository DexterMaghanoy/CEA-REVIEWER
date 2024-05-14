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
                    WHERE quiz_type = 3
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

        <div class="container">
            
        <?php include 'back.php'; ?>
            <div class="row justify-content-center">
                <div class="text-center">
                    <h1>Exam Report</h1>
                </div>


                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">

                    <div id="myChart" style="width:100%; max-width:100%; height:100%;">
                    </div>



                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        // Function to draw the chart
                        function drawChart() {
                            const courseData = <?php echo json_encode($courses); ?>;
                            var chartData = [
                                ['Course', 'Pass Rate', {
                                    role: 'style'
                                }]
                            ]; // Initialize chart data array

                            // Initialize an object to store pass rates for each course_id
                            var coursePassRates = {};

                            // Loop through each course data
                            courseData.forEach(function(course) {
                                // Initialize pass rate for the current course
                                var passRate = 0;

                                // Calculate pass rate only if there are attempts
                                if (course.passed_attempts + course.failed_attempts > 0) {
                                    passRate = 100 * course.passed_attempts / (course.passed_attempts + course.failed_attempts);
                                }

                                // Store pass rate for the current course_id
                                if (!coursePassRates[course.course_id]) {
                                    coursePassRates[course.course_id] = [];
                                }

                                coursePassRates[course.course_id].push(passRate);
                            });

                            // Loop through each course_id and add its pass rate to chartData
                            for (var courseId in coursePassRates) {
                                // Get the course object based on the courseId
                                var course = courseData.find(c => c.course_id == courseId);
                                // Get the course code
                                var courseCode = course ? course.course_code : ''; // If course is not found, use an empty string
                                // Calculate average pass rate for the current course_id
                                var averagePassRate = coursePassRates[courseId].reduce(function(a, b) {
                                    return a + b;
                                }, 0) / coursePassRates[courseId].length;

                                // Add course data to chartData
                                chartData.push([courseCode, averagePassRate, getRandomColor()]);
                            }

                            // Calculate overall average pass rate
                            var overallAveragePassRate = chartData.reduce(function(sum, row, index) {
                                // Skip header row
                                if (index === 0) return sum;
                                return sum + row[1]; // row[1] contains pass rate
                            }, 0) / (chartData.length - 1); // Exclude header row from count

                            // Add overall pass rate to chart data
                            chartData.push(['Overall', overallAveragePassRate, getRandomColor()]);

                            // Set Data
                            const data = google.visualization.arrayToDataTable(chartData);

                            // Set Options
                            const options = {
                                title: 'Exam Pass Rates',
                                chartArea: {
                                    width: '50%'
                                },
                                hAxis: {
                                    title: 'Pass Rate',
                                    minValue: 0,
                                    maxValue: 100
                                },
                                vAxis: {
                                    title: 'Course'
                                },
                                bars: 'horizontal',
                                legend: {
                                    position: 'none'
                                },
                                tooltip: {
                                    isHtml: true,
                                    textStyle: {
                                        fontSize: 14
                                    },
                                    trigger: 'focus'
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
                        <?php
                        // Create an associative array to store courses indexed by course_id
                        $uniqueCourses = [];
                        foreach ($courses as $course) {
                            $courseId = $course['course_id'];
                            // Check if course_id exists in uniqueCourses array
                            if (!isset($uniqueCourses[$courseId])) {
                                // If not, add it to the array
                                $uniqueCourses[$courseId] = $course;
                            } else {
                                // If course_id already exists, consolidate the data
                                $uniqueCourses[$courseId]['passed_attempts'] += $course['passed_attempts'];
                                $uniqueCourses[$courseId]['failed_attempts'] += $course['failed_attempts'];
                            }
                        }
                        ?>
                        <?php foreach ($uniqueCourses as $index => $course) : ?>
                            <!-- Debug output -->
                            <a href="exam_course_modules.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>">


                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1">
                                    <div class="card-body" style="padding: 0.5rem;">
                                        <h5 class="card-title" style="font-size: 1rem;"><?php echo $course['course_code'] . ' -  ' . $course['course_name']; ?></h5>
                                        <!-- Display consolidated data for attempts -->

                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Student who answered:
                                            <?php
                                            $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE course_id = :course_id AND quiz_type = 3");
                                            $stmtAnswered->bindValue(':course_id', $course['course_id']);
                                            if (!$stmtAnswered->execute()) {
                                                echo "Error executing query: " . implode(" ", $stmtAnswered->errorInfo());
                                            } else {
                                                $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
                                                $answeredStudents = $answeredData['answered'];

                                                // Calculate total students enrolled in the program
                                                $totalStudents = 0;
                                                if ($answeredStudents > 0) {
                                                    $stmtTotalStudents = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS total_students FROM tbl_result WHERE program_id = :program_id");
                                                    $stmtTotalStudents->bindValue(':program_id', $program_id);
                                                    if (!$stmtTotalStudents->execute()) {
                                                        echo "Error executing query: " . implode(" ", $stmtTotalStudents->errorInfo());
                                                    } else {
                                                        $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
                                                        $totalStudents = $totalStudentsData['total_students'];
                                                    }
                                                }

                                                echo $answeredStudents . " / " . $totalStudents;
                                            }
                                            ?>
                                        </p>



                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Exam Passed: <?php echo $course['passed_attempts']; ?></p>
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