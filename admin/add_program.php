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

    $program_name = $_POST['program_name'];
    $program_status = 1;

    if (empty($program_name)) {
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
        $checkStmt = $conn->prepare("SELECT program_id FROM tbl_program WHERE program_name = :program_name");
        $checkStmt->bindParam(':program_name', $program_name);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Program Already Exist!",
                    icon: "error"
                });
            });
            </script>';
        } else {
            // Insert new program
            $sql = "INSERT INTO `tbl_program`(`program_name`, `program_status`) VALUES (:program_name, :program_status)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_name", $program_name);
            $stmt->bindParam(":program_status", $program_status);

            if ($stmt->execute()) {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Success!",
                            text: "Program added successfully.",
                            icon: "success"
                        }).then(() => {
                            window.location.href = "program.php";
                        });
                    });
                </script>';
            } else {
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to add program.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "program.php";
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
        <h1>Add Program</h1>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <form action="add_program.php" method="post">
                    <!-- Program Name Input -->
                    <div class="mb-3">
                        <label for="program_name" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="program_name" name="program_name" pattern="^(?!.*\bBS in\b).{5,}$" required>
                    </div>
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

</script>
</html>