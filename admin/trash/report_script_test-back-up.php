<?php foreach ($uniqueCourses as $index => $course) : ?>
    <a href="report_results_test.php?course_id=<?php echo $course['course_id']; ?>&user_id=<?php echo $_SESSION['user_id']; ?>&module_id=<?php echo $course['module_id']; ?>&quiz_type=<?php echo $quiz_type; ?>">
        <div <?php if (isset($_GET['quiz_type'])) {
                    $quiz_type = $_GET['quiz_type'];

                    if ($quiz_type != 1) {
                        $hideQuizCard = 'hidden';
                    } else {
                        $hideQuizCard = '';
                    }
                }
                echo $hideQuizCard; ?> class="card subject-<?php echo ($index % 3) + 1; ?> mb-1" style="  background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
            <div class="card-body" style="padding: 0.5rem;">
                <h5 class="card-title" style="font-size: 1rem;"><?php echo '<img height="25" width="35" src="../GIF/book-write.gif"> ' . $course['course_code'] . ' -  ' . $course['course_name']; ?></h5>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Student who answered:
                    <?php
                    $stmtAnswered = $conn->prepare("SELECT COUNT(DISTINCT stud_id) AS answered FROM tbl_result WHERE course_id = :course_id AND quiz_type = $quiz_type  AND YEAR(created_at) = :created_year");
                    $stmtAnswered->bindValue(':course_id', $course['course_id']);
                    $stmtAnswered->bindParam(':created_year', $created_at, PDO::PARAM_STR);
                    $stmtAnswered->execute();
                    $answeredData = $stmtAnswered->fetch(PDO::FETCH_ASSOC);
                    $answeredStudents = $answeredData['answered'];

                    $stmtTotalStudents = $conn->prepare("SELECT COUNT(stud_id) AS total_students FROM tbl_student WHERE program_id = :program_id AND YEAR(created_at) = :created_year");
                    $stmtTotalStudents->bindValue(':program_id', $program_id, PDO::PARAM_INT); // Assuming program_id is an integer
                    $stmtTotalStudents->bindValue(':created_year', date('Y'), PDO::PARAM_STR); // Using current year
                    $stmtTotalStudents->execute();
                    $totalStudentsData = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
                    $totalStudents = $totalStudentsData['total_students'];

                    echo $answeredStudents . " / " . $totalStudents;
                    ?>
                </p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Module Passed: <?php echo $course['passed_attempts']; ?></p>
                <p style="font-size: 0.8rem; margin-bottom: 0;">Attempts: <?php echo $course['failed_attempts'] + $course['passed_attempts']; ?></p>
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
        const courseData = <?php echo json_encode($courses); ?>;
        const chartData = [
            ['Course', 'Pass Rate', {
                role: 'annotation'
            }]
        ];
        let totalPassRateSum = 0;
        let courseCount = 0;

        const coursePassRates = {};

        courseData.forEach(course => {
            const passRate = (course.passed_attempts + course.failed_attempts) > 0 ?
                (course.passed_attempts / (course.passed_attempts + course.failed_attempts)) * 100 : 0;
            const courseCode = course.course_code;

            if (!coursePassRates[courseCode]) {
                coursePassRates[courseCode] = {
                    totalPassRate: 0,
                    count: 0
                };
            }

            coursePassRates[courseCode].totalPassRate += passRate;
            coursePassRates[courseCode].count += 1;
        });

        for (const courseCode in coursePassRates) {
            const averagePassRate = coursePassRates[courseCode].totalPassRate / coursePassRates[courseCode].count;
            chartData.push([courseCode, averagePassRate, averagePassRate.toFixed(2) + '%']);
            totalPassRateSum += averagePassRate;
            courseCount += 1;
        }

        const overallAveragePassRate = totalPassRateSum / courseCount;
        chartData.push(['Overall', overallAveragePassRate, overallAveragePassRate.toFixed(2) + '%']);

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

        const chart = new google.visualization.BarChart(document.getElementById('myChartTest'));
        chart.draw(data, options);
    }
</script>
