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

    $program_id = $_POST['program_id'];
    $user_id = $_POST['user_id'];
    $sem_id = $_POST['sem_id'];
    $year_id = $_POST['year_id'];
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $course_status = 1;

    if (empty($program_id) || empty($user_id) || empty($sem_id)|| empty($year_id) || empty($course_code) || empty($course_name)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input the fields.",
                    icon: "error"
                });
            });
        </script>';
    } else {
        // Check if program name already exists
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
                    text: "Course Already Exist!",
                    icon: "error"
                });
            });
            </script>';
        } else {
            // Insert new program
            $sql = "INSERT INTO `tbl_course`(`program_id`, `user_id`, `sem_id`, `year_id`, `course_code`, `course_name`, `course_status`)
             VALUES (:program_id,:user_id,:sem_id,:year_id,:course_code,:course_name,:course_status)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_id", $program_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":sem_id", $sem_id);
            $stmt->bindParam(":year_id", $year_id);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
</head>
<body>
    <div class="wrapper">
        <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <i class="lni lni-grid-alt"></i>
                </button>
                <div class="sidebar-logo">
                    <a href="index.php">Dashboard</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="profile.php" class="sidebar-link">
                        <i class="lni lni-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="user.php" class="sidebar-link">
                        <i class="lni lni-agenda"></i>
                        <span>User</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="student.php" class="sidebar-link">
                        <i class="lni lni-graduation"></i>
                        <span>Student</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse"
                        data-bs-target="#auth" aria-expanded="false" aria-controls="auth">
                        <i class="lni lni-library"></i>
                        <span>Category</span>
                    </a>
                    <ul id="auth" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                        <li class="sidebar-item">
                            <a href="program.php" class="sidebar-link">Program</a>
                        </li>
                        <li class="sidebar-item">
                            <a href="course.php" class="sidebar-link">Course</a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a href="report.php" class="sidebar-link">
                        <i class="lni lni-popup"></i>
                        <span>Report</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="../logout.php" class="sidebar-link">
                    <i class="lni lni-exit"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <div class="main py-3">
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
                            <!-- Options will be populated dynamically using AJAX -->
                        </select>
                    </div>

                     <!-- Sem Select -->
                     <div class="mb-3">
                        <label for="sem_id" class="form-label">Semester</label>
                        <select class="form-select" id="sem_id" name="sem_id">
                        <?php
                            // Retrieve the list of semester from your database and populate the options
                            $sqlSem = "SELECT sem_id, sem_name FROM tbl_semester";
                            $stmtSem = $conn->prepare($sqlSem);
                            $stmtSem->execute();
                            $sems = $stmtSem->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($sems as $sem) {
                                echo "<option value='" . $sem['sem_id'] . "'>" . $sem['sem_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                     <!-- Year Select -->
                     <div class="mb-3">
                        <label for="year_id" class="form-label">Year Level</label>
                        <select class="form-select" id="year_id" name="year_id" require>
                            <?php
                                // Retrieve the list of programs from your database and populate the options
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

                    <!-- First Name Input -->
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" required>
                    </div>
                    
                    <!-- Middle Name Input -->
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" required>
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
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

::after,
::before {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

a {
    text-decoration: none;
}

li {
    list-style: none;
}

h1 {
    font-weight: 600;
    font-size: 1.5rem;
}

body {
    font-family: 'Poppins', sans-serif;
}

.wrapper {
    display: flex;
}

.main {
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
    transition: all 0.35s ease-in-out;
    background-color: #fafbfe;
}

#sidebar {
    width: 70px;
    min-width: 70px;
    z-index: 1000;
    transition: all .25s ease-in-out;
    background-color: #0e2238;
    display: flex;
    flex-direction: column;
}

#sidebar.expand {
    width: 260px;
    min-width: 260px;
}

.toggle-btn {
    background-color: transparent;
    cursor: pointer;
    border: 0;
    padding: 1rem 1.5rem;
}

.toggle-btn i {
    font-size: 1.5rem;
    color: #FFF;
}

.sidebar-logo {
    margin: auto 0;
}

.sidebar-logo a {
    color: #FFF;
    font-size: 1.15rem;
    font-weight: 600;
}

#sidebar:not(.expand) .sidebar-logo,
#sidebar:not(.expand) a.sidebar-link span {
    display: none;
}

.sidebar-nav {
    padding: 2rem 0;
    flex: 1 1 auto;
}

a.sidebar-link {
    padding: .625rem 1.625rem;
    color: #FFF;
    display: block;
    font-size: 0.9rem;
    white-space: nowrap;
    border-left: 3px solid transparent;
}

.sidebar-link i {
    font-size: 1.1rem;
    margin-right: .75rem;
}

a.sidebar-link:hover {
    background-color: rgba(255, 255, 255, .075);
    border-left: 3px solid #3b7ddd;
}

.sidebar-item {
    position: relative;
}

#sidebar:not(.expand) .sidebar-item .sidebar-dropdown {
    position: absolute;
    top: 0;
    left: 70px;
    background-color: #0e2238;
    padding: 0;
    min-width: 15rem;
    display: none;
}

#sidebar:not(.expand) .sidebar-item:hover .has-dropdown+.sidebar-dropdown {
    display: block;
    max-height: 15em;
    width: 100%;
    opacity: 1;
}

#sidebar.expand .sidebar-link[data-bs-toggle="collapse"]::after {
    border: solid;
    border-width: 0 .075rem .075rem 0;
    content: "";
    display: inline-block;
    padding: 2px;
    position: absolute;
    right: 1.5rem;
    top: 1.4rem;
    transform: rotate(-135deg);
    transition: all .2s ease-out;
}

#sidebar.expand .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
    transform: rotate(45deg);
    transition: all .2s ease-out;
}
.form-container {
      max-width: 400px;
      margin: 0 auto;
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0px 0px 15px 0px rgba(0, 0, 0, 0.1);
    }

    .form-container label {
      font-weight: bold;
    }

    .form-control {
      border-radius: 5px;
    }

    .btn-custom {
      background-color: #007bff;
      border-color: #007bff;
      color: #fff;
      border-radius: 5px;
    }

    .btn-custom:hover {
      background-color: #0056b3;
      border-color: #0056b3;
    }  
</style>
<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
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
</html>