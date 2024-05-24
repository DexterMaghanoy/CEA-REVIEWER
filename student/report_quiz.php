<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'], $_SESSION['stud_id'])) {
    $program_id = $_SESSION['program_id'];
    $stud_id = $_SESSION['stud_id'];

    // Fetch courses and their quiz counts
    $sql = "SELECT 
                c.course_id,
                c.course_code,
                c.course_name,
                COALESCE(passed_attempts, 0) AS passed_attempts,
                COALESCE(failed_attempts, 0) AS failed_attempts
            FROM 
                tbl_course c
            LEFT JOIN 
                (SELECT 
                    course_id,
                    COUNT(CASE WHEN result_status = 1 THEN 1 END) AS passed_attempts,
                    COUNT(CASE WHEN result_status = 0 THEN 1 END) AS failed_attempts
                FROM tbl_result 
                WHERE quiz_type = 2
                AND stud_id = :stud_id  /* Filter results by stud_id */
                GROUP BY course_id) r 
            ON c.course_id = r.course_id
            WHERE 
                c.program_id = :program_id";

    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->bindParam(':stud_id', $stud_id, PDO::PARAM_INT); // Bind stud_id parameter
    $result->execute();
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    // Ensure that passed_attempts and failed_attempts are set to 0 for courses without records
    foreach ($courses as &$course) {
        $course['passed_attempts'] = isset($course['passed_attempts']) ? $course['passed_attempts'] : 0;
        $course['failed_attempts'] = isset($course['failed_attempts']) ? $course['failed_attempts'] : 0;

        // Calculate pass rate for each course
        $total_attempts = $course['passed_attempts'] + $course['failed_attempts'];
        $pass_rate = $total_attempts > 0 ? round(($course['passed_attempts'] / $total_attempts) * 100, 2) : 0;

        // Add pass rate to the course data
        $course['pass_rate'] = $pass_rate . "%";
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
                    <h1>Subject Quizzes Report</h1>
                </div>


                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">

                    <style>
                        #myChart {
                            border: 1px solid lightblue;
                            padding: 10px;
                            box-sizing: border-box;
                            border-radius: 15px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            height: 350px;
                        }
                    </style>

                    <div id="myChart" style="width:100%; max-width:100%; height:100%;">
                    </div>

                    <!-- JavaScript code for the chart -->
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
                                title: 'Pass Rates by Subject',
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
                        <?php foreach ($courses as $index => $course) : ?>
                            <a href="student_quiz_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_SESSION['stud_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
                                    <div class="card-body" style="padding: 0.5rem;">
                                        <h5 class="card-title" style="font-size: 1rem;"><?php echo $course['course_code'] . ' -  ' . $course['course_name']; ?></h5>
                                        <?php
                                        $total_attempts = $course['failed_attempts'] + $course['passed_attempts'];
                                        if ($total_attempts > 0) {
                                            $pass_rate = 100 * $course['passed_attempts'] / $total_attempts;
                                        } else {
                                            $pass_rate = 'N/A';
                                            $total_attempts = '0';
                                        }
                                        ?>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Pass Rate: <?php echo is_numeric($pass_rate) ? number_format($pass_rate, 2) . '%' : $pass_rate; ?></p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $total_attempts; ?></p>
                                        <?php
                                        // Prepare SQL query to fetch score for specific course ID and stud_id
                                        $sqlScore = "SELECT result_score,total_questions FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND result_status = 1 AND quiz_type = 2";
                                        $resultScore = $conn->prepare($sqlScore);
                                        $resultScore->bindParam(':course_id', $course['course_id'], PDO::PARAM_INT);
                                        $resultScore->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
                                        $resultScore->execute();
                                        // Fetch the result
                                        $score = $resultScore->fetch(PDO::FETCH_ASSOC);
                                        // Display the score if available
                                        if ($score !== false) {
                                            echo "<p style='font-size: 0.8rem; margin-bottom: 0;'>Score: " . $score['result_score'] . " / " . $score['total_questions'] . "</p>";
                                        } else {
                                            echo "<p style='font-size: 0.8rem; margin-bottom: 0;'>Score: N/A</p>";
                                        }
                                        ?>
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