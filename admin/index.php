<?php
session_start();
require '../api/db-connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
try {
    // Query to get program names and count of students in each program
    $stmt = $conn->prepare("SELECT p.program_name, COUNT(s.stud_id) AS num_students 
        FROM tbl_program p
        LEFT JOIN tbl_student s ON p.program_id = s.program_id 
        WHERE p.program_status = 1 
        GROUP BY p.program_id");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $data = [];
    foreach ($programs as $program) {
        $labels[] = $program['program_name'];
        $data[] = $program['num_students'];
    }
} catch (PDOException $e) {
    $errorMessage = 'Database Error: ' . $e->getMessage();
}

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "UPDATE tbl_student SET stud_status = 0 WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";

    $stmt = $conn->prepare($sql);

    $stmt->execute();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <style>
        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;

        }

        .card:hover,
        .card:focus {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .card-body {
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));



        }

        .card-header {

            background: linear-gradient(to left, rgba(95, 170, 252, 0.5), rgba(175, 210, 255, 0.5));




        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;

        }

        .card-text {
            font-size: 1.2rem;

        }

        .card:active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div style="overflow-y: auto" class=" main p-3" ;>

            <style>
                .custom-card {
                    background: linear-gradient(to top, rgba(255, 255, 255, 0.95), rgba(0, 0, 0, 0.2));
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    height: 60px;
                    /* Reduced vertical padding */
                }

                .custom-card h1 {
                    margin-top: 0;
                    margin-bottom: 0px;
                    font-size: 24px;
                    color: #333;
                }
            </style>

            <div class="col-md-12 card custom-card mb-2">
                <div class="card-body">
                    <h1>Dashboard</h1>
                </div>
            </div>

            <div class="row">

                <div class="col-md-4">

                    <div class="card text-bg-light text-black shadow-lg mb-2">
                        <div class="card-header">Time</div>
                        <div class="card-body">
                            <canvas id="clockCanvas" width="120" height="120"></canvas>

                            <div id="clock"></div>
                        </div>
                    </div>



                    <a href="user.php" class="text-black text-decoration-none">
                        <div class="card text-bg-light text-black shadow-lg mb-2">
                            <div class="card-header">
                                <h6> Faculty & Program Head </h6>
                            </div>
                            <div class="card-body">


                                <?php
                                try {
                                    // Assuming you're using PDO and have a database connection
                                    $stmt = $conn->prepare("SELECT COUNT(*) AS TEACHER_COUNT FROM tbl_user WHERE type_id = 3 AND user_status = 1");
                                    $stmt->execute();
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $teacherCount = $row['TEACHER_COUNT'];
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                ?>

                                <p>
                                <p>🤵</p> User Accounts: <?php echo $teacherCount; ?></p>

                            </div>
                        </div>

                    </a>

                    <a href="student.php" class="text-black text-decoration-none">
                        <div class="card text-bg-light text-black shadow-lg mb-3">
                            <div class="card-header">
                                <h6> Students</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                try {
                                    // Assuming you're using PDO and have a database connection
                                    $stmt = $conn->prepare("SELECT COUNT(*) AS STUDENT_COUNT FROM tbl_student WHERE stud_status = 1");
                                    $stmt->execute();
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $studentCount = $row['STUDENT_COUNT'];
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                ?>
                                <p>
                                <p>🎓</p> Number of students: <?php echo $studentCount; ?></p>
                            </div>

                        </div>
                    </a>

                </div>
                <div class="col-md-4">
                    <a href="program.php" class="text-white text-decoration-none">
                        <div class="card text-bg-light text-black shadow-lg mb-3">
                            <div class="card-header">Courses & Students</div>
                            <div class="card-body">

                                <?php if (isset($errorMessage)) : ?>
                                    <p class="card-text text-danger"><?php echo $errorMessage; ?></p>
                                <?php else : ?>
                                    <?php if ($programs) : ?>
                                        <ul class="list-group">
                                            <?php foreach ($programs as $program) : ?>
                                                <li class="list-group-item"><?php echo '<span style="font-size: 1.2em; font-weight: bold; color:black;" class="badge badge-primary badge-pill">' . $program['num_students'] . '</span>' . '📖  ' . $program['program_name']; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else : ?>
                                        <p>No programs found.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>

                    <div class="card text-bg-light text-black shadow-lg mb-3">
                        <div class="card-header">
                            <form method="post" action="">
                                <select style="width: 250px;" name="programSelect" id="programSelect" class="form-control">
                                    <option value="all" <?php if (!isset($_POST['programSelect']) || $_POST['programSelect'] == 'all') echo 'selected'; ?>>All Subjects <span style="float: right;">🔻</span></option>


                                    <?php
                                    try {
                                        // Assuming you're using PDO and have a database connection
                                        $stmt = $conn->prepare("SELECT program_id, program_name FROM tbl_program");
                                        $stmt->execute();
                                        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        // Display the program options
                                        foreach ($programs as $program) {
                                            echo '<option value="' . $program['program_id'] . '"';
                                            if (isset($_POST['programSelect']) && $_POST['programSelect'] == $program['program_id']) {
                                                echo ' selected';
                                            }
                                            echo '>' . $program['program_name'] . '</option>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<option value="" disabled>Error fetching programs</option>';
                                    }
                                    ?>
                                </select>
                            </form>

                        </div>
                        <a href="subjects.php" class="text-white text-decoration-none">

                            <div class="card-body" id="courseList" style="max-height: 300px; overflow-y: auto;">
                                <?php
                                try {
                                    // Assuming you're using PDO and have a database connection
                                    if (isset($_POST['programSelect']) && $_POST['programSelect'] != 'all') {
                                        $progChoosen = $_POST['programSelect'];
                                        $stmt = $conn->prepare("SELECT course_name FROM tbl_course WHERE program_id = :program_id");
                                        $stmt->bindParam(':program_id', $progChoosen);
                                    } else {
                                        // If 'All Programs' is selected, retrieve all courses
                                        $stmt = $conn->prepare("SELECT course_name FROM tbl_course");
                                    }

                                    $stmt->execute();
                                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Display the courses
                                    if ($courses) {
                                        echo '<ul class="list-group">';
                                        foreach ($courses as $course) {
                                            echo '<li class="list-group-item">' . "📚 " . $course['course_name'] . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        // If no courses found for the selected program
                                        echo '<p style = "color:black;";>No courses found.</p>';
                                    }
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                ?>
                            </div>

                    </div>

                    </a>

                    <script>
                        // JavaScript to handle displaying courses based on selected program
                        document.getElementById("programSelect").addEventListener("change", function() {
                            this.form.submit(); // Submit the form when program selection changes
                        });
                    </script>




                </div>


                <div class="col-md-4">
                    <!-- <a href="program.php" class="text-white text-decoration-none"> -->
                    <div class="card text-bg-light text-black shadow-lg mb-3">
                        <div class="card-header">Pass Rate

                            <?php
                            // Set default values for parameters
                            $program_id = $_GET['program_id'] ?? $_SESSION['program_id'];
                            $quiz_type = $_GET['quiz_type'] ?? 1;
                            $created_at = $_GET['created_at'] ?? date('Y');
                            ?>

                        </div>
                        <div class="card-body">
                            <select style="width: 250px; margin-right: 180px;" id="quizTypeDropdown" name="quizType" class="form-select">

                                <option value="1" <?php if ($quiz_type == 1) echo " selected"; ?>>TEST</option>
                                <option value="2" <?php if ($quiz_type == 2) echo " selected"; ?>>QUIZ</option>
                                <option value="3" <?php if ($quiz_type == 3) echo " selected"; ?>>EXAM</option>
                            </select>


                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const quizTypeDropdown = document.getElementById('quizTypeDropdown');

                                    quizTypeDropdown.addEventListener('change', function() {
                                        const selectedQuizType = this.value; // Get the selected value from the dropdown

                                        // Update the URL with the selected value
                                        const currentUrl = new URL(window.location.href);
                                        currentUrl.searchParams.set('quiz_type', selectedQuizType);
                                        window.location.href = currentUrl.toString();
                                    });
                                });
                            </script>


                            <?php

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
                            $labels = [];
                            $data = [];

                            foreach ($courses as $course) {
                                $courseName =  $course['course_name'];
                                $passedAttempts = $course['passed_attempts'];
                                $failedAttempts = $course['failed_attempts'];

                                // Calculate total attempts and pass rate percentage
                                $totalAttempts = $passedAttempts + $failedAttempts;
                                $passRate = ($totalAttempts > 0) ? ($passedAttempts / $totalAttempts) * 100 : 0;

                                // Add course name and pass rate to labels and data arrays
                                $labels[] = $courseName;
                                $data[] = $passRate;
                            }

                            // Check if labels array is empty or if all pass rates are 0
                            $noData = empty($labels) || array_sum($data) == 0;
                            ?>

                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                            <!-- Place this in your HTML file where you want the chart to appear -->
                            <canvas id="myPieChart" width="400" height="400"></canvas>
                            <div id="noDataMessage" style="display: none;">No data to display.</div>

                            <script>
                                <?php
                                // Assuming $noData, $labels, and $data are defined in your PHP code
                                // Ensure $labels and $data are properly sanitized and contain valid data
                                ?>
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

                                    const canvas = document.getElementById('myPieChart');
                                    const ctx = canvas.getContext('2d');

                                    // Draw black border circle
                                    const centerX = canvas.width / 2;
                                    const centerY = canvas.height / 2;
                                    const radius = Math.min(centerX, centerY);
                                    ctx.beginPath();
                                    ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
                                    ctx.strokeStyle = '#000000'; // Black color
                                    ctx.lineWidth = 2; // Adjust thickness as needed
                                    ctx.stroke();

                                    const myPieChart = new Chart(ctx, {
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
                                                                label = ': ';
                                                            }
                                                            label = context.formattedValue;
                                                            return label;
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




                        </div>

                    </div>
                    <!-- </a> -->



                </div>

            </div>
        </div>
    </div>

</body>

</html>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pie Chart Data
    const pieData = {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            data: <?php echo json_encode($data); ?>,
            backgroundColor: ['#007bff', '#6c757d', '#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6610f2'] // Add more colors as needed
        }]
    };

    // Pie Chart Configuration
    const ctx1 = document.getElementById('myPieChart').getContext('2d');
    const myPieChart = new Chart(ctx1, {
        type: 'pie',
        data: pieData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'left', // Move legend to the left side
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            if (label) {
                                label = ': ';
                            }
                            label = context.formattedValue;
                            return " " + label + "%";
                        }
                    }
                },
                datalabels: {
                    color: '#ffffff', // Text color
                    font: {
                        weight: 'bold',
                        size: '14'
                    },
                    formatter: function(value, context) {
                        return context.chart.data.labels[context.dataIndex] + ': ' + value;
                    }
                }
            }
        }
    });

    // Adjust canvas size
    document.getElementById('myPieChart').style.width = '400px';
    document.getElementById('myPieChart').style.height = '400px';
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>














