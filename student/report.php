<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
    $year_id = $_SESSION['year_id'];

    // Prepare SQL query to fetch courses for the given program and year
    $sql = "SELECT *, COUNT(r.result_id) AS quiz_count
            FROM tbl_course c
            LEFT JOIN tbl_result r ON c.course_id = r.course_id AND r.quiz_type = 2
            WHERE c.program_id = :program_id
            GROUP BY c.course_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);

    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    // Iterate through courses to calculate quiz percentage

} else {
    // Redirect to login page if session data is not set
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
                <div class="col-md">
                    <br>
                    <div class="text-center mb-4">
                    </div>
                    <div id="myChart" style="width:100%; max-width:600px; height:500px;">
                    </div>
                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            var chartData = [
                                ['Course', 'Quiz Pass Rate']
                            ];
                            <?php foreach ($courses as $course) : ?>
                                <?php
                                // Calculate the pass rate as a percentage
                                $passRate = ($course['quiz_count'] !== 0) ? (100 / $course['quiz_count']) : 0;
                                ?>
                                chartData.push(['<?php echo $course['course_code']; ?>', <?php echo $passRate; ?>]);
                            <?php endforeach; ?>

                            // Set Data
                            const data = google.visualization.arrayToDataTable(chartData);

                            // Set Options
                            var options = {
                                title: 'Quiz Pass Rate (%)',
                                is3D: true,
                                sliceLabel: 'none', // Hides slice labels
                                tooltip: {

                                    trigger: 'hover',
                                    isHtml: false,
                                },
                                // Set the slice label to the course code
                                sliceVisibilityThreshold: 0, // Show all slices
                            };

                            // Draw
                            const chart = new google.visualization.PieChart(document.getElementById('myChart'));
                            chart.draw(data, options);
                        }
                    </script>



                </div>

                <div class="col-md">
                    <?php if (isset($courses) && !empty($courses)) : ?>
                        <?php foreach ($courses as $index => $course) : ?>
                            <a href="student_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_SESSION['stud_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $course['course_code']; ?></h5>
                                        <p class="card-text"><?php echo $course['course_name']; ?></p>
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