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
            WHERE s.stud_id = :stud_id and s.stud_status = 1 and p.program_status =1");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <link rel="stylesheet" href="mobile-desktop.css" type="text/css">
    <link rel="stylesheet" href="./css/dashboard.css" type="text/css">
    <script defer src="./scripts/dashboard.js"></script>



</head>



<body>

    <div class="mt-5" id="topBar">
        <?php include 'topNavBar.php'; ?>
    </div>

    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="container">
            <div class="row">
                <div class="col-lg-1"></div>
                <div class="col-lg-10">
                    <h1 class="mt-4 mb-4" id="dashboard-title-name">Student Dashboard</h1>
                    <div id="dashboard-cards" class="row justify-content-center">
                        <?php
                        if (!empty($enrolled_courses)) {
                            $background_classes = ['card-bg1', 'card-bg2', 'card-bg3', 'card-bg4', 'card-bg5', 'card-bg6', 'card-bg7', 'card-bg8'];
                            $index = 0;
                            foreach ($enrolled_courses as $course) {
                                $background_class = $background_classes[$index % count($background_classes)];
                        ?>
                                <div class="col-md-4">
                                    <a href="module.php?course_id=<?php echo $course['course_id']; ?>" class="card-link text-decoration-none">
                                        <div class="card bg-light text-dark rounded-5 shadow mb-4 <?php echo $background_class; ?>" id="card-size">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <h5 class="card-title text-white"><?php echo $course['course_code'] . ': ' . $course['course_name']; ?></h5>
                                                <div>
                                                    <p class="card-text" id="card-text-inside-dashboard">
                                                        <?php
                                                        $stmtTotalModules = $conn->prepare("SELECT COUNT(module_id) AS total_modules FROM tbl_module WHERE course_id = :course_id and module_status = 1");
                                                        $stmtTotalModules->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                                                        $stmtTotalModules->execute();
                                                        $totalModuleData = $stmtTotalModules->fetch(PDO::FETCH_ASSOC);
                                                        $totalModules = $totalModuleData['total_modules'];

                                                        $stmtTotalModulesWithQuestions = $conn->prepare("
                                                        SELECT COUNT(*) AS total_modules_q 
                                                        FROM (
                                                            SELECT module_id 
                                                            FROM tbl_question 
                                                            WHERE module_id IN (SELECT module_id FROM tbl_module WHERE course_id = :course_id AND module_status = 1)
                                                            GROUP BY module_id
                                                        ) AS subquery
                                                    ");
                                                        $stmtTotalModulesWithQuestions->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                                                        $stmtTotalModulesWithQuestions->execute();
                                                        $stmtTotalModulesWithQ = $stmtTotalModulesWithQuestions->fetch(PDO::FETCH_ASSOC);
                                                        $totalModulesQ = $stmtTotalModulesWithQ['total_modules_q'];

                                                        $stmtPassedModules = $conn->prepare("SELECT COUNT(module_id) AS passed_modules FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND result_status = 1 AND quiz_type = 1");
                                                        $stmtPassedModules->bindValue(':course_id', $course['course_id'], PDO::PARAM_INT);
                                                        $stmtPassedModules->bindValue(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
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
                                                            $displayQuizStatus = '<img src="./icons/warning-mark.gif" alt="Warning" width="20" height="20">';
                                                        } elseif ($PassedQuiz == 1) {
                                                            $displayQuizStatus = '<img src="./icons/check-mark.gif" alt="Warning" width="20" height="20">';
                                                        } else {
                                                            $displayQuizStatus = $PassedQuiz;
                                                        }

                                                        if ($PassedModules == $totalModulesQ) {
                                                            $displayQuestionStatus = '<img src="./icons/check-mark.gif" alt="Warning" width="20" height="20">';
                                                        } else {
                                                            $displayQuestionStatus = '<img src="./icons/warning-mark.gif" alt="Warning" width="20" height="20">';
                                                        }
                                                        ?>
                                                        <strong>Completed Test:</strong> <?php echo $PassedModules ?>/<?php echo $totalModulesQ ?> <?php echo $displayQuestionStatus ?><br>
                                                        <strong>Quiz Status:</strong> <?php echo $displayQuizStatus ?> <br>
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