<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}


global $avoid;

$user_id = $_SESSION['user_id'];

if (isset($_POST['save'])) {

    $course_id = $_POST['course_id'];
    $module_name = $_POST['module_name'];

    $module_file = null;
    if (isset($_FILES["module_file"]) && $_FILES["module_file"]["error"] == UPLOAD_ERR_OK) {
        $module_file = file_get_contents($_FILES["module_file"]["tmp_name"]);
    }

    if (empty($course_id) || empty($module_name) || is_null($module_file)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please fill in all fields.",
                    icon: "error"
                });
            });
        </script>';
        exit();
    } else {
        try {
            // Insert new module
            $sql = "INSERT INTO `tbl_module` (`program_id`, `course_id`, `module_name`, `module_file`)
                VALUES (:program_id, :course_id, :module_name, :module_file)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":program_id", $program_id);
            $stmt->bindParam(":course_id", $course_id);
            $stmt->bindParam(":module_name", $module_name);
            $stmt->bindParam(":module_file", $module_file, PDO::PARAM_LOB);

            if ($stmt->execute()) {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Module added successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "subjects.php";
                            });
                        });
                    </script>';
                exit();
            } else {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Failed!",
                                text: "Failed to add module.",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "subjects.php";
                            });
                        });
                    </script>';
                exit();
            }
        } catch (PDOException $e) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            if (strpos($e->getMessage(), 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away') !== false) {
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Failed!",
                                text: "The uploaded file is too large.",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "./subjects.php";
                            });
                        });
                    </script>';
                exit();
            } else {
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Failed!",
                                text: "An unexpected error occurred.",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "subjects.php";
                            });
                        });
                    </script>';
                exit();
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
    <title>Add Module</title>
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
            <div class="text-center mb-5 mt-4">
                <?php
                $courseSql = "SELECT course_name FROM tbl_course WHERE course_id = :course_id";
                $courseStmt = $conn->prepare($courseSql);
                $courseStmt->bindParam(':course_id', $_GET['course_id'], PDO::PARAM_INT);
                $courseStmt->execute();
                $SubjectName = $courseStmt->fetch(PDO::FETCH_ASSOC);

                // Check if course name is fetched successfully
                if ($SubjectName) {
                    $courseName = $SubjectName['course_name'];
                } else {
                    $courseName = "Unknown Course"; // Default value if course name not found
                }
                ?>
                <h1>Add Module: <span style="font-weight: normal;"><?php echo htmlspecialchars($courseName); ?></span></h1>
            </div>
            <div class="row justify-content-center mt-5 mb-5">
                <div class="col-md-5">
                    <form action="add_module.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="module_name" class="form-label">Module Title</label>
                            <input type="text" class="form-control" id="module_name" name="module_name" required pattern="^[^/<>*]+$" title="Please enter a valid module name">
                        </div>
                        <div class="mb-3">
                            <label for="module_file" class="form-label">Module File</label>
                            <input type="file" class="form-control" id="module_file" name="module_file" accept=".pdf" required>
                        </div>
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars(isset($_GET['course_id']) ? $_GET['course_id'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="submit" class="btn btn-success mt-2" value="Save" name="save">
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    </div>
</body>

</html>
<script>
    const hamBurger = document.querySelector(".toggle-btn");
    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>