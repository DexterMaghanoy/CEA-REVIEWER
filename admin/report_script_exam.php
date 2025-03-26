<a href="report_results_exam.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>&quiz_type=<?php echo $quiz_type; ?>">

    <div
        <?php
        if (isset($_GET['quiz_type'])) {
            $quiz_type = $_GET['quiz_type'];
            $hideExamCard = ($quiz_type == 3) ? '' : 'hidden';
        } else {
            $hideExamCard = 'hidden';
        }
        echo ' ' . $hideExamCard;
        ?>
        class="card subject-<?php echo ($index % 3) + 1; ?> mb-1"
        style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3)); border-radius: 10px;">
        <div class="card-body" style="padding: 0.5rem;">
            <?php
            $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result 
         WHERE quiz_type = :quiz_type AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtAnswered->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtAnswered->bindValue(':created_year', date('Y'), PDO::PARAM_INT); // Use current year
            $stmtAnswered->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtAnswered->execute();
            $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
            $answeredStudents = $answeredData['answered'];


            // Get total students who answered
            $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered 
FROM tbl_result 
WHERE quiz_type = :quiz_type AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtAnswered->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtAnswered->bindValue(':created_year', date('Y'), PDO::PARAM_INT); // Use current year
            $stmtAnswered->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtAnswered->execute();
            $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
            $answeredStudents = $answeredData['answered'];

            // Get students who passed the module
            $stmtModulePassed = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS ModulePassed 
FROM tbl_result 
WHERE quiz_type = :quiz_type AND result_status = 1 AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtModulePassed->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtModulePassed->bindValue(':created_year', date('Y'), PDO::PARAM_INT);
            $stmtModulePassed->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtModulePassed->execute();
            $answeredModulePassed = $stmtModulePassed->fetch(PDO::FETCH_ASSOC);
            $answeredModulePassedStudents = $answeredModulePassed['ModulePassed'];

            // Get students who attempted the module (both passed & failed)
            $stmtModuleAttempts = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS ModuleAttempts 
FROM tbl_result 
WHERE quiz_type = :quiz_type AND (result_status = 1 OR result_status = 0) AND YEAR(created_at) = :created_year AND program_id = :program_id");
            $stmtModuleAttempts->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
            $stmtModuleAttempts->bindValue(':created_year', date('Y'), PDO::PARAM_INT);
            $stmtModuleAttempts->bindParam(':program_id', $program_id, PDO::PARAM_INT);
            $stmtModuleAttempts->execute();
            $answeredModuleAttempts = $stmtModuleAttempts->fetch(PDO::FETCH_ASSOC);
            $answeredModuleAttemptsStudents = $answeredModuleAttempts['ModuleAttempts'];

            // Get total students in the program
            $stmtTotalStudents = $conn->prepare("SELECT COUNT(stud_id) AS total_students 
FROM tbl_student 
WHERE program_id = :program_id AND YEAR(created_at) = :created_year");
            $stmtTotalStudents->bindValue(':program_id', $program_id, PDO::PARAM_INT);
            $stmtTotalStudents->bindValue(':created_year', date('Y'), PDO::PARAM_INT);
            $stmtTotalStudents->execute();
            $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
            $totalStudents = $totalStudentsData['total_students'];

            echo '<img height="25" width="35" src="../GIF/questions-and-answers.gif" class="rounded-circle"> EXAM';
            ?>
            <p>Students who answered: <?php echo $answeredStudents . " / " . $totalStudents; ?></p>
            <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $answeredModuleAttemptsStudents; ?></p>
        </div>
    </div>
</a>

<div id="myChartExam" style="width: 100%; height: 400px;"></div>

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
        const chartData = [
            ['Course', 'Pass Rate', {
                role: 'style'
            }, {
                role: 'annotation'
            }]
        ];

        <?php
        if ($answeredModuleAttemptsStudents > 0 && $totalStudents > 0) {
            $SubjectRate = ($answeredModulePassedStudents / $answeredModuleAttemptsStudents) * 100;
            echo "chartData.push(['Overall', $SubjectRate, getRandomColor(), 'Exam Rate: " . number_format($SubjectRate, 2) . "%']);";
        } else {
            echo "chartData.push(['Overall', 0, getRandomColor(), 'No data available']);";
        }
        ?>

        const data = google.visualization.arrayToDataTable(chartData);

        const options = {
            title: 'Student Performance',
            hAxis: {
                title: 'Pass Rate',
                minValue: 0,
                maxValue: 100,
                format: '#\'%\'',
                titleTextStyle: {
                    fontSize: 14,
                    bold: true
                }
            },
            vAxis: {
                title: 'EXAM',
                titleTextStyle: {
                    fontSize: 14,
                    bold: true
                }
            },
            chartArea: {
                width: '50%',
                height: '70%'
            },
            bar: {
                groupWidth: '75%'
            },
            colors: ['#4285F4']
        };

        const chart = new google.visualization.BarChart(document.getElementById('myChartExam'));
        chart.draw(data, options);
    }
</script>