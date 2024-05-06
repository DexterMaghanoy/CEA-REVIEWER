<?php
session_start();

require '../api/db-connect.php';

// Check if session data is set, if not redirect to login page
if (!isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

$program_id = $_SESSION['program_id'];
$year_id = $_SESSION['year_id'];

// Fetch courses for the given program and year
$sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

// Initialize Quiz Percentage
$QuizPercentage = 0;

// Fetch latest quiz result
$sql = "SELECT stud_id, course_id, result_score, total_questions
        FROM tbl_result
        WHERE quiz_type = 2
        ORDER BY result_id DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute();
$QuizResult = $stmt->fetch(PDO::FETCH_ASSOC);

if ($QuizResult) {
    // Calculate pass rate and set Quiz Percentage
    $result_score = $QuizResult['result_score'];
    $total_questions = $QuizResult['total_questions'];
    if ($total_questions > 0) {
        $QuizPercentage = ($result_score / $total_questions) * 100;
    }
}

// Loop through courses to fetch quiz attempts
foreach ($courses as $course) {
    $current_course_id = $course['course_id'];

    // Count quiz attempts for the current course
    $sql = "SELECT COUNT(*) AS quiz_result_count FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND quiz_type = 2";
    $qstmt = $conn->prepare($sql);
    $qstmt->bindParam(":course_id", $current_course_id, PDO::PARAM_INT);
    $qstmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
    $qstmt->execute();

    // Fetch quiz attempts count
    $qresult = $qstmt->fetch(PDO::FETCH_ASSOC);

    if ($qstmt->errorCode() !== '00000') {
        echo "Error: " . $qstmt->errorInfo()[2];
    } else {
        $quizAttempts = $qresult['quiz_result_count'];
        echo "Quiz attempts for course ID $current_course_id: $quizAttempts";
    }
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
                        <!-- Debugging: Placeholder for chart -->
                    </div>
                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            var chartData = [
                                ['Course', 'Mhl']
                            ];

                            <?php
                            // Debugging: Output courses array
                            echo "console.log(" . json_encode($courses) . ");\n";
                            ?>

                            <?php foreach ($courses as $course) : ?>
                                <?php
                                // Calculate quiz percentage for this specific course
                                $courseQuizPercentage = 0; // Initialize to 0 as default
                                // Check if there is a corresponding quiz result for this course
                                if ($QuizResult && $course['course_id'] == $QuizResult['course_id']) {
                                    // Calculate quiz percentage based on the result score and total questions
                                    if ($QuizResult['total_questions'] > 0) {
                                        $courseQuizPercentage = ($QuizResult['result_score'] / $QuizResult['total_questions']) * 100 / $quizAttempts;
                                    }
                                }
                                ?>
                                chartData.push(['<?php echo $course['course_code']; ?>', <?php echo $courseQuizPercentage; ?>]);
                            <?php endforeach; ?>

                            // Debugging: Output chartData array
                            console.log(chartData);

                            // Set Data
                            const data = google.visualization.arrayToDataTable(chartData);

                            // Set Options
                            const options = {
                                title: 'Quiz Pass Rate'
                                // is3D: true // Debugging: Removed is3D
                            };

                            // Draw
                            const chart = new google.visualization.PieChart(document.getElementById('myChart'));
                            chart.draw(data, options);
                        }
                    </script>
                </div>

                <!-- Other HTML content -->
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
