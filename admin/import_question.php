<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

// Check if program_id, course_id, and module_id are set in the URL parameters
if (isset($_GET['program_id']) && isset($_GET['course_id']) && isset($_GET['module_id'])) {
    // Set the session variables based on the URL parameters
    $_SESSION['program_id'] = $_GET['program_id'];
    $_SESSION['course_id'] = $_GET['course_id'];
    $_SESSION['module_id'] = $_GET['module_id'];
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

    $test_id = $_POST['test_id'];

    // Check if the necessary session variables are set
    if (isset($_POST['module_id'])) {
        $module_id = $_POST['module_id'];

        $reader = new SpreadsheetReader($targetDirectory);

        foreach ($reader as $key => $row) {
            $question_text = $row[0];
            $question_A = $row[1];
            $question_B = $row[2];
            $question_C = $row[3];
            $question_D = $row[4];
            $question_answer = $row[5];

            $sql = "INSERT INTO `tbl_question` (`module_id`, `question_text`, `question_A`, `question_B`, `question_C`, `question_D`, `question_answer`)";
            $sql .= " VALUES (:module_id, :question_text, :question_A, :question_B, :question_C, :question_D, :question_answer)";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":module_id", $module_id);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_A", $question_A);
            $stmt->bindParam(":question_B", $question_B);
            $stmt->bindParam(":question_C", $question_C);
            $stmt->bindParam(":question_D", $question_D);
            $stmt->bindParam(":question_answer", $question_answer);

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
                            text: "Questions imported successfully!",
                            icon: "success"
                        }).then(() => {
                            window.location.href = "question.php?program_id=' . $_SESSION['program_id'] . '&course_id=' . $_SESSION['course_id'] . '&module_id=' . $_SESSION['module_id'] . '";
                        });
                    });
                </script>';
            } else {
                echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Failed!",
                        text: "Failed to import questions.",
                        icon: "error"
                    }).then(() => {
                        window.location.href = "question.php?program_id=' . $_SESSION['program_id'] . '&course_id=' . $_SESSION['course_id'] . '&module_id=' . $_SESSION['module_id'] . '";
                    });
                });
                </script>';
            }
        }
    } else {
        echo "Session variables not set.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Questions</title>
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
                            <h1>Import Question</h1>
                        </div>
                        <div class="container">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="module_id" value="<?= isset($_GET['module_id']) ? $_GET['module_id'] : ''; ?>">
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