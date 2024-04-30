<?php
session_start();

require '../api/db-connect.php';

if(isset($_SESSION['program_id'])){
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['save'])) {

    $course_id = $_POST['course_id'];
    $module_name = $_POST['module_name'];
    $module_file = file_get_contents($_FILES["module_file"]["tmp_name"]);

    if (empty($course_id) || empty($module_name) || empty($module_file)) {
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
            // Generate module number automatically
            $module_number = generateModuleNumber(); // Call a function to generate module number

            // Insert new module
            $sql = "INSERT INTO `tbl_module`(`course_id`, `module_number`, `module_name`, `module_file`)
             VALUES (:course_id,:module_number,:module_name,:module_file)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":course_id", $course_id);
            $stmt->bindParam(":module_number", $module_number);
            $stmt->bindParam(":module_name", $module_name);
            $stmt->bindParam(":module_file", $module_file);

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
                            window.location.href = "course.php";
                        });
                    });
                </script>';
            } else {
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to add module.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "course.php";
                        });
                    });
                    </script>';
            }
        }
    }

// Function to generate module number
function generateModuleNumber() {
    // You can implement your logic here to generate the module number dynamically
    // For example, you can query the database to get the last module number and increment it
    // Here's a simple example:
    // $lastModuleNumber = 100; // Example last module number
    // $newModuleNumber = $lastModuleNumber + 1;
    // return $newModuleNumber;
    // Modify this logic based on your requirements
    // For simplicity, let's return a static value for now
    return 1;
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
        <h1>Add Module</h1>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <form action="add_module.php" method="post" enctype="multipart/form-data">

                    <!-- Module Title Input -->
                    <div class="mb-3">
                        <label for="module_name" class="form-label">Module Title</label>
                        <input type="text" class="form-control" id="module_name" name="module_name" required>
                    </div>
                    
                    <!-- Module File Input -->
                    <div class="mb-3">
                        <label for="module_file" class="form-label">Module File</label>
                        <input type="file" class="form-control" id="module_file" name="module_file" accept=".pdf" required>
                        <div>
                    </div>
                    
                    <!-- Hidden Employee ID and Submit Button -->
                    <input type="hidden" name="course_id" value="<?php echo isset($_GET['course_id']) ? $_GET['course_id'] : ''; ?>">

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
