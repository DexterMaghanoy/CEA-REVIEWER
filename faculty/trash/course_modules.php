<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}
    
$user_id = $_SESSION['user_id'];

// Retrieve course ID from URL parameter
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Query modules for the specified course
    $stmt = $conn->prepare("
        SELECT *
        FROM tbl_module
        WHERE course_id = :course_id
    ");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect if course ID is not provided
    header("Location: index.php");
    exit();
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Modules</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <style>
        .search-bar {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Modules for the Course</h1>
                        </div>
                        <div class="input-group search-bar">
                            <!-- Search bar -->
                            <input type="text" class="form-control" id="searchInput" placeholder="Search Module">
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="moduleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Select Module
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="moduleDropdown">
                                <?php if (!empty($modules)) : ?>
                                    <?php foreach ($modules as $module) : ?>
                                        <!-- Module dropdown item -->
                                        <li><a class="dropdown-item" href="view_progress.php?course_id=<?php echo $course_id; ?>&module_id=<?php echo $module['module_id']; ?>"><?php echo $module['module_name']; ?></a></li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <!-- No modules found message -->
                                    <li><span class="dropdown-item disabled">No modules found for this course.</span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");
        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });

        // Function to perform search
        document.getElementById("searchInput").addEventListener("input", function(  ) {
            const searchInput = this.value.toLowerCase();
            const modules = document.querySelectorAll("#moduleDropdown .dropdown-item");
            modules.forEach(function(module) {
                const moduleName = module.textContent.toLowerCase();
                if (moduleName.includes(searchInput)) {
                    module.style.display = "block";
                } else {
                    module.style.display = "none";
                }
            });
        });
    </script>
</body>

</html>
