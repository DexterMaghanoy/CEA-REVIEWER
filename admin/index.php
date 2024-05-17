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
        GROUP BY p.program_id
    ");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for pie chart
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

    // Set PDO to throw exceptions
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Define the SQL query to update stud_status
    $sql = "UPDATE tbl_student SET stud_status = 0 WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);

    // Execute the SQL statement
    $stmt->execute();

    // Output success message
    // echo "Student statuses updated successfully.";
} catch (PDOException $e) {
    // Output error message
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
            background: linear-gradient(to right, rgba(120, 210, 211, 0.5), rgba(200, 240, 241, 0.5));
        }

        .card-header {

            background: linear-gradient(to right, rgba(95, 170, 252, 0.5), rgba(175, 210, 255, 0.5));




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
        <div class="main p-3">
            <div class="text-center">
                <h1 class="mt-3 mb-3">Dashboard</h1>
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




                    <a href="modules.php" class="text-white text-decoration-none">
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
                </div>

                <div class="col-md-4">
                    <a href="report.php" class="text-light text-decoration-none">
                        <div class="card text-bg-light text-black shadow-lg mb-3">
                            <div class="card-header">Student Count</div>
                            <div class="card-body">
                                <canvas id="myPieChart" class="canvas-margin"></canvas>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </div>

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
                                    label += ': ';
                                }
                                label += context.formattedValue;
                                return label;
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
</body>

</html>













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