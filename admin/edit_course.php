<?php
session_start();
require("../api/db-connect.php");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['update'])) {
    $course_id = $_POST['course_id'];
    $program_id = $_POST['program_id'];
    $user_id = $_POST['user_id'];
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];

    if (empty($program_id) || empty($user_id) || empty($course_code) || empty($course_name)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input all fields.",
                    icon: "error"
                });
            });
        </script>';
    } else {
        $sql = "UPDATE `tbl_course` SET 
                program_id = :program_id,
                user_id = :user_id,
                course_code = :course_code,
                course_name = :course_name
                WHERE course_id = :course_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":course_code", $course_code);
        $stmt->bindParam(":course_name", $course_name);
        $stmt->bindParam(":course_id", $course_id);

        if ($stmt->execute()) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Course updated successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "subjects.php";
                            });
                        });
                    </script>';
        } else {
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to update course.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "subjects.php";
                        });
                    });
                    </script>';
        }
    }
}

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
    $sql = "SELECT * FROM tbl_course WHERE course_id = :course_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":course_id", $course_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $course_id = $row['course_id'];
        $program_id = $row['program_id'];
        $user_id = $row['user_id'];
        $course_code = $row['course_code'];
        $course_name = $row['course_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
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
        ?> <?php
            include 'back.php';
            ?>
        <div class="main py-3">
            <div class="text-center mb-4">
                <h1>Edit Subject</h1>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <form action="edit_course.php" method="post">
                            <!-- Program Select -->
                            <div class="mb-3">
                                <label for="program_id" class="form-label">Program</label>
                                <select class="form-select" id="program_id" name="program_id">
                                    <?php
                                    $sqlProgram = "SELECT program_id, program_name FROM tbl_program WHERE program_status = 1";
                                    $stmtProgram = $conn->prepare($sqlProgram);
                                    $stmtProgram->execute();
                                    $programs = $stmtProgram->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($programs as $program) {
                                        $selected = ($program_id == $program['program_id']) ? "selected" : "";
                                        echo "<option value='" . $program['program_id'] . "' $selected>" . $program['program_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Faculty Select -->
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Faculty</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <?php
                                    $sqlUser = "SELECT u.user_id, u.user_fname, u.user_lname, u.user_mname 
                            FROM tbl_user u 
                            JOIN tbl_course c ON u.program_id = c.program_id 
                            WHERE u.user_status = 1 AND u.type_id != 1 AND c.course_id = :course_id";
                                    $stmtUser = $conn->prepare($sqlUser);
                                    $stmtUser->bindParam(":course_id", $course_id);
                                    $stmtUser->execute();
                                    $users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($users as $user) {
                                        $selected = ($user_id == $user['user_id']) ? "selected" : "";
                                        echo "<option value='" . $user['user_id'] . "' $selected>" . $user['user_lname'] . ', ' . $user['user_fname'] . ' ' . $user['user_mname'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Course Code Input -->
                            <div class="mb-3">
                                <label for="course_code" class="form-label">Course Code</label>
                                <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo $course_code; ?>" required>
                            </div>

                            <!-- Course Name Input -->
                            <div class="mb-3">
                                <label for="course_name" class="form-label">Course Name</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo $course_name; ?>" required>
                            </div>

                            <!-- Hidden Course ID and Submit Button -->
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>

</html>


<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });

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
<!-- 
<script>
    window.onload = function() {
        history.replaceState({}, document.title, window.location.pathname);
    };
</script> -->