<script>
    const canvas = document.getElementById("clockCanvas");
    const ctx = canvas.getContext("2d");
    let radius = canvas.height / 2;
    ctx.translate(radius, radius);
    radius = radius * 0.90
    setInterval(drawClock, 1000);

    function drawClock() {
        drawFace(ctx, radius);
        drawNumbers(ctx, radius);
        drawTime(ctx, radius);
    }

    function drawFace(ctx, radius) {
        const grad = ctx.createRadialGradient(0, 0, radius * 0.95, 0, 0, radius * 1.05);
        grad.addColorStop(0, '#333');
        grad.addColorStop(0.5, 'white');
        grad.addColorStop(1, '#333');
        ctx.beginPath();
        ctx.arc(0, 0, radius, 0, 2 * Math.PI);
        ctx.fillStyle = 'white';
        ctx.fill();
        ctx.strokeStyle = grad;
        ctx.lineWidth = radius * 0.1;
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(0, 0, radius * 0.1, 0, 2 * Math.PI);
        ctx.fillStyle = '#333';
        ctx.fill();
    }

    function drawNumbers(ctx, radius) {
        ctx.font = radius * 0.15 + "px Arial";
        ctx.textBaseline = "middle";
        ctx.textAlign = "center";
        for (let num = 1; num < 13; num++) {
            let ang = num * Math.PI / 6;
            ctx.rotate(ang);
            ctx.translate(0, -radius * 0.85);
            ctx.rotate(-ang);
            ctx.fillText(num.toString(), 0, 0);
            ctx.rotate(ang);
            ctx.translate(0, radius * 0.85);
            ctx.rotate(-ang);
        }
    }

    function drawTime(ctx, radius) {
        const now = new Date();
        let hour = now.getHours();
        let minute = now.getMinutes();
        let second = now.getSeconds();
        //hour
        hour = hour % 12;
        hour = (hour * Math.PI / 6) +
            (minute * Math.PI / (6 * 60)) +
            (second * Math.PI / (360 * 60));
        drawHand(ctx, hour, radius * 0.5, radius * 0.07);
        //minute
        minute = (minute * Math.PI / 30) + (second * Math.PI / (30 * 60));
        drawHand(ctx, minute, radius * 0.8, radius * 0.07);
        // second
        second = (second * Math.PI / 30);
        drawHand(ctx, second, radius * 0.9, radius * 0.02);
    }

    function drawHand(ctx, pos, length, width) {
        ctx.beginPath();
        ctx.lineWidth = width;
        ctx.lineCap = "round";
        ctx.moveTo(0, 0);
        ctx.rotate(pos);
        ctx.lineTo(0, -length);
        ctx.stroke();
        ctx.rotate(-pos);
    }
