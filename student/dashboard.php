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
    $user_stmt = $conn->prepare("SELECT s.*, y.year_level, p.program_name
            FROM tbl_student s
            INNER JOIN tbl_year y ON s.year_id = y.year_id
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
                                    AND program_id = ? 
                                    AND year_id = 1");
    $course_stmt->execute([$program_id]);
    $enrolled_courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
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
        <?php include 'sidebar.php'; ?>
        <div class="main p-3">
            <div class="text-center">
                <h1>Dashboard</h1>
            </div>
            <div class="container mt-3">
                <div class="row justify-content-center"> <!-- Center the cards horizontally -->
                    <?php
                    if (!empty($enrolled_courses)) {
                        $background_classes = ['card-bg1', 'card-bg2', 'card-bg3', 'card-bg4', 'card-bg5', 'card-bg6', 'card-bg7', 'card-bg8'];
                        $index = 0;
                        foreach ($enrolled_courses as $course) {
                            $background_class = $background_classes[$index % count($background_classes)];
                            echo '<div class="col-md-4">';
                            echo '<div class="card text-dark rounded-3 shadow ' . $background_class . '">';
                            echo '<div class="card-body">';
                            echo '<a href="module.php?course_id=' . $course['course_id'] . '" class="card-link">';
                            echo '<h5><p class="card-text" style="color: white;">' . $course['course_code'] . ': ' . $course['course_name'] . '</p></h5>';
                            echo '</a>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            $index++;
                        }
                    } else {
                        echo '<p class="card-text">No enrolled courses found for the student.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");
        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>
</body>

</html>