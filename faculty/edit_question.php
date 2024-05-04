<?php
session_start();

require '../api/db-connect.php';

if(isset($_SESSION['program_id'])){
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['update'])) {
    $question_id = $_POST['question_id'];
    $question_text = $_POST['question_text'];
    $question_A = $_POST['question_A'];
    $question_B = $_POST['question_B'];
    $question_C = $_POST['question_C'];
    $question_D = $_POST['question_D'];
    $question_answer = $_POST['question_answer'];

    if (empty($question_id) || empty($question_text) || empty($question_A) || empty($question_B) || empty($question_C) || empty($question_D) || empty($question_answer)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input all fields.",
                    icon: "error"
                });
            });
        </script>';
    } else {
        $sql = "UPDATE `tbl_question` SET 
                question_text = :question_text,
                question_A = :question_A,
                question_B = :question_B,
                question_C = :question_C,
                question_D = :question_D,
                question_answer = :question_answer
                WHERE question_id = :question_id"; 

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":question_text", $question_text);
        $stmt->bindParam(":question_A", $question_A);
        $stmt->bindParam(":question_B", $question_B);
        $stmt->bindParam(":question_C", $question_C);
        $stmt->bindParam(":question_D", $question_D);
        $stmt->bindParam(":question_answer", $question_answer);
        $stmt->bindParam(":question_id", $question_id);

        if ($stmt->execute()) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Success!",
                        text: "Question updated successfully.",
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
                        text: "Failed to update question.",
                        icon: "error"
                    }).then(() => {
                        window.location.href = "course.php";
                    });
                });
            </script>';
        }
    }
}

if (isset($_GET['question_id'])) {
    $question_id = $_GET['question_id'];
    $sql = "SELECT * FROM tbl_question WHERE question_id = :question_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":question_id", $question_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $question_text = $row['question_text'];
        $question_A = $row['question_A'];
        $question_B = $row['question_B'];
        $question_C = $row['question_C'];
        $question_D = $row['question_D'];
        $question_answer = $row['question_answer'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question</title>
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
        <h1>Edit Question</h1>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <form action="edit_question.php" method="post">   
            
                   <!-- Module Number Input -->
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="1" required><?php echo $question_text; ?></textarea>
                    </div>

                    <!-- Option Input -->
                    <div class="mb-3">
                        <label for="question_A" class="form-label">Option A</label>
                        <textarea class="form-control" id="question_A" name="question_A" rows="1" required><?php echo $question_A; ?></textarea>
                    </div>

                    <!-- Option Input -->
                    <div class="mb-3">
                        <label for="question_B" class="form-label">Option B</label>
                        <textarea class="form-control" id="question_B" name="question_B" rows="1" required><?php echo $question_B; ?></textarea>
                    </div>

                    <!-- Option Input -->
                    <div class="mb-3">
                        <label for="question_C" class="form-label">Option C</label>
                        <textarea class="form-control" id="question_C" name="question_C" rows="1" required><?php echo $question_C; ?></textarea>
                    </div>

                    <!-- Option Input -->
                    <div class="mb-3">
                        <label for="question_D" class="form-label">Option D</label>
                        <textarea class="form-control" id="question_D" name="question_D" rows="1" required><?php echo $question_D; ?></textarea>
                    </div>

                    <!-- Option Input -->
                    <div class="mb-3">
                        <label for="question_answer" class="form-label">Answer</label>
                        <textarea class="form-control" id="question_answer" name="question_answer" rows="1" required> <?php echo $question_answer; ?></textarea>
                    </div>

                    <!-- Hidden Employee ID and Submit Button -->
                    <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                    <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
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