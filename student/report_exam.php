<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['program_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require '../api/db-connect.php';

// Get user ID from session
$user_id = isset($_SESSION['stud_id']) ? $_SESSION['stud_id'] : null;

// Get course and module IDs from URL parameters
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

// Pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * 10;

// Fetch modules
if ($course_id) {
    $stmt = $conn->prepare("SELECT * FROM tbl_module WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_module");
}
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construct SQL query
$sql = "SELECT r.stud_id, s.stud_fname, s.stud_mname, s.stud_lname, c.course_name, m.module_name, r.created_at, r.total_questions, r.result_score, SUM(r.result_score) AS total_score
        FROM tbl_result r
        INNER JOIN tbl_student s ON r.stud_id = s.stud_id
        INNER JOIN tbl_module m ON r.module_id = m.module_id
        INNER JOIN tbl_course c ON m.course_id = c.course_id
        WHERE r.quiz_type = 3 AND r.result_status = 1";

// Add conditions for course and module IDs
if ($course_id) {
    $sql .= " AND m.course_id = :course_id";
}
if ($module_id) {
    $sql .= " AND r.module_id = :module_id";
}

$sql .= " GROUP BY r.stud_id, c.course_id";

// Count query for pagination
$countQuery = "SELECT COUNT(*) AS count FROM (
               SELECT r.stud_id
               FROM tbl_result r
               INNER JOIN tbl_student s ON r.stud_id = s.stud_id
               INNER JOIN tbl_module m ON r.module_id = m.module_id
               INNER JOIN tbl_course c ON m.course_id = c.course_id
               WHERE r.quiz_type = 3 AND r.result_status = 1";

if ($course_id) {
    $countQuery .= " AND m.course_id = :course_id";
}
if ($module_id) {
    $countQuery .= " AND r.module_id = :module_id";
}

$countQuery .= " GROUP BY r.stud_id, c.course_id) AS sub";

$stmtCount = $conn->prepare($countQuery);
if ($course_id) {
    $stmtCount->bindValue(':course_id', $course_id, PDO::PARAM_INT);
}
if ($module_id) {
    $stmtCount->bindValue(':module_id', $module_id, PDO::PARAM_INT);
}
$stmtCount->execute();
$countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
$totalCount = $countResult['count'];
$totalPages = ceil($totalCount / 10);

$sql .= " LIMIT 10 OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($course_id) {
    $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output results or handle if no results found
if ($results) {
    // Output results
    foreach ($results as $result) {
        // Output each result
    }
} else {
    // Handle no results found
    // echo "No results found.";
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Report</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body>

    <div class="wrapper">

        <?php include 'sidebar.php'; ?>

        <div class="container mt-3 mb-3">
            <div class="row justify-content-center mt-2">
                <div class="text-center mb-2 mt-3">
                    <h1>Exam Report</h1>

                    <?php


                    ?>
                </div>

                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">

                    <style>
                        #myChartExam {
                            border: 1px solid lightblue;
                            padding: 10px;
                            box-sizing: border-box;
                            border-radius: 15px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            height: 350px;
                        }
                    </style>
                    <div id="myChartExam" class="col-sm"></div>

                </div>
                <div class="col-sm">
                    <div class="col-sm">
                        <div class="card" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
                            <div class="card-body">
                                <h5 class="card-title">EXAM</h5>
                                <?php
                                $query = "SELECT 
                             COUNT(*) AS total_attempts, 
                             SUM(result_status = 1) AS passed_attempts,
                             SUM(result_score) AS total_score,
                             SUM(total_questions) AS total_questions,
                             MAX(result_status) AS max_status
                       FROM tbl_result 
                       WHERE stud_id = :stud_id AND quiz_type = 3";
                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                // Display the attempts and result
                                if ($result && $result['total_attempts'] > 0) {
                                    echo "<p class='card-text'>Attempts: " . $result['total_attempts'] . "</p>";
                                    if ($result['max_status'] == 0) {
                                        echo "<p class='card-text'>Score: N/A</p>";
                                        echo "<p class='card-text'>Result: N/A</p>";
                                    } else {
                                        echo "<p class='card-text'>Score: " . $result['total_score'] . "/" . $result['total_questions'] . "</p>";
                                        echo "<p class='card-text'>Result: " . ($result['passed_attempts'] > 0 ? "Passed" : "Failed") . "</p>";
                                    }
                                } else {
                                    echo "<p class='card-text'>Attempts: No Record</p>";
                                    echo "<p class='card-text'>Score: No Record</p>";
                                    echo "<p class='card-text'>Result: No Record</p>";
                                }
                                ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");
        const sidebar = document.querySelector("#sidebar");
        const mainContent = document.querySelector(".main");

        hamBurger.addEventListener("click", function() {
            sidebar.classList.toggle("expand");
            mainContent.classList.toggle("expand");
        });
    </script>

    <script>
        google.charts.load('current', {
            'packages': ['corechart']
        });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            // Create a new DataTable
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Student Name');
            data.addColumn('number', 'Pass Rate');
            data.addColumn({
                type: 'string',
                role: 'annotation',
                'p': {
                    'html': true
                }
            });

            // Fetch data from the server using PHP
            <?php
            $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS total_attempts, SUM(result_status = 1) AS passed_attempts FROM tbl_result WHERE stud_id = :stud_id AND quiz_type = 3");
            $stmtAttempts->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
            $stmtAttempts->execute();
            $result = $stmtAttempts->fetch(PDO::FETCH_ASSOC);

            // Check if data is fetched successfully
            if ($result && $result['total_attempts'] > 0) {
                $passRate = $result['total_attempts'] != 0 ? number_format(100 * $result['passed_attempts'] / $result['total_attempts'], 2) : 0;
                echo "data.addRow(['" . $_SESSION['stud_fname'] . " " . $_SESSION['stud_lname'] . "', " . $passRate . ", 'Pass Rate: " . $passRate . "%']);";
            } else {
                // Handle if no data is available
                echo "data.addRow(['Student', 0, 'No exam data available.']);";
            }
            ?>

            // Set options for the chart
            const options = {
                title: 'Student Performance by Module',
                hAxis: {
                    title: 'Pass Rate',
                    minValue: 0,
                    maxValue: 100
                },
                vAxis: {
                    title: 'Student Name'
                },
                chartArea: {
                    width: '50%',
                    height: '70%'
                }
            };

            // Instantiate and draw the chart
            const chart = new google.visualization.BarChart(document.getElementById('myChartExam'));
            chart.draw(data, options);
        }
    </script>

</body>

</html>
