<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['stud_id'];

try {
    // Prepare the query to fetch user details
    $user_stmt = $conn->prepare("SELECT s.*, p.program_name
            FROM tbl_student s
            INNER JOIN tbl_program p ON s.program_id = p.program_id
            WHERE s.stud_id = :stud_id");
    $user_stmt->bindParam(':stud_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();

    
    // Fetch user details
    if ($user_stmt->rowCount() > 0) {
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Prepare the query to fetch enrolled courses
    $course_stmt = $conn->prepare("SELECT course_code, course_name, course_id 
                                FROM tbl_course 
                                WHERE course_status = 1 
                                AND program_id = ?");
    $course_stmt->execute([$program_id]);
    $enrolled_courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

    $course_stmt->execute([$program_id]);
    $enrolled_courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}

if (!isset($_SESSION['program_id'])) {
    // Redirect to login page if session data is not set
    header("Location: ../index.php");
    exit();
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
</head>
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

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="container">
            <center>
                <div class="row">
                    <div class="col-lg-1"></div>

                    <div class="col-lg">
                        <div class="text-center mt-3 mb-3">

                            <h1>Dashboard</h1>
                        </div>
                        <div class="row justify-content-center"> <!-- Center the cards horizontally -->
                            <?php
                            if (!empty($enrolled_courses)) {
                                $background_classes = ['card-bg1', 'card-bg2', 'card-bg3', 'card-bg4', 'card-bg5', 'card-bg6', 'card-bg7', 'card-bg8'];
                                $index = 0;
                                foreach ($enrolled_courses as $course) {
                                    $background_class = $background_classes[$index % count($background_classes)];
                            ?>
                                    <div class="col-md-4">
                                        <a href="module.php?course_id=<?php echo $course['course_id']; ?>" class="card-link text-decoration-none">
                                            <div style="background-color: rgb(232, 328, 237);"  class="card text-dark rounded-3 shadow <?php echo $background_class; ?>">
                                                <div class="card-body">
                                                    <h5>
                                                        <p class="card-text" style="color: white;"><?php echo $course['course_code'] . ': ' . $course['course_name']; ?></p>
                                                    </h5>
                                                    <br>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                            <?php
                                    $index++;
                                }
                            } else {
                                echo '<div class="col text-center"><p class="card-text">No enrolled courses found for the student.</p></div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="col-lg-1"></div>

                </div>
            </center>


        </div>
    </div>

</body>



</html>





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