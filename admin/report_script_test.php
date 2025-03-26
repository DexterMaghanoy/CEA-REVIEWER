

<?php
$prog_id = isset($_GET['program_id']) ? $_GET['program_id'] : null;
$selected_year = isset($_GET['created_at']) ? $_GET['created_at'] : date('Y'); // Default to current year if not provided

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
$grandTotalAttempts = 0;

foreach ($uniqueCourses as $course) {
    // Ensure failed_attempts and passed_attempts are integers
    $failedAttempts = (int)($course['failed_attempts'] ?? 0);
    $passedAttempts = (int)($course['passed_attempts'] ?? 0);

    // Calculate total attempts per course
    $totalAttempts = $failedAttempts + $passedAttempts;
    $grandTotalAttempts += $totalAttempts;

    // Calculate pass rate
    $passRates[$course['course_code']] = ($totalAttempts > 0) ? (($passedAttempts / $totalAttempts) * 100) : 0;
}


?>

<!-- Updated Frontend with Fixed Total Attempts Calculation -->
<?php foreach ($uniqueCourses as $index => $course) : ?>
    <?php
    $passRate = $passRates[$course['course_code']] ?? 0;

    // Fetch students who answered
    $stmtAnswered = $conn->prepare("
        SELECT COUNT(DISTINCT stud_id) AS answered 
        FROM tbl_result 
        WHERE course_id = :course_id 
        AND quiz_type = :quiz_type 
        AND YEAR(created_at) = :created_year
    ");
    $stmtAnswered->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
    $stmtAnswered->bindValue(':quiz_type', $quiz_type, PDO::PARAM_INT);
    $stmtAnswered->bindValue(':created_year', $selected_year, PDO::PARAM_STR);
    $stmtAnswered->execute();
    $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
    $answeredStudents = $answeredData['answered'] ?? 0;

    // Fetch total module count
    $stmtTotalModules = $conn->prepare("
        SELECT COUNT(module_id) AS total_modules 
        FROM tbl_module 
        WHERE course_id = :course_id
    ");
    $stmtTotalModules->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
    $stmtTotalModules->execute();
    $totalModuleData = $stmtTotalModules->fetch(PDO::FETCH_ASSOC);
    $totalModules = $totalModuleData['total_modules'] ?? 0;
    ?>

    <a href="report_results_test.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>&quiz_type=<?php echo $quiz_type; ?>">
        <div class="card subject-<?php echo ($index % 3) + 1; ?> mb-1"
            style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
            <div class="card-body" style="padding: 0.5rem;">
                <h5 class="card-title" style="font-size: 1rem;">
                    <?php echo '<img height="25" width="35" src="../GIF/book-write.gif" class="rounded-circle"> ' . htmlspecialchars($course['course_code']) . ' - ' . htmlspecialchars($course['course_name']); ?>
                </h5>

                <p style="font-size: 0.8rem; margin-bottom: 0;">
                    Student answered: <?php echo htmlspecialchars($answeredStudents) . " / " . htmlspecialchars($allStudentbyProgram); ?>
                </p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">
                    Total Modules: <?php echo htmlspecialchars($totalModules); ?>
                </p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">
                    Passed & Attempts: <?php echo htmlspecialchars($course['passed_attempts']) . " / " . htmlspecialchars($course['failed_attempts'] + $course['passed_attempts']); ?>
                </p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">
                    Pass Rate: <?php echo number_format($passRate, 2); ?>%
                </p>
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
        const passRates = <?php echo json_encode($passRates); ?>;
        const chartData = [
            ['Course', 'Pass Rate', {
                role: 'annotation'
            }]
        ];

        Object.keys(passRates).forEach(courseCode => {
            const passRate = passRates[courseCode];
            chartData.push([courseCode, passRate, passRate.toFixed(2) + '%']);
        });

        const totalPassRate = chartData.slice(1).reduce((sum, row) => sum + row[1], 0);
        const overallAveragePassRate = totalPassRate / (chartData.length - 1);
        chartData.push(['Overall', overallAveragePassRate, overallAveragePassRate.toFixed(2) + '%']);

        const data = google.visualization.arrayToDataTable(chartData);
        const options = {
            title: 'Test Pass Rate',
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
                title: 'Course'
            },
            bars: 'horizontal',
            legend: {
                position: 'none'
            },
            annotations: {
                textStyle: {
                    fontSize: 12,
                    bold: true,
                    color: '#000'
                }
            },
            tooltip: {
                textStyle: {
                    fontSize: 14
                },
                trigger: 'focus'
            },
            height: 500
        };

        const chart = new google.visualization.BarChart(document.getElementById('myChartTest'));
        chart.draw(data, options);
    }
</script>