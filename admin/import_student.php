<?php
session_start();
require("../api/db-connect.php");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST["import"])) {
    $fileName = $_FILES["excel"]["name"];
    $fileExtension = explode('.', $fileName);
    $fileExtension = strtolower(end($fileExtension));

    $newFileName = date("Y.m.d") . " - " . date("h.i.sa") . "." . $fileExtension;

    $targetDirectory = "../uploads/" . $newFileName;

    move_uploaded_file($_FILES["excel"]["tmp_name"], $targetDirectory);

    ini_set('display_errors', 0);
    error_reporting(0);

    require "../excelReader/excel_reader2.php";
    require "../excelReader/SpreadsheetReader.php";

    $reader = new SpreadsheetReader($targetDirectory);

    foreach ($reader as $key => $row) {
        $program_id = $row[0];
        $stud_no = $row[2];
        $stud_fname = $row[3];
        $stud_mname = $row[4];
        $stud_lname = $row[5];
        $stud_password = $row[6];
        $stud_status = $row[7];

        $sql = "INSERT INTO `tbl_student`(`program_id`, `year_id`, `stud_no`, `stud_fname`, `stud_mname`, `stud_lname`, `stud_password`, `stud_status`)";
        $sql .= " VALUES (:program_id,:year_id,:stud_no,:stud_fname,:stud_mname,:stud_lname,:stud_password,:stud_status)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":stud_no", $stud_no);
        $stmt->bindParam(":stud_fname", $stud_fname);
        $stmt->bindParam(":stud_mname", $stud_mname);
        $stmt->bindParam(":stud_lname", $stud_lname);
        $stmt->bindParam(":stud_password", $stud_password);
        $stmt->bindParam(":stud_status", $stud_status);

        // Execute the statement to insert data
        if ($stmt->execute()) {
            // Data was successfully inserted
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Success!",
                            text: "Student imported successfully!",
                            icon: "success"
                        }).then(() => {
                            window.location.href = "student.php";
                        });
                    });
                </script>';
        } else {
            echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Failed!",
                        text: "Failed to import student.",
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
    <title>Import Student</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
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
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-10">
                        <div class="text-center mb-4">
                            <h1>Import Student</h1>
                        </div>
                        <div class="container">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <input type="file" name="excel" class="form-control" required accept=".xlsx, .xls">
                                </div>
                                <button type="submit" name="import" class="btn btn-success">Save</button>
                            </form>
                        </div>
                    </div>
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
</script>

</html>