<?php
$prog_id = isset($_GET['program_id']) ? $_GET['program_id'] : null;
$selected_year = isset($_GET['created_at']) ? $_GET['created_at'] : date('Y'); // Get year from URL, default to current year

// Fetch total count of students by program and year
$stmtTotalStudentsByProgram = $conn->prepare("
    SELECT COUNT(stud_id) AS total_students 
    FROM tbl_student 
    WHERE program_id = :program_id 
    AND YEAR(created_at) = :selected_year
");
$stmtTotalStudentsByProgram->bindValue(':program_id', $prog_id, PDO::PARAM_INT);
$stmtTotalStudentsByProgram->bindValue(':selected_year', $selected_year, PDO::PARAM_INT);
$stmtTotalStudentsByProgram->execute();
$totalStudentsDataByProgram = $stmtTotalStudentsByProgram->fetch(PDO::FETCH_ASSOC);
$allStudentbyProgram = $totalStudentsDataByProgram['total_students'] ?? 0;

// Handle zero students case
if ($allStudentbyProgram == 0) {
    echo '<div style="
        text-align: center; 
        font-weight: bold; 
        padding: 20px; 
        border-radius: 10px; 
        background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));
        height: 100%;
        width: 100%;
        box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
    ">
        No data to display
    </div>';
    exit;
}


$passRates = [];
foreach ($uniqueCourses as $course) {
    $totalAttempts = $course['failed_attempts'] + $course['passed_attempts'];
    $passRates[$course['course_code']] = ($totalAttempts > 0) ? (($course['passed_attempts'] / $totalAttempts) * 100) : 0;
}
?>

<?php foreach ($uniqueCourses as $index => $course) : ?>
    <?php
    // Retrieve the calculated pass rate for the current course
    $passRate = $passRates[$course['course_code']];

    // Fetch total count of students who answered for the current module
    $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE course_id = :course_id AND quiz_type = :quiz_type AND YEAR(created_at) = :created_year");
    $stmtAnswered->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
    $stmtAnswered->bindValue(':quiz_type', $quiz_type, PDO::PARAM_INT);
    $stmtAnswered->bindValue(':created_year', date('Y'), PDO::PARAM_STR);
    $stmtAnswered->execute();
    $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
    $answeredStudents = $answeredData['answered'];
    ?>

    <!-- HTML code to display course information -->
    <a href="report_results_quiz.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>&quiz_type=<?php echo $quiz_type; ?>">
        <div <?php if (isset($_GET['quiz_type'])) {
                    $quiz_type = $_GET['quiz_type'];
                    if ($quiz_type != 2) {
                        $hideTestCard = 'hidden';
                    } else {
                        $hideTestCard = '';
                    }
                }
                echo $hideTestCard; ?> class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
            <div class="card-body" style="padding: 0.5rem;">
                <h5 class="card-title" style="font-size: 1rem;">
                    <?php echo '<img height="25" width="35" src="../GIF/book-write.gif" class="rounded-circle"> ' . $course['course_code'] . ' -  ' . $course['course_name']; ?>
                </h5>

                <p style="font-size: 0.8rem; margin-bottom: 0;">Student who answered: <?php echo $answeredStudents . " / " . $allStudentbyProgram; ?></p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Module Passed: <?php echo $course['passed_attempts']; ?></p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $course['failed_attempts'] + $course['passed_attempts']; ?></p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Pass Rate: <?php echo number_format($passRate, 2); ?>%</p>
            </div>
        </div>
    </a>
<?php endforeach; ?>
<script>
    google.charts.load('current', {
        'packages': ['corechart']
    });
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        // Fetching pass rates from PHP
        const passRates = <?php echo json_encode($passRates); ?>;
        const chartData = [
            ['Course', 'Pass Rate', {
                role: 'annotation'
            }]
        ];

        // Convert pass rates object into an array of arrays
        Object.keys(passRates).forEach(courseCode => {
            const passRate = passRates[courseCode];
            const passRatePercentage = passRate.toFixed(2) + '%';
            chartData.push([courseCode, passRate, passRatePercentage]);
        });

        // Calculate overall average pass rate
        const totalPassRate = chartData.slice(1).reduce((sum, row) => sum + row[1], 0);
        const overallAveragePassRate = totalPassRate / (chartData.length - 1);

        // Push the overall average pass rate to chart data
        const overallAveragePassRatePercentage = overallAveragePassRate.toFixed(2) + '%';
        chartData.push(['Overall', overallAveragePassRate, overallAveragePassRatePercentage]);

        // Prepare chart data and options
        const data = google.visualization.arrayToDataTable(chartData);
        const options = {
            title: 'Quiz Pass Rate by Subject',
            chartArea: {
                width: '50%'
            },
            hAxis: {
                title: 'Pass Rate',
                minValue: 0,
                maxValue: 100,
                format: '#\'%\''
            },
            vAxis: {
                title: 'Subject'
            },
            bars: 'horizontal',
            legend: {
                position: 'none'
            },
            annotations: {
                alwaysOutside: false,
                textStyle: {
                    fontSize: 12,
                    bold: true,
                    color: '#000',
                    auraColor: 'none'
                }
            },
            tooltip: {
                isHtml: true,
                textStyle: {
                    fontSize: 14
                },
                trigger: 'focus'
            },
            height: 500
        };

        // Draw the chart
        const chart = new google.visualization.BarChart(document.getElementById('myChartQuiz'));
        chart.draw(data, options);
    }
</script>