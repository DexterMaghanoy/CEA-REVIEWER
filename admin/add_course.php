<?php
session_start();
require("../api/db-connect.php");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['save'])) {

    $program_id = $_POST['program_id'];
    $user_id = $_POST['user_id'];
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $course_status = 1;

    if (empty($program_id) || empty($user_id) || empty($course_code) || empty($course_name)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input all the fields.",
                    icon: "error"
                });
            });
        </script>';
    } else {
        // Check if course code already exists
        $checkStmt = $conn->prepare("SELECT course_id FROM tbl_course WHERE course_code = :course_code");
        $checkStmt->bindParam(':course_code', $course_code);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Course Already Exists!",
                    icon: "error"
                });
            });
            </script>';
        } else {
            // Insert new course
            $sql = "INSERT INTO `tbl_course`(`program_id`, `user_id`, `course_code`, `course_name`, `course_status`)
             VALUES (:program_id,:user_id,:course_code,:course_name,:course_status)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_id", $program_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":course_code", $course_code);
            $stmt->bindParam(":course_name", $course_name);
            $stmt->bindParam(":course_status", $course_status);

            if ($stmt->execute()) {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Success!",
                            text: "Course added successfully.",
                            icon: "success"
                        }).then(() => {
                            window.location.href = "course.php";
                        });
                    });
                </script>';
            } else {
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to add course.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "course.php";
                        });
                    });
                    </script>';
            }
        }
    }
}



// Fetch all faculty members for the selected program
$sqlFaculty = "SELECT user_id, user_fname, user_mname, user_lname FROM tbl_user WHERE type_id = 3";
$stmtFaculty = $conn->prepare($sqlFaculty);
$stmtFaculty->execute();
$facultyMembers = $stmtFaculty->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course</title>
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
            <?php include 'back.php'; ?>
            <div class="text-center mb-4">
                <h1>Add Course</h1>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <form action="add_course.php" method="post">
                            <!-- Program Select -->
                            <div class="mb-3">
                                <label for="program_id" class="form-label">Program</label>
                                <select class="form-select" id="program_id" name="program_id" require>
                                    <option value="">-- Select Course --</option>
                                    <?php
                                    // Retrieve the list of programs from your database and populate the options
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

                            <!-- Faculty Select -->
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Faculty</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">-- Select Faculty --</option>
                                    <?php
                                    // Populate faculty members in the dropdown
                                    foreach ($facultyMembers as $faculty) {
                                        echo "<option value='" . $faculty['user_id'] . "'>" . $faculty['user_lname'] . ', ' . $faculty['user_fname'] . ' ' . $faculty['user_mname'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Course Code Input -->
                            <div class="mb-3">
                                <label for="course_code" class="form-label">Course Code</label>
                                <input type="text" class="form-control" id="course_code" name="course_code" required>
                            </div>

                            <!-- Course Name Input -->
                            <div class="mb-3">
                                <label for="course_name" class="form-label">Course Name</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" required>
                            </div>

                            <!-- Submit Button -->
                            <input type="submit" class="btn btn-success mt-2" value="Save" name="save">
                        </form>
                    </div>
                </div>
            </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
<script>
    document.getElementById('program_id').addEventListener('change', function() {
        var programId = this.value;
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    var users = JSON.parse(xhr.responseText);
                    var userSelect = document.getElementById('user_id');
                    userSelect.innerHTML = ''; // Clear previous options
                    users.forEach(function(user) {
                        var option = document.createElement('option');
                        option.value = user.user_id;
                        option.textContent = user.user_lname + ', ' + user.user_fname + ' ' + user.user_mname;
                        userSelect.appendChild(option);
                    });
                } else {
                    console.error('AJAX request failed');
                }
            }
        };
        xhr.open('GET', 'get_faculty.php?program_id=' + programId, true);
        xhr.send();
    });
</script>


<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>


</html>