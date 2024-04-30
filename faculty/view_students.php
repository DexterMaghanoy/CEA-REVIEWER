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

// Retrieve course ID from URL parameter
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Query students enrolled in the specified course who have records
    $stmt = $conn->prepare("
        SELECT s.*, p.program_name, y.year_level
        FROM tbl_student s
        INNER JOIN tbl_program p ON s.program_id = p.program_id
        INNER JOIN tbl_year y ON s.year_id = y.year_id
        WHERE s.program_id = (SELECT program_id FROM tbl_course WHERE course_id = :course_id)
        AND EXISTS (
            SELECT 1 FROM tbl_result r WHERE r.stud_id = s.stud_id
        )
    ");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Redirect if course ID is not provided
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Students Enrolled in the Course</h1>
                        </div>
                        <?php if (!empty($students)) : ?>
                            <div class="table-responsive">
                                <table class="table table-bordered border-secondary">
                                    <caption>List of Students</caption>
                                    <thead class="table-dark">
                                        <tr>
                                            <th scope="col">Student No.</th>
                                            <th scope="col">Program</th>
                                            <th scope="col">Full Name</th>
                                            <th scope="col">Year Level</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student) : ?>
                                            <tr>
                                                <td><?php echo $student['stud_no']; ?></td>
                                                <td><?php echo $student['program_name']; ?></td>
                                                <td><?php echo $student['stud_fname'] . ' ' . $student['stud_lname']; ?></td>
                                                <td><?php echo $student['year_level']; ?></td>
                                                <td><a href="view_progress.php?student_id=<?php echo $student['stud_id']; ?>" class="btn btn-primary">View Progress</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p>No students enrolled in this course or students without records.</p>
                        <?php endif; ?>
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
