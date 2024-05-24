<?php
session_start();
require '../api/db-connect.php';

$program_id = $_GET['program_id'] ?? $_SESSION['program_id'];
$quiz_type = $_GET['quiz_type'] ?? 1;
$created_at = $_GET['created_at'] ?? date('Y');

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
}
unset($course);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
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



                <div style="border: 1px solid lightblue; /* Adds a light blue border for emphasis */
                padding: 10px; /* Optional: Adds some padding inside the div */
                box-sizing: border-box; /* Ensures padding and border are included in the element's total width and height */
                border-radius: 15px; /* Makes the border rounded */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Adds a subtle box shadow */
                " id="myChart" class="col-sm"></div>
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
                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="  background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
                                    <
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-sm">
                            <div class="card h-100" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
                                <div class="card-body">
                                    <h5 class="card-title" style="font-size: 1rem;">No Results</h5>
                                    <p class="card-text" style="font-size: 0.8rem;">
                                    <h1>No matches found.</h1>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        google.charts.load('current', {
            'packages': ['corechart']
        });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            const courseData = <?php echo json_encode($courses); ?>;
            const chartData = [
                ['Course', 'Pass Rate', {
                    role: 'style'
                }]
            ];
            const coursePassRates = {};

            courseData.forEach(course => {
                const passRate = (course.passed_attempts + course.failed_attempts) > 0 ?
                    100 * course.passed_attempts / (course.passed_attempts + course.failed_attempts) : 0;
                if (!coursePassRates[course.course_id]) {
                    coursePassRates[course.course_id] = [];
                }
                coursePassRates[course.course_id].push(passRate);
            });

            for (const courseId in coursePassRates) {
                const course = courseData.find(c => c.course_id == courseId);
                const courseCode = course ? course.course_code : '';
                const averagePassRate = coursePassRates[courseId].reduce((a, b) => a + b, 0) / coursePassRates[courseId].length;
                chartData.push([courseCode, averagePassRate, getRandomColor()]);
            }

            const overallAveragePassRate = chartData.reduce((sum, row, index) => {
                if (index === 0) return sum;
                return sum + row[1];
            }, 0) / (chartData.length - 1);
            chartData.push(['Overall', overallAveragePassRate, getRandomColor()]);

            const data = google.visualization.arrayToDataTable(chartData);
            const options = {
                title: 'Test Pass Rate by Module',
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

            const chart = new google.visualization.BarChart(document.getElementById('myChart'));
            chart.draw(data, options);
        }

        function getRandomColor() {
            return '#' + Math.floor(Math.random() * 16777215).toString(16);
        }
    </script>
</body>

</html>


<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>