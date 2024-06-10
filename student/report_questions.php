<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'], $_SESSION['stud_id'])) {
    $program_id = $_SESSION['program_id'];
    $stud_id = $_SESSION['stud_id'];

    // Modify the SQL query to calculate total attempts
    $sql = "SELECT 
                c.course_id,
                c.course_code,
                c.course_name,
                SUM(passed_attempts) AS passed_attempts,
                SUM(failed_attempts) AS failed_attempts
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
                AND stud_id = :stud_id  /* Filter results by stud_id */
                GROUP BY course_id, module_id) r 
            ON c.course_id = r.course_id
            WHERE 
                c.program_id = :program_id
            GROUP BY 
                c.course_id";

    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->bindParam(':stud_id', $stud_id, PDO::PARAM_INT); // Bind stud_id parameter
    $result->execute();
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($courses)) {
        // Ensure that passed_attempts and failed_attempts are set to 0 for courses without records
        foreach ($courses as &$course) {
            $course['passed_attempts'] = isset($course['passed_attempts']) ? $course['passed_attempts'] : 0;
            $course['failed_attempts'] = isset($course['failed_attempts']) ? $course['failed_attempts'] : 0;
            // Calculate pass rate
            $totalAttempts = $course['failed_attempts'] + $course['passed_attempts'];
            $course['pass_rate'] = $totalAttempts > 0 ? ($course['passed_attempts'] / $totalAttempts) * 100 : 0;
        }
        unset($course); // unset reference variable to prevent accidental modification

        // Encode data for JavaScript
        $chartDataJson = json_encode($courses);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
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

        <div class="container mt-3 mb-3">
            <div class="row justify-content-center mt-2">
                <div class="text-center mb-2 mt-3">
                    <h1>Module Test Report</h1>

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


                    <div id="myChart" class="col-sm mb-3"></div>

                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            const chartData = <?php echo $chartDataJson; ?>;
                            var data = new google.visualization.DataTable();
                            data.addColumn('string', 'Subject');
                            data.addColumn('number', 'Pass Rate');
                            data.addColumn({
                                type: 'string',
                                role: 'style' // Add a style role column
                            });
                            data.addColumn({
                                type: 'string',
                                role: 'annotation'
                            });

                            chartData.forEach(function(course) {
                                var strengthWeakness;
                                var passRateString;

                                if (course.pass_rate === 0 || !course.pass_rate) {
                                    strengthWeakness = 'No record';
                                    passRateString = '0%';
                                } else {
                                    strengthWeakness = course.pass_rate >= 50 ? 'Good' : 'Weak';
                                    passRateString = course.pass_rate.toFixed(2) + '%';
                                }

                                var annotation = passRateString + ' ' + strengthWeakness + ' ';
                                var color = strengthWeakness === 'Good' ? 'green' : (strengthWeakness === 'Weak' ? 'red' : 'gray'); // Determine color based on strength, weakness, or no record

                                data.addRow([course.course_name, course.pass_rate, color, annotation]);
                            });

                            var options = {
                                title: 'Pass Rates by Subject',
                                chartArea: {
                                    width: '50%'
                                },
                                hAxis: {
                                    title: 'Pass Rate (%)',
                                    minValue: 0,
                                    maxValue: 100
                                },
                                vAxis: {
                                    title: 'Subject'
                                },
                                bars: 'horizontal',
                                legend: {
                                    position: 'none'
                                }
                            };

                            var chart = new google.visualization.BarChart(document.getElementById('myChart'));
                            chart.draw(data, options);
                        }
                    </script>




                </div>
                <div class="col-sm">
                    <?php if (!empty($courses)) : ?>
                        <?php foreach ($courses as $index => $course) : ?>
                            <!-- Debug output -->
                            <a href="student_question_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_SESSION['stud_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3)); color: black;   box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08); outline: 1px solid rgba(0, 0, 0, 0.2);">
                                    <div class="card-body" style="padding: 0.5rem;">
                                        <h5 class="card-title" style="font-size: 1rem;"><?php echo $course['course_code'] . ' -  ' . $course['course_name']; ?></h5>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Module Passed: <?php echo $course['passed_attempts']; ?></p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $course['failed_attempts'] + $course['passed_attempts']; ?></p>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Pass Rate: <?php echo number_format($course['pass_rate'], 2); ?>%</p>
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
