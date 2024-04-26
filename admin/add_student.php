<?php
session_start();
require("../api/db-connect.php");

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['save'])) {

    $year_id = filter_input(INPUT_POST, 'year_id', FILTER_SANITIZE_NUMBER_INT);
    $program_id = filter_input(INPUT_POST, 'program_id', FILTER_SANITIZE_NUMBER_INT);
    $stud_no = filter_input(INPUT_POST, 'stud_no', FILTER_SANITIZE_STRING);
    $stud_fname = filter_input(INPUT_POST, 'stud_fname', FILTER_SANITIZE_STRING);
    $stud_mname = filter_input(INPUT_POST, 'stud_mname', FILTER_SANITIZE_STRING);
    $stud_lname = filter_input(INPUT_POST, 'stud_lname', FILTER_SANITIZE_STRING);
    $stud_password = filter_input(INPUT_POST, 'stud_password', FILTER_SANITIZE_STRING);
    $stud_status = 1;

    if (empty($year_id) || empty($program_id) || empty($stud_no) || empty($stud_fname) || empty($stud_mname) || empty($stud_lname) ||  empty($stud_password)) {
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
            $sql = "INSERT INTO `tbl_student`(`program_id`, `year_id`, `stud_no`, `stud_fname`, `stud_mname`, `stud_lname`, `stud_password`, `stud_status`) 
            VALUES (:program_id,:year_id,:stud_no,:stud_fname,:stud_mname,:stud_lname,:stud_password,:stud_status)";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_id", $program_id);
            $stmt->bindParam(":year_id", $year_id);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
    <div class="wrapper">
    <?php
        include 'sidebar.php';
        ?>
        <div class="main py-3">
    <div class="text-center mb-4">
        <h1>Add Student</h1>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <form action="add_student.php" method="post">
                    <!-- Program Select -->
                    <div class="mb-3">
                        <label for="program_id" class="form-label">Program</label>
                        <select class="form-select" id="program_id" name="program_id">
                        <?php
                            // Retrieve the list of program from your database and populate the options
                            $sqlProgram = "SELECT program_id, program_name FROM tbl_program WHERE program_status = 1";
                            $stmtProgram = $conn->prepare($sqlProgram);
                            $stmtProgram->execute();
                            $programs = $stmtProgram->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($programs as $program) {
                                echo "<option value='" . $program['program_id'] . "'>" . $program['program_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Year Select -->
                    <div class="mb-3">
                        <label for="year_id" class="form-label">Year</label>
                        <select class="form-select" id="year_id" name="year_id">
                        <?php
                            // Retrieve the list of program from your database and populate the options
                            $sqlYear = "SELECT year_id, year_level FROM tbl_year";
                            $stmtYear = $conn->prepare($sqlYear);
                            $stmtYear->execute();
                            $years = $stmtYear->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($years as $year) {
                                echo "<option value='" . $year['year_id'] . "'>" . $year['year_level'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                      <!-- Number Input -->
                    <div class="mb-3">
                        <label for="stud_no" class="form-label">Student No.</label>
                        <input type="text" class="form-control" id="stud_no" name="stud_no" required>
                    </div>

                    <!-- First Name Input -->
                    <div class="mb-3">
                        <label for="stud_fname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="stud_fname" name="stud_fname" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                    </div>
                    
                    <!-- Middle Name Input -->
                    <div class="mb-3">
                        <label for="stud_mname" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="stud_mname" name="stud_mname" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                    </div>
                    
                    <!-- Last Name Input -->
                    <div class="mb-3">
                        <label for="stud_lname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="stud_lname" name="stud_lname" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                    </div>
                    
                    <!-- Password Input -->
                    <div class="mb-3">
                        <label for="stud_password" class="form-label">Password</label>
                        <div class="password-input-container">
                            <input type="password" class="form-control" id="stud_password" name="stud_password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 12 or more characters" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility()"><i class="far fa-eye-slash"></i></span>
                        </div>
                    </div>
                    <!-- Hidden Employee ID and Submit Button -->
                    <input type="submit" class="btn btn-success mt-2" value="Save" name="save">
                </form>
            </div>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
</body>

<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
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