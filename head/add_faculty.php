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

    $type_id = 3;
    $program_id = $_SESSION['program_id'];
    $user_fname = $_POST['user_fname'];
    $user_mname = $_POST['user_mname'];
    $user_lname = $_POST['user_lname'];
    $user_image = $_POST['user_image'];
    $user_name = $_POST['user_name'];
    $user_password = $_POST['user_password'];
    $user_status = 1;

    if (empty($program_id) || empty($type_id) || empty($user_fname) || empty($user_mname) || empty($user_lname) || empty($user_image) ||  empty($user_name) || empty($user_password)) {
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
                    window.location.href = "faculty.php";
                });
            });
        </script>';
    } else {
        $sql = "INSERT INTO `tbl_user`(`type_id`, `program_id`, `user_fname`, `user_mname`, `user_lname`, `user_image`, `user_name`, `user_password`, `user_status`) 
        VALUES (:type_id,:program_id,:user_fname,:user_mname,:user_lname,:user_image,:user_name,:user_password,:user_status)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":type_id", $type_id);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":user_fname", $user_fname);
        $stmt->bindParam(":user_mname", $user_mname);
        $stmt->bindParam(":user_lname", $user_lname);
        $stmt->bindParam(":user_image", $user_image);
        $stmt->bindParam(":user_name", $user_name);
        $stmt->bindParam(":user_password", $user_password);
        $stmt->bindParam(":user_status", $user_status);

        if ($stmt->execute()) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Faculty added successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "faculty.php";
                            });
                        });
                    </script>';
        } else {
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to add faculty.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "faculty.php";
                        });
                    });
                    </script>';
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
    <title>Add Faculty</title>
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
                <h1>Add Faculty</h1>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <form action="add_faculty.php" method="post">
                            <!-- First Name Input -->
                            <div class="mb-3">
                                <label for="user_fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="user_fname" name="user_fname" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Middle Name Input -->
                            <div class="mb-3">
                                <label for="user_mname" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="user_mname" name="user_mname" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Last Name Input -->
                            <div class="mb-3">
                                <label for="user_lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="user_lname" name="user_lname" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- User Image Input -->
                            <div class="mb-3">
                                <label for="user_image" class="form-label">Image</label>
                                <input class="form-control" type="file" id="user_image" name="user_image" required>
                            </div>

                            <!-- Username Input -->
                            <div class="mb-3">
                                <label for="user_name" class="form-label">Username</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" required>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="user_password" class="form-label">Password</label>
                                <div class="password-input-container">
                                    <input type="password" class="form-control" id="user_password" name="user_password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 12 or more characters" required>
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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
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