</script>


<script>
    function updateClock() {
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        const now = new Date();
        const monthName = months[now.getMonth()];
        const day = now.getDate();
        const year = now.getFullYear();

        const formattedDate = `${monthName} ${day} ${year}`;

        document.getElementById('clock').innerText = formattedDate;
    }

    // Update the clock every day
    setInterval(updateClock, 1000 * 60 * 60 * 24); // Update every 24 hours

    // Initial call to display the date immediately
    updateClock();
</script>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Get radio buttons
        const testRadio = document.getElementById('testRadio');
        const quizRadio = document.getElementById('quizRadio');
        const examRadio = document.getElementById('examRadio');

        // Function to update quiz_type based on radio button selection
        function updateQuizType() {
            if (testRadio.checked) {
                // If TEST radio button is selected
                <?php $quiz_type = 1; ?>;
            } else if (quizRadio.checked) {
                // If QUIZ radio button is selected
                <?php $quiz_type = 2; ?>;
            } else if (examRadio.checked) {
                // If EXAM radio button is selected
                <?php $quiz_type = 3; ?>;
            }
        }

        // Listen for changes in radio button selection
        testRadio.addEventListener('change', updateQuizType);
        quizRadio.addEventListener('change', updateQuizType);
        examRadio.addEventListener('change', updateQuizType);
    });
</script>