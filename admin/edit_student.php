<?php
session_start();

require("../api/db-connect.php");

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

$programs = [];
try {
    $sql = "SELECT program_id, program_name FROM tbl_program";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle the exception if needed
}

if (isset($_GET['stud_id'])) {
    $stud_id = $_GET['stud_id'];
    $sql = "SELECT * FROM tbl_student WHERE stud_id = :stud_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":stud_id", $stud_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $stud_id = $row['stud_id'];
        $stud_no = $row['stud_no'];
        $stud_fname = $row['stud_fname'];
        $stud_mname = $row['stud_mname'];
        $stud_lname = $row['stud_lname'];
        $stud_password = $row['stud_password'];
        $program_id = $row['program_id'];  // Retrieve the program ID from the database
    }
}

if (isset($_POST['update'])) {
    $stud_id = $_POST['stud_id'];
    $program_id = $_POST['prog_id'];
    $stud_no = $_POST['stud_no'];
    $stud_fname = $_POST['stud_fname'];
    $stud_mname = $_POST['stud_mname'];
    $stud_lname = $_POST['stud_lname'];
    $stud_password = $_POST['stud_password'];

    if (empty($program_id) || empty($stud_no) || empty($stud_fname) || empty($stud_mname) || empty($stud_lname) || empty($stud_password)) {
        // Display error message if any field is empty
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
        $sql = "UPDATE `tbl_student` SET 
        program_id = :program_id,
        stud_no = :stud_no,
        stud_fname = :stud_fname,
        stud_mname = :stud_mname,
        stud_lname = :stud_lname,
        stud_password = :stud_password
        WHERE stud_id = :stud_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":stud_no", $stud_no);
        $stmt->bindParam(":stud_fname", $stud_fname);
        $stmt->bindParam(":stud_mname", $stud_mname);
        $stmt->bindParam(":stud_lname", $stud_lname);
        $stmt->bindParam(":stud_password", $stud_password);
        $stmt->bindParam(":stud_id", $stud_id);

        if ($stmt->execute()) {
            // Display success message if update is successful
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Student updated successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "student.php";
                            });
                        });
                    </script>';
        } else {
            // Display error message if update fails
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to update student.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "student.php";
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
    <title>Edit Student</title>
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
        <div class="container">

            <div class="text-center mb-1 mt-2">
                <h1>Edit Student</h1>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <form action="edit_student.php" method="post">

                        <!-- Program ID Dropdown -->
                        <div class="mb-3">
                            <label for="prog_id" class="form-label">Program ID</label>
                            <select class="form-select" id="prog_id" name="prog_id" required>
                                <option value="" disabled>Select Program</option>
                                <?php foreach ($programs as $program) : ?>
                                    <option value="<?php echo $program['program_id']; ?>" <?php echo ($program['program_id'] == $program_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($program['program_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Number Input -->
                        <div class="mb-3">
                            <label for="stud_no" class="form-label">Student No.</label>
                            <input type="text" class="form-control" id="stud_no" value="<?php echo $stud_no; ?>" name="stud_no" required autocomplete="off" pattern="[0-9-]*">
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
                            <input type="text" class="form-control" id="stud_fname" name="stud_fname" value="<?php echo $stud_fname; ?>" pattern="[A-Za-z ]+" title="Please enter only alphabetic characters" required>
                        </div>

                        <!-- Middle Name Input -->
                        <div class="mb-3">
                            <label for="stud_mname" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="stud_mname" name="stud_mname" value="<?php echo $stud_mname; ?>" pattern="[A-Za-z ]+" title="Please enter only alphabetic characters" required>
                        </div>

                        <!-- Last Name Input -->
                        <div class="mb-3">
                            <label for="stud_lname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="stud_lname" name="stud_lname" value="<?php echo $stud_lname; ?>" pattern="[A-Za-z ]+" title="Please enter only alphabetic characters" required>
                        </div>


                        <!-- Password Input -->
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
                        <div class="mb-3">
                            <label for="stud_password" class="form-label">Password</label>
                            <div class="password-input-container">
                                <span class="toggle-password" onclick="togglePasswordVisibility()"><i class="far fa-eye-slash"></i></span>
                                <input type="password" class="form-control" value="<?php echo htmlspecialchars($stud_password); ?>" id="stud_password" name="stud_password" pattern="^(?!.*[.<>\.])(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}$" title="Must contain at least one number and one uppercase and lowercase letter, and at least 12 or more characters, without spaces" required>

                            </div>
                        </div>


                        <script>
                            function validatePasswordInput() {
                                var passwordInput = document.getElementById("stud_password");
                                var passwordValue = passwordInput.value;

                                // Remove spaces
                                passwordValue = passwordValue.replace(/\s/g, '');

                                // Remove special characters
                                passwordValue = passwordValue.replace(/[<>\/]/g, '');

                                // Update the input value
                                passwordInput.value = passwordValue;
                            }

                            // Add an event listener to the input field to validate input on change
                            document.getElementById("stud_password").addEventListener("input", validatePasswordInput);
                        </script>



                        <!-- Hidden Student ID and Submit Button -->
                        <input type="hidden" name="stud_id" value="<?php echo $stud_id; ?>">
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