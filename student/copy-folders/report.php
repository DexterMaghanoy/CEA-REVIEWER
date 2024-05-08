<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];

    // Fetch courses and their quiz counts
    $sql = "SELECT c.*, COUNT(r.result_id) AS quiz_count
            FROM tbl_course c
            LEFT JOIN tbl_result r ON c.course_id = r.course_id AND r.quiz_type = 2
            WHERE c.program_id = :program_id
            GROUP BY c.course_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
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
            <div class="row justify-content-center mt-2">
                <div class="text-center mb-4 mt-3">
                    <h1>Report</h1>
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

                        function drawChart() {
                            const courseData = <?php echo json_encode($courses); ?>; // Assuming $courses is your PHP variable
                            var chartData = [
                                ['Subject', 'Pass Rate', {
                                    role: 'style'
                                }]
                            ]; // Add style role

                            courseData.forEach(function(course) {
                                var passRate = calculatePassRate(course);
                                var color = getRandomColor(); // Generate a random color
                                chartData.push([course.course_code, passRate, color]);
                            });

                            // Set Data
                            const data = google.visualization.arrayToDataTable(chartData);

                            // Set Options
                            const options = {
                                title: 'Quiz Rate per Subject',
                                is3D: true,
                                sliceVisibilityThreshold: 0, // show all slices, even tiny ones
                                tooltip: {
                                    isHtml: true,
                                    textStyle: {
                                        fontSize: 14
                                    },
                                    trigger: 'focus' // now it's on hover
                                }
                            };

                            // Draw
                            const chart = new google.visualization.BarChart(document.getElementById('myChart')); // Use BarChart instead of PieChart
                            chart.draw(data, options);
                        }

                        // Function to calculate pass rate
                        function calculatePassRate(course) {
                            var percent = 100;
                            var quizCount = course.quiz_count !== null && course.quiz_count !== 0 ? percent / course.quiz_count : 0;
                            return quizCount;
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
                <div class="col-md">
                    <?php if (!empty($courses)) : ?>
                        <?php foreach ($courses as $index => $course) : ?>
                            <!-- Debug output -->
                            <a href="student_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_SESSION['stud_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $course['course_code']; ?></h5>
                                        <p class="card-text"><?php echo $course['course_name'] . '   -    ' . $course['course_id']; ?></p>
                                        <p class="card-text">
                                            <?php echo '   Attempts    ' . '   -    ' .
                                            '5' . ' Times' ?>
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