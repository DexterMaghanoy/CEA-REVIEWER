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
                COALESCE(CAST(passed_attempts AS UNSIGNED), 0) AS passed_attempts,
                COALESCE(CAST(failed_attempts AS UNSIGNED), 0) AS failed_attempts
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
        $course['passed_attempts'] = (int) $course['passed_attempts'];
        $course['failed_attempts'] = (int) $course['failed_attempts'];

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        <?php
        include 'sidebar.php';
        ?>

        <div class="container mt-3 mb-3">
            <div class="row justify-content-center mt-2">
                <div class="text-center mb-2 mt-3">
                    <h1>Quiz Report</h1>
                </div>


                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">

                    <style>
                        #myQuizChart {
                            border: 1px solid lightblue;
                            padding: 10px;
                            box-sizing: border-box;
                            border-radius: 15px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            height: 350px;
                        }
                    </style>

                    <div id="myQuizChart" class="col-sm mb-4"></div>



                    <!-- JavaScript code for the chart -->
                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            const courseData = <?php echo json_encode($courses); ?>;
                            var chartData = [];

                            courseData.forEach(function(course) {
                                var passRate = 0;

                                if (course.passed_attempts + course.failed_attempts > 0) {
                                    passRate = 100 * course.passed_attempts / (course.passed_attempts + course.failed_attempts);
                                }

                                var strengthWeakness;
                                var passRateString;

                                if (passRate === 0 || !passRate) {
                                    strengthWeakness = 'No record';
                                    passRateString = '0%';
                                } else {
                                    strengthWeakness = passRate >= 50 ? 'Good' : 'Weak';
                                    passRateString = passRate.toFixed(2) + '%';
                                }

                                var annotation = passRateString + ' ' + strengthWeakness + ' ';

                                chartData.push([course.course_name, passRate, strengthWeakness === 'Good' ? 'green' : (strengthWeakness === 'Weak' ? 'red' : 'gray'), annotation]);
                            });

                            var data = new google.visualization.DataTable();
                            data.addColumn('string', 'Subject');
                            data.addColumn('number', 'Pass Rate');
                            data.addColumn({
                                type: 'string',
                                role: 'style'
                            });
                            data.addColumn({
                                type: 'string',
                                role: 'annotation'
                            });
                            data.addRows(chartData);

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

                            const chart = new google.visualization.BarChart(document.getElementById('myQuizChart'));
                            chart.draw(data, options);
                        }
                    </script>
                </div>

                <div class="col-sm">
                    <?php if (!empty($courses)) : ?>
                        <?php foreach ($courses as $index => $course) : ?>
                            <a href="student_quiz_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_SESSION['stud_id']; ?>">
                                <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3)); color: black;   box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08); outline: 1px solid rgba(0, 0, 0, 0.2);">

                                    <div class="card-body" style="padding: 0.5rem;">
                                        <h5 class="card-title" style="font-size: 1rem;"><?php echo $course['course_code'] . ' -  ' . $course['course_name']; ?></h5>
                                        <?php
                                        // $total_attempts = $course['failed_attempts'] + $course['passed_attempts'];
                                        if ($total_attempts > 0) {
                                            // $pass_rate = 100 * $course['passed_attempts'] / $total_attempts;
                                        } else {
                                            $pass_rate = 'N/A';
                                            $total_attempts = '0';
                                        }
                                        ?>

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
                                            echo "<p style='font-size: 0.8rem; margin-bottom: 0;'>Passed Score: " . $score['result_score'] . " / " . $score['total_questions'] . "</p>";
                                        } else {
                                            echo "<p style='font-size: 0.8rem; margin-bottom: 0;'>Score: N/A</p>";
                                        }
                                        ?>
                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $total_attempts; ?></p>


                                        <p style="font-size: 0.8rem; margin-bottom: 0;">Pass Rate: <?php echo is_numeric($pass_rate) ? number_format($pass_rate, 2) . '%' : $pass_rate; ?></p>

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