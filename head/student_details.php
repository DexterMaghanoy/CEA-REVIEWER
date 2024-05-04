<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['program_id']) && isset($_GET['year_id'])) {
    $program_id = $_GET['program_id'];
    $year_id = $_GET['year_id'];

    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id AND year_id = :year_id";

    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id);
    $result->bindParam(':year_id', $year_id);

    if ($result->execute()) {
        // Query executed successfully
        $courses = $result->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Query failed, display error message
        echo "Error executing query: " . $result->errorInfo()[2];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment</title>
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
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Student Assessment</h1>
                        </div>
                        <?php if (isset($courses) && !empty($courses)) : ?>
                            <?php foreach ($courses as $index => $course) : ?>
                                <a href="student_result.php?course_id=<?php echo $course['course_id']; ?>&stud_id=<?php echo $_GET['stud_id']; ?>">
                                    <div class="card subject-<?php echo ($index % 3) + 1; ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $course['course_code']; ?></h5>
                                            <p class="card-text"><?php echo $course['course_name']; ?></p>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No courses found.</p>
                        <?php endif; ?>
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