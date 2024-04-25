<?php
session_start();
require("../api/db-connect.php");

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update'])) {
    $user_id = $_POST['user_id'];
    $type_id = $_POST['type_id'];
    $program_id = $_POST['program_id'];
    $user_fname = $_POST['user_fname'];
    $user_mname = $_POST['user_mname'];
    $user_lname = $_POST['user_lname'];
    $user_image = $_POST['user_image'];
    $user_name = $_POST['user_name'];
    $user_password = $_POST['user_password'];

    if (empty($program_id) ||empty($type_id) || empty($user_fname) || empty($user_mname) || empty($user_lname) ||  empty($user_name) || empty($user_password)) {
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
                    window.location.href = "user.php";
                });
            });
        </script>';
    } else {
        $sql = "UPDATE `tbl_user` SET 
        type_id = :type_id,
        program_id = :program_id,
        user_fname = :user_fname,
        user_mname = :user_mname,
        user_lname = :user_lname,
        user_image = :user_image,
        user_name = :user_name,
        user_password = :user_password
        WHERE user_id = :user_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":type_id", $type_id);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":user_fname", $user_fname);
        $stmt->bindParam(":user_mname", $user_mname);
        $stmt->bindParam(":user_lname", $user_lname);
        $stmt->bindParam(":user_image", $user_image);
        $stmt->bindParam(":user_name", $user_name);
        $stmt->bindParam(":user_password", $user_password);
        $stmt->bindParam(":user_id", $user_id);

      if ($stmt->execute()) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                    echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                    echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "User updated successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "user.php";
                            });
                        });
                    </script>';
        } else {
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to update user.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "user.php";
                        });
                    });
                    </script>';
        }
    }
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $sql = "SELECT * FROM tbl_user WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $user_id = $row['user_id'];
        $type_id = $row['type_id'];
        $program_id = $row['program_id'];
        $user_fname = $row['user_fname'];
        $user_mname = $row['user_mname'];
        $user_lname = $row['user_lname'];
        $user_image = $row['user_image'];
        $user_name = $row['user_name'];
        $user_password = $row['user_password'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
        <h1>Edit Faculty</h1>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <form action="edit_faculty.php" method="post">
                <div class="row">
                        <!-- Program Select -->
                        <div class="col-md-6 mb-3">
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
                        <!-- User Select -->
                        <div class="col-md-6 mb-3">
                            <label for="type_id" class="form-label">User Role</label>
                            <select class="form-select" id="type_id" name="type_id">
                                <?php
                                $sqlType = "SELECT type_id, type_name FROM tbl_type WHERE type_id > 1";
                                $stmtType = $conn->prepare($sqlType);
                                $stmtType->execute();
                                $types = $stmtType->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($types as $type) {
                                    $selected = ($type_id == $type['type_id']) ? "selected" : "";
                                    echo "<option value='" . $type['type_id'] . "' $selected>" . $type['type_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <!-- First Name Input -->
                    <div class="mb-3">
                        <label for="user_fname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="user_fname" name="user_fname" value="<?php echo $user_fname; ?>" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                    </div>
                    
                    <!-- Middle Name Input -->
                    <div class="mb-3">
                        <label for="user_mname" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="user_mname" name="user_mname" value="<?php echo $user_mname; ?>" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                    </div>
                    
                    <!-- Last Name Input -->
                    <div class="mb-3">
                        <label for="user_lname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="user_lname" name="user_lname" value="<?php echo $user_lname; ?>" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                    </div>
                    
                    <!-- User Image Input -->
                    <div class="mb-3">
                        <label for="user_image" class="form-label">Image</label>
                        <input class="form-control" type="file" id="user_image" name="user_image">
                    </div>
                    
                    <!-- Username Input -->
                    <div class="mb-3">
                        <label for="user_name" class="form-label">Username</label>
                        <input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo $user_name; ?>" required>
                    </div>
                    
                    <!-- Password Input -->
                    <div class="mb-3">
                        <label for="user_password" class="form-label">Password</label>
                        <div class="password-input-container">
                            <input type="password" class="form-control" id="user_password" name="user_password" value="<?php echo $user_password; ?>" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 12 or more characters" required>
                            <span class="toggle-password" onclick="togglePasswordVisibility()"><i class="far fa-eye-slash"></i></span>
                        </div>
                    </div>
                    <!-- Hidden Employee ID and Submit Button -->
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
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
    .password-input-container {
    position: relative;
}

.toggle-password {
    position: absolute;
    bottom: 0;
    right: 10px;
    cursor: pointer;
    margin-bottom: 6px;
}   
</style>
<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
  document.querySelector("#sidebar").classList.toggle("expand");
});

function togglePasswordVisibility() {
    var passwordInput = document.getElementById("user_password");
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