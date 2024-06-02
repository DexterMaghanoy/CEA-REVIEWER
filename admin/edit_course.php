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

    $program_id = $_POST['program_id'];
    $program_name = $_POST['program_name'];

    if (empty($program_id) || empty($program_name)) {
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
                    window.location.href = "courses.php";
                });
            });
        </script>';
    } else {
        $sql = "UPDATE `tbl_program` SET 
        program_name = :program_name
        WHERE program_id = :program_id";

        $stmt = $conn->prepare($sql);
        $program_name = htmlspecialchars($program_name, ENT_QUOTES, 'UTF-8');
        $stmt->bindParam(":program_name", $program_name);
        $stmt->bindParam(":program_id", $program_id, PDO::PARAM_INT);

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
                                window.location.href = "courses.php?program_id=' . $_SESSION['program_id'] . '&course_id=' . $_SESSION['course_id'] . '&module_id=' . $_GET['module_id'] . '";
          
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
                            window.location.href = "courses.php";
                        });
                    });
                    </script>';
        }
    }
}

if (isset($_GET['program_id'])) {
    $program_id = $_GET['program_id'];
    $sql = "SELECT * FROM tbl_program WHERE program_id = :program_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":program_id", $program_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $program_id = $row['program_id'];
        $program_name = $row['program_name'];
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
            <div class="text-center mb-4">
                <h1>Edit Course</h1>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <form action="edit_course.php" method="post">
                        <!-- Program Name Input -->
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="program_name" name="program_name" value="<?php echo htmlspecialchars($program_name); ?>" pattern="[A-Za-z0-9\s]+" title="Invalid Input" required>
                        </div>

                        <!-- Hidden Employee ID and Submit Button -->
                        <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
                        <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
                    </form>
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