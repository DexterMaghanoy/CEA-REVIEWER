<?php
session_start();

require("../api/db-connect.php");

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['save'])) {

    $stud_no = $_POST['stud_no'];
    $stud_fname = $_POST['stud_fname'];
    $stud_mname = $_POST['stud_mname'];
    $stud_lname = $_POST['stud_lname'];
    $stud_password = $_POST['stud_lname'];
    $stud_status = 1;

    if (empty($stud_no) || empty($stud_fname) || empty($stud_mname) || empty($stud_lname) ||  empty($stud_password)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input all fields.",
                    icon: "error"
                }).then(() => {
                    window.location.href = "student.php";
                });
            });
        </script>';
    } else {
        // Check if student already exists
        $check_sql = "SELECT COUNT(*) AS count FROM `tbl_student` WHERE `stud_no` = :stud_no";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(":stud_no", $stud_no);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $student_count = $result['count'];

        if ($student_count > 0) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Student already exists.",
                            icon: "error"
                        });
                    });
                </script>';
        } else {
            // Insert new student
            $sql = "INSERT INTO `tbl_student`(`program_id`, `stud_no`, `stud_fname`, `stud_mname`, `stud_lname`, `stud_password`, `stud_status`) 
            VALUES (:program_id,:stud_no,:stud_fname,:stud_mname,:stud_lname,:stud_password,:stud_status)";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_id", $program_id);
            $stmt->bindParam(":stud_no", $stud_no);
            $stmt->bindParam(":stud_fname", $stud_fname);
            $stmt->bindParam(":stud_mname", $stud_mname);
            $stmt->bindParam(":stud_lname", $stud_lname);
            $stmt->bindParam(":stud_password", $stud_password);
            $stmt->bindParam(":stud_status", $stud_status);

            if ($stmt->execute()) {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Student added successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "student.php";
                            });
                        });
                    </script>';
            } else {
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Failed!",
                                text: "Failed to add student.",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "student.php";
                            });
                        });
                    </script>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>
        <div class="container">
            <?php
            include 'back.php';
            ?>
            <div class="text-center mb-4">
                <h1>Add Student</h1>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Add Student</title>
                    </head>

                    <body>
                        <form action="add_student.php" method="post" autocomplete="off" id="studentForm">



                            <!-- Number Input -->
                            <div class="mb-3">
                                <label for="stud_no" class="form-label">Student No.</label>
                                <input type="text" class="form-control" id="stud_no" name="stud_no" required autocomplete="off" pattern="[0-9-]*">
                                <div class="invalid-feedback">
                                    Please enter a valid student number.
                                </div>
                            </div>
                            <script>
                                // Prevent script injection in input fields
                                document.getElementById('stud_no').addEventListener('input', function() {
                                    this.value = this.value.replace(/[^0-9-]/g, '');
                                });
                            </script>

                            <!-- First Name Input -->
                            <div class="mb-3">
                                <label for="stud_fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="stud_fname" name="stud_fname" pattern="^(?!.*[<>?;$\\\/.]).*$" title="Please enter only alphabetic characters and spaces, and exclude <, >, /, ?, $, ;" required autocomplete="off">
                            </div>

                            <!-- Middle Name Input -->
                            <div class="mb-3">
                                <label for="stud_mname" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="stud_mname" name="stud_mname" pattern="^(?!.*[<>?;$\\\/.]).*$" title="Please enter only alphabetic characters and spaces, and exclude <, >, /, ?, $, ;" required autocomplete="off">
                            </div>


                            <!-- Last Name Input -->
                            <div class="mb-3">
                                <label for="stud_lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="stud_lname" name="stud_lname" pattern="^(?!.*[<>?;$\\\/.]).*$" title="Please enter only alphabetic characters and exclude numbers, <, >, /, ?, $, ;" required autocomplete="off">
                            </div>


                            <style>
                                .password-input-container {
                                    position: relative;
                                }

                                .toggle-password {
                                    position: absolute;
                                    right: 10px;
                                    /* Adjust as needed */
                                    top: 50%;
                                    transform: translateY(-50%);
                                    cursor: pointer;
                                    z-index: 1;
                                }
                            </style>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="stud_password" class="form-label">Password</label>
                                <div class="password-input-container">
                                    <input type="password" class="form-control" id="stud_password" name="stud_password" pattern="^(?!.*[<>?;$\\\/]).*$" title="Must contain at least one number and one uppercase and lowercase letter, and at least 12 or more characters. Characters <, >, ?, ;, $, \, / are not allowed." required autocomplete="new-password" disabled>
                                    <span class="toggle-password" onclick="togglePasswordVisibility()"><i class="far fa-eye-slash"></i></span>
                                </div>
                            </div>

                            <script>
                                // Function to synchronize last name with password
                                function syncLastNameWithPassword() {
                                    var lastNameInput = document.getElementById('stud_lname');
                                    var passwordInput = document.getElementById('stud_password');

                                    // Set the value of the password input field to the value of the last name input field
                                    passwordInput.value = lastNameInput.value;
                                }

                                // Add event listener to the last name input field
                                document.getElementById('stud_lname').addEventListener('input', syncLastNameWithPassword);
                            </script>



                            <!-- Hidden Employee ID and Submit Button -->
                            <input type="submit" class="btn btn-success mt-2" value="Save" name="save">
                        </form>

                        <script>
                            // Function to toggle password visibility
                            function togglePasswordVisibility() {
                                var passwordInput = document.getElementById('stud_password');
                                var icon = document.querySelector('.toggle-password i');

                                if (passwordInput.type === "password") {
                                    passwordInput.type = "text";
                                    icon.classList.remove('fa-eye-slash');
                                    icon.classList.add('fa-eye');
                                } else {
                                    passwordInput.type = "password";
                                    icon.classList.remove('fa-eye');
                                    icon.classList.add('fa-eye-slash');
                                }
                            }

                            // Clear input fields on page load
                            window.onload = function() {
                                document.getElementById('stud_no').value = '';
                                document.getElementById('stud_fname').value = '';
                                document.getElementById('stud_mname').value = '';
                                document.getElementById('stud_lname').value = '';
                                document.getElementById('stud_password').value = '';
                            };
                        </script>
                    </body>

                    </html>

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

    function togglePasswordVisibility() {
        var passwordInput = document.getElementById("stud_password");
        var icon = document.querySelector('.toggle-password i');

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            passwordInput.type = "password";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
</script>

</html>