<?php

// Set default values for parameters
$program_id = $_GET['program_id'] ?? $_SESSION['program_id'];
$quiz_type = $_GET['quiz_type'] ?? 1;
$created_at = $_GET['created_at'] ?? date('Y');

// Query to fetch course data
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

// Prepare and execute the query
$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->bindParam(':quiz_type', $quiz_type, PDO::PARAM_INT);
$result->bindParam(':created_year', $created_at, PDO::PARAM_STR);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

// Process the fetched data
foreach ($courses as &$course) {
    $course['passed_attempts'] = $course['passed_attempts'] ?? 0;
    $course['failed_attempts'] = $course['failed_attempts'] ?? 0;
}
unset($course);
// Prepare data for the pie chart

// Prepare data for the pie chart
$labels = [];
$data = [];

foreach ($courses as $course) {
    $courseName = $course['course_code'] . ' - ' . $course['course_name'];
    $passedAttempts = $course['passed_attempts'] ?? 0; // Default to 0 if null
    $failedAttempts = $course['failed_attempts'] ?? 0; // Default to 0 if null

    // Calculate total attempts
    $totalAttempts = $passedAttempts + $failedAttempts;

    // Calculate pass rate percentage
    $passRate = ($totalAttempts > 0) ? ($passedAttempts / $totalAttempts) * 100 : 0;

    // Add course name and pass rate to labels and data arrays
    $labels[] = $courseName;
    $data[] = $passRate;
}

// Check if labels array is empty or if all pass rates are 0
$noData = empty($labels) || array_sum($data) == 0;



?>

    <script>
        // Check if there's no data to display
        if (<?php echo $noData ? 'true' : 'false'; ?>) {
            // Display the message if there's no data
            document.getElementById('noDataMessage').style.display = 'block';
        } else {
            // If there's data, render the pie chart
            const pieData = {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: ['#007bff', '#6c757d', '#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6610f2']
                }]
            };

            const ctx1 = document.getElementById('myPieChart').getContext('2d');

            // Draw black border circle
            const centerX = ctx1.canvas.width / 2;
            const centerY = ctx1.canvas.height / 2;
            const radius = Math.min(centerX, centerY);
            ctx1.beginPath();
            ctx1.arc(centerX, centerY, radius, 0, 2 * Math.PI);
            ctx1.strokeStyle = '#000000'; // Black color
            ctx1.lineWidth = 2; // Adjust thickness as needed
            ctx1.stroke();

            const myPieChart = new Chart(ctx1, {
                type: 'pie',
                data: pieData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'left',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label = context.formattedValue;
                                    return  " " + label + "%";
                                }
                            }
                        },
                        datalabels: {
                            color: '#ffffff',
                            font: {
                                weight: 'bold',
                                size: '14'
                            },
                            formatter: function(value, context) {
                                return context.chart.data.labels[context.dataIndex] + ': ' + value + ' %'; // Add "%" to the value
                            }
                        }

                    }
                }
            });


        }
    </script>


    <script>
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>
