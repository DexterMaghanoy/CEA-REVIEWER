<a href="report_results_exam.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>&quiz_type=<?php echo $quiz_type; ?>">

    <div <?php
            if (isset($_GET['quiz_type'])) {
                $quiz_type = $_GET['quiz_type'];

                if ($quiz_type == 3) {
                    $hideExamCard = ''; // Show content
                } else {
                    $hideExamCard = 'hidden'; // Hide content
                }
            } else {
                // If quiz_type parameter does not exist
                $hideExamCard = 'hidden'; // Hide content
            }

            echo $hideExamCard;
            ?> class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
        <div class="card-body" style="padding: 0.5rem;">
            <?php
            $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE quiz_type = :quiz_type AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtAnswered->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtAnswered->bindParam(':created_year', $created_at, PDO::PARAM_STR);
            $stmtAnswered->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtAnswered->execute();
            $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
            $answeredStudents = $answeredData['answered'];

            $stmtModulePassed = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS ModulePassed FROM tbl_result WHERE quiz_type = :quiz_type AND result_status = 1 AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtModulePassed->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtModulePassed->bindParam(':created_year', $created_at, PDO::PARAM_STR);
            $stmtModulePassed->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtModulePassed->execute();
            $answeredModulePassed = $stmtModulePassed->fetch(PDO::FETCH_ASSOC);
            $answeredModulePassedStudents = $answeredModulePassed['ModulePassed'];

            $stmtModuleAttempts = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS ModuleAttempts FROM tbl_result WHERE quiz_type = :quiz_type AND (result_status = 1 OR result_status = 0) AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtModuleAttempts->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtModuleAttempts->bindParam(':created_year', $created_at, PDO::PARAM_STR);
            $stmtModuleAttempts->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtModuleAttempts->execute();
            $answeredModuleAttempts = $stmtModuleAttempts->fetch(PDO::FETCH_ASSOC);
            $answeredModuleAttemptsStudents = $answeredModuleAttempts['ModuleAttempts'];

            $stmtTotalStudents = $conn->prepare("SELECT COUNT(stud_id) AS total_students FROM tbl_student WHERE program_id = :program_id AND YEAR(created_at) = :created_year");
            $stmtTotalStudents->bindValue(':program_id', $program_id, PDO::PARAM_INT); // Assuming program_id is an integer
            $stmtTotalStudents->bindValue(':created_year', date('Y'), PDO::PARAM_STR); // Using current year
            $stmtTotalStudents->execute();
            $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
            $totalStudents = $totalStudentsData['total_students'];

            echo '<img height="25" width="35" src="../GIF/questions-and-answers.gif">';
            echo ' EXAM';
            ?>
            <p>Students who answered: <?php echo $answeredStudents . " / " . $totalStudents; ?></p>
            <p style="font-size: 0.8rem; margin-bottom: 0;">Module Passed: <?php echo $answeredModulePassedStudents; ?></p>
            <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $answeredModuleAttemptsStudents; ?></p>
        </div>
    </div>

</a>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {
        'packages': ['corechart']
    });
    google.charts.setOnLoadCallback(drawChart);

    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    function drawChart() {
        // Fetching data from PHP
        const courseData = <?php echo json_encode($courses); ?>;
        const chartData = [
            ['Course', 'Pass Rate', { role: 'style' }, { role: 'annotation' }]
        ];

        <?php
        $SubjectRate = (($answeredModulePassedStudents / $answeredModuleAttemptsStudents) * 100)/ $totalStudents;

        echo "chartData.push(['Overall', $SubjectRate, getRandomColor(), 'Subject Rate: " . number_format($SubjectRate, 2) . "%']);";
        ?>

        const options = {
            title: 'Student Performance by Module',
            hAxis: {
                title: 'Pass Rate',
                minValue: 0,
                maxValue: 100
            },
            vAxis: {
                title: 'Exam'
            },
            chartArea: {
                width: '50%',
                height: '70%'
            }
        };

        const chart = new google.visualization.BarChart(document.getElementById('myChartExam'));
        chart.draw(google.visualization.arrayToDataTable(chartData), options);
    }
</script>