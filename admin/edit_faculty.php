<?php
session_start();

require("../api/db-connect.php");

if (!isset($_SESSION['original_user_id'])) {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['original_user_id'] = $_SESSION['user_id'];
    } else {
        header("Location: ../index.php");
        exit();
    }
}
function getUserId()
{
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        // Only set session user_id if it's not already set
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $_GET['user_id'];
        }
        return $_GET['user_id'];
    } elseif (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    } else {
        return null;
    }
}

$user_id = getUserId();

if ($user_id !== null) {
    $_SESSION['user_id'] = $user_id;
} else {
    echo "User ID parameter is missing or invalid.";
    header("Location: ../index.php");
    exit();
}


// Fetch user data
$sql = "SELECT * FROM tbl_user WHERE user_id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":user_id", $_GET['user_id'], PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $type_id = $row['type_id'];
    $program_id = $row['program_id'];
    $user_fname = $row['user_fname'];
    $user_mname = $row['user_mname'];
    $user_lname = $row['user_lname'];
    $user_name = $row['user_name'];
    $user_password = $row['user_password'];
}
// Update user data
if (isset($_POST['update'])) {
    $type_id = $_POST['type_id'];
    $program_id = $_POST['program_id'];
    $user_fname = $_POST['user_fname'];
    $user_mname = $_POST['user_mname'];
    $user_lname = $_POST['user_lname'];
    $user_name = $_POST['user_name'];
    $user_password = $_POST['user_password'];

    if (empty($program_id) || empty($type_id) || empty($user_fname) || empty($user_mname) || empty($user_lname) || empty($user_name) || empty($user_password)) {
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
        $sql = "UPDATE tbl_user SET 
            type_id = :type_id,
            program_id = :program_id,
            user_fname = :user_fname,
            user_mname = :user_mname,
            user_lname = :user_lname,
            user_name = :user_name,
            user_password = :user_password
            WHERE user_id = :user_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":type_id", $type_id);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":user_fname", $user_fname);
        $stmt->bindParam(":user_mname", $user_mname);
        $stmt->bindParam(":user_lname", $user_lname);
        $stmt->bindParam(":user_name", $user_name);
        $stmt->bindParam(":user_password", $user_password);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
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
            $_SESSION['user_id'] = $_SESSION['original_user_id'];
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
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
        <?php
        include 'back.php';
        ?>
        <div class="main py-3">
            <div class="text-center mb-4">
                <h1>Edit User</h1>
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
                                        $sqlType = "SELECT type_id, type_name FROM tbl_type WHERE type_id > 0";
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
                            <!-- First Name Input -->
                            <div class="mb-3">
                                <label for="user_fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="user_fname" name="user_fname" value="<?php echo htmlspecialchars($user_fname); ?>" pattern="[A-Za-z ]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Middle Name Input -->
                            <div class="mb-3">
                                <label for="user_mname" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="user_mname" name="user_mname" value="<?php echo htmlspecialchars($user_mname); ?>" pattern="[A-Za-z ]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Last Name Input -->
                            <div class="mb-3">
                                <label for="user_lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="user_lname" name="user_lname" value="<?php echo htmlspecialchars($user_lname); ?>" pattern="[A-Za-z ]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- User Image Input -->
                            <!-- <div class="mb-3">
                                <label for="user_image" class="form-label">Image</label>
                                <input class="form-control" type="file" id="user_image" name="user_image">
                            </div> -->

                            <!-- Username Input -->
                            <div class="mb-3">
                                <label for="user_name   " class="form-label">Username</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" pattern="[^\s\/<>]*" value="<?php echo htmlspecialchars($user_name); ?>" required>
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
                                <label for="user_password" class="form-label">Password</label>
                                <div class="password-input-container">
                                    <input type="password" class="form-control" id="user_password" name="user_password" value="<?php echo htmlspecialchars($user_password); ?>" pattern="^(?!.*[<>.\\?;'\" ]).*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}$" title="Must contain at least one number, one uppercase letter, one lowercase letter, be at least 12 characters long, and not contain any of the following characters: <, >, ., \, ?, ;, ', \"" required>
 <span class=" toggle-password" onclick="togglePasswordVisibility()"><i class="far fa-eye-slash"></i></span>
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