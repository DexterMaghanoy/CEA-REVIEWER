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
    $user_stmt = $conn->prepare("SELECT s.*, p.program_name
            FROM tbl_student s
            INNER JOIN tbl_program p ON s.program_id = p.program_id
            WHERE s.stud_id = :stud_id");
    $user_stmt->bindParam(':stud_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    if ($user_stmt->rowCount() > 0) {
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    }
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
    header("Location: ../index.php");
    exit();
}

try {
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
            <div class="row">
                <div class="col-lg-1"></div>

                <div class="col-lg">
                    <div class="text-center mt-3 mb-3">

                        <h1>Dashboard</h1>


                    </div>
                    <div class="row justify-content-center">
                        <?php
                        if (!empty($enrolled_courses)) {
                            $background_classes = ['card-bg1', 'card-bg2', 'card-bg3', 'card-bg4', 'card-bg5', 'card-bg6', 'card-bg7', 'card-bg8'];
                            $index = 0;
                            foreach ($enrolled_courses as $course) {
                                $background_class = $background_classes[$index % count($background_classes)];
                        ?>
                                <div class="col-md-4">
                                    <a href="module.php?course_id=<?php echo $course['course_id']; ?>" class="card-link text-decoration-none">
                                        <div class="card bg-light text-dark rounded-3 shadow <?php echo $background_class; ?>" style="height: 220px; width: 330px;">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <h5 class="card-title text-white"><?php echo $course['course_code'] . ': ' . $course['course_name']; ?></h5>
                                                <div>
                                                    <p class="card-text" style="font-size: 0.9rem;">


                                                        <?php

                                                        $stmtTotalModules = $conn->prepare("SELECT COUNT(module_id) AS total_modules FROM tbl_module WHERE course_id = :course_id");
                                                        $stmtTotalModules->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                                                        $stmtTotalModules->execute();
                                                        $totalModuleData = $stmtTotalModules->fetch(PDO::FETCH_ASSOC);
                                                        $totalModules = $totalModuleData['total_modules'];


                                                        $stmtPassedModules = $conn->prepare("SELECT COUNT(module_id) AS passed_modules FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND result_status = 1 AND quiz_type = 1");
                                                        $stmtPassedModules->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                                                        $stmtPassedModules->bindValue(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT); // Changed from ':course_id' to ':stud_id'
                                                        $stmtPassedModules->execute();
                                                        $totalPassedModules = $stmtPassedModules->fetch(PDO::FETCH_ASSOC);
                                                        $PassedModules = $totalPassedModules['passed_modules'];



                                                        $stmtPassedQuiz = $conn->prepare("SELECT COUNT(module_id) AS passed_quiz FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND result_status = 1 AND quiz_type = 2");
                                                        $stmtPassedQuiz->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                                                        $stmtPassedQuiz->bindValue(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
                                                        $stmtPassedQuiz->execute();
                                                        $totalPassedQuiz = $stmtPassedQuiz->fetch(PDO::FETCH_ASSOC);
                                                        $PassedQuiz = $totalPassedQuiz['passed_quiz'];


                                                        if ($PassedQuiz == 0 || $PassedQuiz == null) {
                                                            $displayQuizStatus = '
                                                            <img src="./icons/warning-mark.gif" alt="Warning" width="25" height="25">

                                                            N/A';
                                                        } elseif ($PassedQuiz == 1) {
                                                            $displayQuizStatus = '<img src="./icons/check-mark.gif" alt="Warning" width="20" height="20"> Done ';
                                                        } else {
                                                            $displayQuizStatus = $PassedQuiz; // Fallback in case there are other values
                                                        }


                                                        ?>
                                                        <strong>Completed Test:</strong> <?php echo $PassedModules ?> / <?php echo $totalModules ?> <br>
                                                        <strong>Quiz Status:</strong> <?php echo  $displayQuizStatus ?> <br>

                                                    </p>
                                                </div>
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