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

// Use JOIN to get user_type and course_name from related tables
$user_id = $_SESSION['stud_id'];

// Use JOIN to get user_type and course_name from related tables
$sql = "SELECT s.*, p.program_name
            FROM tbl_student s
            INNER JOIN tbl_program p ON s.program_id = p.program_id
            WHERE s.stud_id = :stud_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':stud_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if the query was successful and if there is a user with the given emp_id
if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data
}
if (isset($_SESSION['program_id'])) {

    $program_id = $_SESSION['program_id'];

    // Prepare SQL query to fetch courses for the given program and year
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect to login page if session data is not set
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Answers</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="profile.css" type="text/css">
</head>

<body>
    <div class="wrapper">

        <?php
        include 'sidebar.php';
        ?>
        <div class="container">
            <br>
            <div class="main p-3">
                <div class="text-center">
                    <h1>Profile</h1>
                </div>
                <br>
                <div class="row d-flex justify-content-center align-items-center">
                    <div class="col col-lg-6 mb-4 mb-lg-0">
                        <div class="card mb-3" style="border-radius: .5rem;">
                            <div class="row g-0">
                                <div class="col-md-4 gradient-custom text-center text-white" style="border-top-left-radius: .5rem; border-bottom-left-radius: .5rem;">
                                    <img src="../img/student.png" alt="Avatar" class="rounded-circle img-fluid my-5" style="width: 100px;">
                                    <h5><?php echo $user['stud_fname'] . ' ' . $user['stud_lname'] ?></h5>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body p-4">
                                        <h6>Information</h6>
                                        <hr class="mt-0 mb-4">
                                        <div class="row pt-1">
                                            <div class="col-6 mb-3">
                                                <h6>Program</h6>
                                                <p class="text-muted"><?php echo $user['program_name']; ?></p>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <h6>Fullname</h6>
                                                <p class="text-muted"><?php echo $user['stud_lname'] . ', ' . $user['stud_fname'] . ' ' . $user['stud_mname']; ?></p>
                                            </div>
                                        </div>
                                        <h6>Account</h6>
                                        <hr class="mt-0 mb-4">
                                        <div class="row pt-1">
                                            <div class="col-6 mb-3">
                                                <h6>Username</h6>
                                                <p class="text-muted"><?php echo $user['stud_no']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>

</html>