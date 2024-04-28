<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Use JOIN to get user_type and course_name from related tables
$sql = "SELECT u.*, t.type_name, p.program_name
            FROM tbl_user u
            INNER JOIN tbl_type t ON u.type_id = t.type_id
            INNER JOIN tbl_program p ON u.program_id = p.program_id
            WHERE u.user_id = :user_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if the query was successful and if there is a user with the given emp_id
if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data
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
</head>

<body>
    <div class="wrapper">


        <?php
        include 'sidebar.php';
        ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Dashboard

                </h1>
            </div>
            <div class="container mt-5">
                <div class="row">
                    <!-- Card 1: Total Faculty -->
                    <div class="col-md-4">
                        <div class="card bg-primary text-white rounded-3 shadow">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Total Faculty</h5>
                                <?php
                                try {
                                    require("../api/db-connect.php"); // Include your database connection file here

                                    if (isset($_SESSION['program_id'])) {
                                        $program_id = $_SESSION['program_id'];

                                        // Prepare and execute the SQL query using prepared statements
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_user WHERE user_status = 1 AND type_id = 3 AND program_id = :program_id");
                                        $stmt->bindParam(':program_id', $program_id);
                                        $stmt->execute();

                                        // Fetch the count
                                        $faculty = $stmt->fetchColumn();

                                        if ($faculty !== false) {
                                            echo '<p class="card-text">Number of faculty members: <strong>' . $faculty . '</strong></p>';
                                        } else {
                                            echo '<p class="card-text">An error occurred while fetching the count.</p>';
                                        }
                                    } else {
                                        echo '<p class="card-text">Program ID is not set.</p>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <!-- Card 2: Total Students -->
                    <div class="col-md-4">
                        <div class="card bg-secondary text-white rounded-3 shadow">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Total Students</h5>
                                <?php
                                try {
                                    require("../api/db-connect.php"); // Include your database connection file here

                                    // Prepare and execute the SQL query
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE stud_status = 1 AND program_id = $program_id");
                                    $stmt->execute();

                                    // Fetch the count
                                    $enrolled = $stmt->fetchColumn();

                                    if ($enrolled !== false) {
                                        echo '<p class="card-text">Number of students: <strong>' . $enrolled . '</strong></p>';
                                    } else {
                                        echo '<p class="card-text">An error occurred while fetching the count.</p>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <!-- Card 3: Calendar -->
                    <div class="col-md-4">
                        <div class="card bg-info text-white rounded-3 shadow"">
            <div class=" card-header">Date</div>
                        <div class="card-body">
                            <!-- You can place your calendar content here -->
                            <div id="calendar">
                                <?php
                                // Get the current day, month, and year using PHP's date() function
                                $currentDay = date('d');       // Day (01 - 31)
                                $currentMonth = date('F');     // Month (January - December)
                                $currentYear = date('Y');      // Year (e.g., 2023)

                                // Display the day, month, and year
                                echo "<h5>$currentMonth $currentDay , $currentYear</h5>";
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>

</html>