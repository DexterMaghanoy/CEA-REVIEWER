<?php
require_once "../api/db-connect.php"; // Adjust the path as needed
session_start();

if(isset($_SESSION['program_id']) && isset($_SESSION['year_id'])) {
    
    $program_id = $_SESSION['program_id'];
    $year_id = $_SESSION['year_id'];

    // Prepare SQL query to fetch courses for the given program and year
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id AND year_id = :year_id AND sem_id = 1";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->bindParam(':year_id', $year_id, PDO::PARAM_INT);
    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect to login page if session data is not set
    header("Location: ../login.php");
    exit();
}
// Function to sanitize user input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to handle database errors
function handleDatabaseError($errorMessage) {
    // Log the error or display a generic message
    error_log("Database Error: " . $errorMessage);
    // Redirect the user to an error page
    header("Location: error.php");
    exit();
}


// Ensure module_id is provided
if (!isset($_GET['module_id'])) {
    handleDatabaseError("Module ID is not provided.");
}

// Fetching questions from the database
$module_id = sanitizeInput($_GET['module_id']);

$sql = "SELECT * FROM tbl_question WHERE module_id = :module_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
if (!$stmt->execute()) {
    handleDatabaseError("Failed to fetch questions from the database.");
}
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);


shuffle($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Processing form submission
    if (isset($_SESSION['stud_id'])) {
        $stud_id = $_SESSION['stud_id'];
        $user_id = $_POST['user_id'];
        // Retrieve submitted answers
        $answers = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'answer_') === 0) {
                $question_id = substr($key, strlen('answer_'));
                $answers[$question_id] = sanitizeInput($value);
            }
        }
        $_SESSION['test_completed'] = true;
        // Fetch correct answers from the database
        $sql = "SELECT question_id, question_answer FROM tbl_question WHERE module_id = :module_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            handleDatabaseError("Failed to fetch correct answers from the database.");
        }
        $correct_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $score = 0;
        foreach ($correct_answers as $question) {
            $question_id = $question['question_id'];
            // Check if answer matches the correct answer
            if (isset($answers[$question_id]) && $answers[$question_id] === $question['question_answer']) {
                $score++;
            }
        }

        // Insert the result into tbl_result
        $sql = "INSERT INTO tbl_result (module_id, user_id, stud_id, result_score) VALUES (:module_id, :user_id, :stud_id, :result_score)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $_POST['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(":stud_id", $stud_id, PDO::PARAM_INT);
        $stmt->bindParam(":result_score", $score, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            handleDatabaseError("Failed to insert result into the database.");
        }
            // Redirect to index page after submission
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Success!",
                    text: "Good Job!",
                    icon: "success"
                }).then(() => {
                    window.location.href = "index.php";
                });
            });
        </script>';
    } else {
        handleDatabaseError("Student ID is not set in session.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
   <style>
        /* Custom CSS */
        body {
            background-color: #f8f9fa;
        }
        #content {
            max-width: 100000px; /* Adjust the max-width here */
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .question-box {
            margin-bottom: 30px;
            width: 10000%;
        }
        .question-text {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .form-check-label {
            margin-left: 5px;
            font-weight: normal;
        }
        .btn-primary {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            transition-duration: 0.4s;
            cursor: pointer;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background-color: #45a049;
            color: white;
        }
    </style>
</head>
<body>
<div class="wrapper">
<?php
include 'sidebar.php';
?>
<main id="content">
    <form method="post">
      <input type="hidden" name="user_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
        <div class="mb-3">
            <div class="col-md-6"> <!-- Adjust the column width as needed -->
            </div>
        </div>
        <?php if (!empty($result)): ?>
            <?php $counter = 1; ?>
            <?php foreach ($result as $question): ?>
                <div class="question-box">
                    <div class="question-text"><?php echo $counter . ". " . sanitizeInput($question['question_text']); ?></div>
                    <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                        <?php $optionKey = 'question_' . $option; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answer_<?php echo $question['question_id']; ?>" id="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>" value="<?php echo sanitizeInput($question[$optionKey]); ?>">
                            <label class="form-check-label" for="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>"><?php echo sanitizeInput($question[$optionKey]); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php $counter++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No questions found.</p>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</main>
<!-- Include Bootstrap 5 JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
