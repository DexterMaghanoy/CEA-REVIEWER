<?php
session_start();

require '../api/db-connect.php';


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
    header("Location: ../index.php");
    exit();
}


// Use JOIN to get user_type and course_name from related tables
$user_id = $_SESSION['stud_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $studentId = $_SESSION['stud_id'];

    // Perform validation here

    // Check if the new password matches the confirm password
}

// Fetch user data after password update
$sql = "SELECT s.*, p.program_name
            FROM tbl_student s
            INNER JOIN tbl_program p ON s.program_id = p.program_id
            WHERE s.stud_id = :stud_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':stud_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if the query was successful and if there is a user with the given stud_id
if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data
} else {
    // Redirect to login page if user data is not found
    header("Location: ../login.php");
    exit();
}




$sql = "SELECT stud_password FROM tbl_student WHERE stud_id = :stud_id";
$passStmt = $conn->prepare($sql);
$passStmt->bindParam(':stud_id', $user_id, PDO::PARAM_INT);
$passStmt->execute();

// Fetch the result
$userPass = $passStmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $userPassword = $userPass['stud_password'];
} else {
    echo "User not found";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">

    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="mobile-desktop.css" type="text/css">
    <link rel="stylesheet" href="./css/profile.css" type="text/css">

</head>

<style>

  
</style>



<body>
    <div id="topBar">

        <?php
        include 'topNavBar.php';
        ?>

    </div>

    <div style="background-color: rgb(252, 249, 249);" class="wrapper">

        <?php include 'sidebar.php'; ?>

        <div class="container">
            <br>
            <div id="profile-title" class="text-center">
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
                                <p>Student</p>
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
                                            <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                                        </div>

                                        <!-- Modal for changing password -->
                                        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-info text-white">
                                                        <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form id="changePasswordForm" novalidate>
                                                            <div class="mb-3">
                                                                <label for="currentPassword" class="form-label">Current Password</label>
                                                                <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                                                <div class="invalid-feedback">Current password is required.</div>
                                                                <div id="currentError" class="text-danger mt-1"></div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="newPassword" class="form-label">New Password</label>
                                                                <div class="input-group">
                                                                    <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                                                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                                                    </button>
                                                                </div>
                                                                <div id="pass1Error" class="text-danger mt-1"></div>
                                                                <div class="form-text">Your password must be at least 8 characters long, contain upper and lower case letters, and include a number or special character.</div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                                                <div class="input-group">
                                                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                                                    </button>
                                                                </div>
                                                                <div id="pass2Error" class="text-danger mt-1"></div>
                                                            </div>
                                                            <button type="submit" class="btn btn-success w-100">Change Password</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>

                                        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">
                                        <style>
                                            .my-custom-popup {
                                                position: center;
                                                top: -10%;
                                                left: 2.5%;
                                            }
                                        </style>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const hamBurger = document.querySelector(".toggle-btn");
            hamBurger.addEventListener("click", function() {
                document.querySelector("#sidebar").classList.toggle("expand");
            });
        </script>
</body>

</html>