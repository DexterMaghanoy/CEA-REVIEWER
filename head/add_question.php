<?php
session_start();
require '../api/db-connect.php';

// Check if program_id, course_id, and module_id are set in the URL parameters
if (isset($_GET['program_id']) && isset($_GET['course_id']) && isset($_GET['module_id'])) {
    // Set the session variables based on the URL parameters
    $_SESSION['program_id'] = $_GET['program_id'];
    $_SESSION['course_id'] = $_GET['course_id'];
    $_SESSION['module_id'] = $_GET['module_id'];
}

// Retrieve user_id from session
$user_id = $_SESSION['user_id'];

// Check if the form is submitted
if (isset($_POST['save'])) {
    // Retrieve form data
    $question_text = $_POST['question_text'];
    $question_A = $_POST['question_A'];
    $question_B = $_POST['question_B'];
    $question_C = $_POST['question_C'];
    $question_D = $_POST['question_D'];
    $question_answer = $_POST['question_answer'];

    // Validate form data
    if (empty($question_text) || empty($question_A) || empty($question_B) || empty($question_C) || empty($question_D) || empty($question_answer)) {
        // Handle empty fields
        echo '
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>
            <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">
            <script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Failed!",
                        text: "Please input all fields.",
                        icon: "error"
                    });
                });
            </script>';
    } else {
        // Insert new question with module_id and course_id
        $sql = "INSERT INTO `tbl_question` (`module_id`, `question_text`, `question_A`, `question_B`, `question_C`, `question_D`, `question_answer`, `course_id`, `program_id`)
        VALUES (:module_id, :question_text, :question_A, :question_B, :question_C, :question_D, :question_answer, :course_id, :program_id)";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":module_id", $_SESSION['module_id']);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_A", $question_A);
            $stmt->bindParam(":question_B", $question_B);
            $stmt->bindParam(":question_C", $question_C);
            $stmt->bindParam(":question_D", $question_D);
            $stmt->bindParam(":question_answer", $question_answer);
            $stmt->bindParam(":course_id", $_SESSION['course_id']);
            $stmt->bindParam(":program_id", $_SESSION['program_id']);

            // Execute the query
            if ($stmt->execute()) {
                // Handle successful query execution
                echo '
                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>
                    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">
                    <script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Question added successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "course.php";
                            });
                        });
                    </script>';
            } else {
                // Handle query execution failure
                echo '
                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>
                    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">
                    <script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Failed!",
                                text: "Failed to add question.",
                                icon: "error"
                            });
                        });
                    </script>';
            }
        } catch (PDOException $e) {
            // Handle PDO exception
            echo "Error: " . $e->getMessage();
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
    <title>Add Question</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php
        include 'back.php';
        ?>

        <div class="container">
            <div class="text-center mb-4">
                <h1>Add Question</h1>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <form action="add_question.php" method="post">
                        <!-- Question Text Input -->
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question</label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                        </div>
                        <!-- Option Inputs -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="question_A" class="form-label">Option A</label>
                                <input type="text" class="form-control" id="question_A" name="question_A" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="question_B" class="form-label">Option B</label>
                                <input type="text" class="form-control" id="question_B" name="question_B" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="question_C" class="form-label">Option C</label>
                                <input type="text" class="form-control" id="question_C" name="question_C" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="question_D" class="form-label">Option D</label>
                                <input type="text" class="form-control" id="question_D" name="question_D" required>
                            </div>
                        </div>
                        <!-- Answer Input -->
                        <div class="mb-3">
                            <label for="question_answer" class="form-label">Correct Answer</label>
                            <input type="text" class="form-control" id="question_answer" name="question_answer" required>
                        </div>
                        <!-- Hidden Course ID and Submit Button -->
                        <input type="hidden" name="course_id" value="<?php echo $_SESSION['course_id']; ?>">
                        <button type="submit" class="btn btn-primary" name="save">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>

</html>

<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>