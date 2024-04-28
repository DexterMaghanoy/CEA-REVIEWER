<?php
require("../api/db-connect.php");
session_start(); // Start the session

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update'])) {
    // Sanitize input values
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
    $program_id = $_SESSION['program_id'];
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $sem_id = filter_input(INPUT_POST, 'sem_id', FILTER_SANITIZE_NUMBER_INT);
    $year_id = filter_input(INPUT_POST, 'year_id', FILTER_SANITIZE_NUMBER_INT);
    $course_code = filter_input(INPUT_POST, 'course_code', FILTER_SANITIZE_STRING);
    $course_name = filter_input(INPUT_POST, 'course_name', FILTER_SANITIZE_STRING);

    // Validate input data
    if (empty($program_id) || empty($user_id) || empty($sem_id) || empty($year_id) || empty($course_code) || empty($course_name)) {
        echo '<script>alert("Please input all fields.");</script>';
    } else {
        try {
            // Prepare and execute the SQL query using prepared statements
            $sql = "UPDATE `tbl_course` SET 
                    program_id = :program_id,
                    user_id = :user_id,
                    sem_id = :sem_id,
                    year_id = :year_id,
                    course_code = :course_code,
                    course_name = :course_name
                    WHERE course_id = :course_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_id", $program_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":sem_id", $sem_id);
            $stmt->bindParam(":year_id", $year_id);
            $stmt->bindParam(":course_code", $course_code);
            $stmt->bindParam(":course_name", $course_name);
            $stmt->bindParam(":course_id", $course_id);
            if ($stmt->execute()) {
                // Redirect after successful update
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
                                window.location.href = "course.php";
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
                        window.location.href = "course.php";
                    });
                });
                </script>';
            }
        } catch (PDOException $e) {
            echo '<script>alert("Database Error: ' . $e->getMessage() . '");</script>';
        }
    }
}

// Retrieve course data for editing
if (isset($_GET['course_id'])) {
    $course_id = filter_input(INPUT_GET, 'course_id', FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT * FROM tbl_course WHERE course_id = :course_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":course_id", $course_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $course_id = $row['course_id'];
        $program_id = $row['program_id'];
        $user_id = $row['user_id'];
        $sem_id = $row['sem_id'];
        $year_id = $row['year_id'];
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
    <title>Edit Course</title>
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
        <div class="main py-3">
            <div class="text-center mb-4">
                <h1>Edit Course</h1>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <form action="edit_course.php" method="post">

                            <!-- Faculty Select -->
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Faculty</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <?php
                                    $program_id = $_SESSION['program_id'];
                                    $sqlUser = "SELECT user_id, user_fname, user_lname, user_mname FROM tbl_user WHERE program_id = $program_id AND type_id = 3";
                                    $stmtUser = $conn->prepare($sqlUser);
                                    $stmtUser->execute();
                                    $users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($users as $user) {
                                        $selected = ($user_id == $user['user_id']) ? "selected" : "";
                                        echo "<option value='" . $user['user_id'] . "' $selected>" . $user['user_lname'] . ', ' . $user['user_fname'] . ' ' . $user['user_mname'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Sem Select -->
                            <div class="mb-3">
                                <label for="sem_id" class="form-label">Semester</label>
                                <select class="form-select" id="sem_id" name="sem_id">
                                    <?php
                                    $sqlSemester = "SELECT sem_id, sem_name FROM tbl_semester";
                                    $stmtSemester = $conn->prepare($sqlSemester);
                                    $stmtSemester->execute();
                                    $semester = $stmtSemester->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($semester as $sem) {
                                        $selected = ($sem_id == $sem['sem_id']) ? "selected" : "";
                                        echo "<option value='" . $sem['sem_id'] . "' $selected>" . $sem['sem_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Year Select -->
                            <div class="mb-3">
                                <label for="year_id" class="form-label">Year Level</label>
                                <select class="form-select" id="year_id" name="year_id">
                                    <?php
                                    $sqlYear = "SELECT year_id, year_level FROM tbl_year";
                                    $stmtYear = $conn->prepare($sqlYear);
                                    $stmtYear->execute();
                                    $years = $stmtYear->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($years as $year) {
                                        $selected = ($year_id == $year['year_id']) ? "selected" : "";
                                        echo "<option value='" . $year['year_id'] . "' $selected>" . $year['year_level'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- First Name Input -->
                            <div class="mb-3">
                                <label for="course_code" class="form-label">Course Code</label>
                                <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo $course_code; ?>" required>
                            </div>

                            <!-- Middle Name Input -->
                            <div class="mb-3">
                                <label for="course_name" class="form-label">Course Name</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo $course_name; ?>" required>
                            </div>

                            <!-- Hidden Employee ID and Submit Button -->
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
                        </form>
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