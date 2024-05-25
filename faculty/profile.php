<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
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
    <title>Profile</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<style>
    .card {
        border: none;
        box-shadow: 10px 20px 30px rgba(0, 0, 0, 0.1);
    }
</style>

<body>
    <div class="wrapper">

        <?php
        include 'sidebar.php';
        ?>
        <div class="main p-3">
            <div class="text-center mt-3">
                <h1>Profile</h1>
            </div>
            <section class="vh-90">
                <div class="container py-5 h-100">
                    <div class="row d-flex justify-content-center align-items-center h-100">
                        <div class="col col-lg-6 mb-4 mb-lg-0">
                            <div class="card mb-3" style="border-radius: .5rem;">
                                <div class="row g-0">
                                    <div class="col-md-4 gradient-custom text-center text-white" style="border-top-left-radius: .5rem; border-bottom-left-radius: .5rem;">
                                        <!-- <img src="data:image/jpg;base64,<?php echo base64_encode($user['user_image']); ?>" alt="Avatar" class="rounded-circle img-fluid my-5" style="width: 100px;"> -->
                                        <?php

                                        echo '<img src="/cea-reviewer/img/faculty.png" alt="Avatar" class="rounded-circle img-fluid my-5" style="width: 100px;">';
                                        ?>
                                        <h5><?php echo $user['user_fname'] . ' ' . $user['user_lname'] ?></h5>
                                        <p><?php echo $user['type_name']; ?></p>
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
                                                    <p class="text-muted"><?php echo $user['user_lname'] . ', ' . $user['user_fname'] . ' ' . $user['user_mname']; ?></p>
                                                </div>
                                            </div>
                                            <h6>Account</h6>
                                            <hr class="mt-0 mb-4">
                                            <div class="row pt-1">
                                                <div class="col-6 mb-3">
                                                    <h6>Username</h6>
                                                    <p class="text-muted"><?php echo $user['user_name']; ?></p>
                                                    <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                                                </div>
                                                <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form id="changePasswordForm">
                                                                    <div class="mb-3">
                                                                        <label for="currentPassword" class="form-label">Current Password</label>
                                                                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="newPassword" class="form-label">New Password</label>
                                                                        <div class="input-group">
                                                                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                                                                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                                                                <i class="fa fa-eye" aria-hidden="true"></i>
                                                                            </button>
                                                                        </div>
                                                                        <div id="pass1Error" class="text-danger"></div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                                                                        <div class="input-group">
                                                                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                                                <i class="fa fa-eye" aria-hidden="true"></i>
                                                                            </button>
                                                                        </div>
                                                                        <div id="pass2Error" class="text-danger"></div>
                                                                    </div>

                                                                    <button type="submit" class="btn btn-success">Change Password</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <script>
                                                    document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
                                                        event.preventDefault(); // Prevent default form submission

                                                        var isValid = validatePassword();
                                                        if (isValid) {
                                                            // Serialize form data
                                                            var formData = new FormData(this);

                                                            // Make AJAX request
                                                            var xhr = new XMLHttpRequest();
                                                            xhr.open('POST', '../changepass.php', true);
                                                            xhr.onload = function() {
                                                                if (xhr.status === 200) {
                                                                    // Password updated successfully
                                                                    alert(xhr.responseText);
                                                                    // Clear form fields
                                                                    document.getElementById('changePasswordForm').reset();
                                                                    // Optionally, you can close the modal here
                                                                    $('#changePasswordModal').modal('hide');
                                                                } else {
                                                                    // Error updating password
                                                                    alert('Error updating password.');
                                                                }
                                                            };
                                                            xhr.send(formData);
                                                        }
                                                    });

                                                    document.getElementById('toggleNewPassword').addEventListener('click', function() {
                                                        var newPasswordInput = document.getElementById('newPassword');
                                                        if (newPasswordInput.type === 'password') {
                                                            newPasswordInput.type = 'text';
                                                        } else {
                                                            newPasswordInput.type = 'password';
                                                        }
                                                    });

                                                    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
                                                        var confirmPasswordInput = document.getElementById('confirmPassword');
                                                        if (confirmPasswordInput.type === 'password') {
                                                            confirmPasswordInput.type = 'text';
                                                        } else {
                                                            confirmPasswordInput.type = 'password';
                                                        }
                                                    });

                                                    function validatePassword() {
                                                        var pass1 = document.getElementById("newPassword").value;
                                                        var pass2 = document.getElementById("confirmPassword").value;
                                                        var pass1Error = document.getElementById("pass1Error");
                                                        var pass2Error = document.getElementById("pass2Error");
                                                        var isValid = true;

                                                        // Reset error messages
                                                        pass1Error.innerHTML = "";
                                                        pass2Error.innerHTML = "";

                                                        // Password length validation
                                                        if (pass1.length < 8) {
                                                            pass1Error.innerHTML = "Password must be at least 8 characters long";
                                                            isValid = false;
                                                        }

                                                        // Password complexity validation
                                                        var uppercaseRegex = /[A-Z]/;
                                                        var lowercaseRegex = /[a-z]/;
                                                        var specialCharRegex = /[!@#$%^&*(),.?":{}|<>]/;
                                                        var numberRegex = /[0-9]/;

                                                        if (!uppercaseRegex.test(pass1) || !lowercaseRegex.test(pass1) || !specialCharRegex.test(pass1) || !numberRegex.test(pass1)) {
                                                            pass1Error.innerHTML = "Password must contain at least one uppercase letter, one lowercase letter, one special character, and one number";
                                                            isValid = false;
                                                        }

                                                        // Matching passwords validation
                                                        if (pass1 !== pass2) {
                                                            pass2Error.innerHTML = "Passwords do not match";
                                                            isValid = false;
                                                        }

                                                        return isValid;
                                                    }
                                                </script>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </section>
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