<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'], $_SESSION['stud_id'])) {
    $program_id = $_SESSION['program_id'];
    $stud_id = $_SESSION['stud_id'];
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

        <?php include 'sidebar.php'; ?>

        <div class="container mt-3 mb-3">
            <div class="row justify-content-center mt-2">
                <div class="text-center mb-2 mt-3">
                    <h1>Quiz Report</h1>
                </div>


                <?php include 'report_dropdown.php'; ?>
                <div class="col-sm">


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

                        <div id="myChartExam" class="col-sm mb-3"></div>

                    </div>



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
                            data.addColumn({
                                type: 'string',
                                role: 'style'
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
                                $color = $passRate >= 50 ? 'green' : 'red';
                                echo "data.addRow(['" . $_SESSION['stud_fname'] . " " . $_SESSION['stud_lname'] . "', " . $passRate . ", 'Pass Rate: " . $passRate . "%', '" . $color . "']);";
                            } else {
                                // Handle if no data is available
                                echo "data.addRow(['Student', 0, 'No exam data available.', 'red']);";
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



                </div>
                <div class="col-sm">
                    <div class="card" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08); outline: 1px solid rgba(0, 0, 0, 0.2);">


                        <div style="color: black;" class="card-body">
                            <a style="color: black;" href="student_exam_result.php">

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

                                        $Currentquery = "SELECT 
                                        COUNT(*) AS total_attempts, 
                                        SUM(result_status = 1) AS passed_attempts,
                                        SUM(result_score) AS total_score,
                                        SUM(total_questions) AS total_questions,
                                        MAX(result_status) AS max_status
                                  FROM tbl_result 
                                  WHERE stud_id = :stud_id AND quiz_type = 3 and attempt_id = :attempt_id";
                                        $Currentstmt = $conn->prepare($Currentquery);
                                        $Currentstmt->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
                                        $Currentstmt->bindParam(':attempt_id', $result['total_attempts']);
                                        $Currentstmt->execute();
                                        $Currentresult = $Currentstmt->fetch(PDO::FETCH_ASSOC);


                                        echo "<p class='card-text'>Score: " . $result['total_score'] . "/" . $Currentresult['total_questions'] . "</p>";
                                        echo "<p class='card-text'>Result: N/A</p>";
                                    } else {

                                        $Currentquery = "SELECT 
                                        COUNT(*) AS total_attempts, 
                                        SUM(result_status = 1) AS passed_attempts,
                                        SUM(result_score) AS total_score,
                                        SUM(total_questions) AS total_questions,
                                        MAX(result_status) AS max_status
                                  FROM tbl_result 
                                  WHERE stud_id = :stud_id AND quiz_type = 3 and attempt_id = :attempt_id";
                                        $Currentstmt = $conn->prepare($Currentquery);
                                        $Currentstmt->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
                                        $Currentstmt->bindParam(':attempt_id', $result['total_attempts']);
                                        $Currentstmt->execute();
                                        $Currentresult = $Currentstmt->fetch(PDO::FETCH_ASSOC);


                                        // Calculate the percentage
                                        $percentage = ($Currentresult['total_score'] / $Currentresult['total_questions']) * 100;

                                        // Determine the color based on the percentage
                                        if ($percentage >= 50) {
                                            // Green color for pass
                                            $colorClass = 'text-success';
                                        } else {
                                            // Red color for fail
                                            $colorClass = 'text-danger';
                                        }

                                        // Output the score with conditional styling
                                        echo "<p class='card-text'><span style='color: black;'>Score:</span> <strong class='" . $colorClass . "'>" . $Currentresult['total_score'] . "/" . $Currentresult['total_questions'] . "</strong></p>";

                                        echo "<p class='card-text'>Result: <span style='color: " . ($Currentresult['passed_attempts'] > 0 ? "green" : "red") . ";'><strong>" . ($Currentresult['passed_attempts'] > 0 ? "Passed" : "Failed") . "</strong></span></p>";
                                    }
                                } else {
                                    echo "<p class='card-text'>Attempts: No Record</p>";
                                    echo "<p class='card-text'>Score: No Record</p>";
                                    echo "<p class='card-text'>Result: No Record</p>";
                                }
                                ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm">

                    </div>
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