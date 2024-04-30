<?php
require_once "../api/db-connect.php"; // Adjust the path as needed
session_start();

if (isset($_SESSION['program_id']) && isset($_SESSION['year_id'])) {

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
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to handle database errors
function handleDatabaseError($errorMessage)
{
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

// Set a flag to indicate if questions are found
$questionsFound = !empty($result);

// Set a flag to check if all questions are answered
$allQuestionsAnswered = false;
if ($questionsFound) {
    $allQuestionsAnswered = true;
    foreach ($result as $question) {
        if (!isset($_POST['answer_' . $question['question_id']])) {
            $allQuestionsAnswered = false;
            break;
        }
    }
}

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
        // Calculate the score
        $score = 0;
        $total_questions = count($answers); // Get the total number of questions attempted
        // Loop through each question and process the answers
        foreach ($answers as $question_id => $answer) {
            // Fetch the maximum attempt_id for the current combination
            $sqlMaxAttempt = "SELECT COALESCE(MAX(attempt_id), 0) AS max_attempt FROM tbl_quiz_answers WHERE quiz_id = :quiz_id AND student_id = :student_id AND question_id = :question_id";
            $stmtMaxAttempt = $conn->prepare($sqlMaxAttempt);
            $stmtMaxAttempt->bindParam(":quiz_id", $module_id, PDO::PARAM_INT);
            $stmtMaxAttempt->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
            $stmtMaxAttempt->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtMaxAttempt->execute();
            $max_attempt_row = $stmtMaxAttempt->fetch(PDO::FETCH_ASSOC);
            $new_attempt_id = $max_attempt_row['max_attempt'] + 1; // Increment the maximum attempt_id by 1

            // Fetch the correct answer for the question
            $sqlCorrectAnswer = "SELECT question_answer FROM tbl_question WHERE question_id = :question_id";
            $stmtCorrectAnswer = $conn->prepare($sqlCorrectAnswer);
            $stmtCorrectAnswer->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtCorrectAnswer->execute();
            $correct_answer = $stmtCorrectAnswer->fetch(PDO::FETCH_ASSOC)['question_answer'];

            // Compare the answer with the correct answer to calculate the score
            if ($answer === $correct_answer) {
                $score++;
            }

            // Insert the answer into tbl_quiz_answers with the new attempt_id
            $sqlInsertAnswer = "INSERT INTO tbl_quiz_answers (quiz_id, student_id, question_id, chosen_answer, attempt_id) VALUES (:quiz_id, :student_id, :question_id, :chosen_answer, :attempt_id)";
            $stmtInsertAnswer = $conn->prepare($sqlInsertAnswer);
            $stmtInsertAnswer->bindParam(":quiz_id", $module_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":chosen_answer", $answer, PDO::PARAM_STR);
            $stmtInsertAnswer->bindParam(":attempt_id", $new_attempt_id, PDO::PARAM_INT);
            if (!$stmtInsertAnswer->execute()) {
                handleDatabaseError("Failed to insert quiz answer into the database.");
            }

            // Additional processing as needed...
        }

        // Insert the result into tbl_result
        $sql = "INSERT INTO tbl_result (module_id, stud_id, result_score, total_questions) 
                VALUES (:module_id, :stud_id, :result_score, :total_questions)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
        $stmt->bindParam(":stud_id", $stud_id, PDO::PARAM_INT);
        $stmt->bindParam(":result_score", $score, PDO::PARAM_INT);
        $stmt->bindParam(":total_questions", $total_questions, PDO::PARAM_INT); // Pass the total questions attempted
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="quiz.css" type="text/css">
    <link rel="stylesheet" href="style.css" type="text/css">
    <style>

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
                    <h1 id="quiz-title">QUIZ</h1>
                </div>
                <?php
                $noQuestionsFound = empty($result); // Check if no questions are found
                $allQuestionsAnswered = $questionsFound && $allQuestionsAnswered; // Check if all questions are answered
                ?>
                <form method="post">
                    <input type="hidden" name="user_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">

                    <?php if (!empty($result)) : ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($result as $question) : ?>
                            <div class="question-box">
                                <div class="question-text"><?php echo $counter . ". " . sanitizeInput($question['question_text']); ?></div>
                                <?php foreach (['A', 'B', 'C', 'D'] as $option) : ?>
                                    <?php $optionKey = 'question_' . $option; ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answer_<?php echo $question['question_id']; ?>" id="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>" value="<?php echo sanitizeInput($question[$optionKey]); ?>">
                                        <label class="form-check-label" for="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>"><?php echo sanitizeInput($question[$optionKey]); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php $counter++; ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No questions found.</p>
                        <?php $allQuestionsAnswered = false; // No questions found, so set this to false 
                        ?>
                    <?php endif; ?>
                    <button id="submit-btn" type="submit" class="btn btn-primary" <?php if ($noQuestionsFound || !$allQuestionsAnswered) echo 'hidden'; ?>>Submit</button>
                </form>

                <br>
                <br>
        </main>
        <!-- Include Bootstrap 5 JS and Popper.js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>




<script>
    // Function to check if all questions are answered
    function checkAllQuestionsAnswered() {
        const questions = document.querySelectorAll('.question-box');
        for (let question of questions) {
            const radioButtons = question.querySelectorAll('input[type="radio"]');
            let answered = false;
            for (let radioButton of radioButtons) {
                if (radioButton.checked) {
                    answered = true;
                    break;
                }
            }
            if (!answered) {
                return false; // Not all questions answered
            }
        }
        return true; // All questions answered
    }

    // Function to enable or disable the submit button based on questions answered
    function updateSubmitButton() {
        const submitBtn = document.getElementById('submit-btn');
        if (checkAllQuestionsAnswered()) {
            submitBtn.removeAttribute('disabled');
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
        }
    }

    // Event listener to check and update submit button status
    document.addEventListener('DOMContentLoaded', function() {
        updateSubmitButton();
        const radioButtons = document.querySelectorAll('input[type="radio"]');
        for (let radioButton of radioButtons) {
            radioButton.addEventListener('change', updateSubmitButton);
        }
    });

    const hamBurger = document.querySelector(".toggle-btn");
    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });

    // Function to check if any question is answered
    function anyQuestionAnswered() {
        const questions = document.querySelectorAll('.question-box');
        for (let question of questions) {
            const radioButtons = question.querySelectorAll('input[type="radio"]');
            for (let radioButton of radioButtons) {
                if (radioButton.checked) {
                    return true; // At least one question answered
                }
            }
        }
        return false; // No question answered
    }

    // Function to update the visibility of the submit button
    function updateSubmitButtonVisibility() {
        const submitBtn = document.getElementById('submit-btn');
        const noQuestionsFound = <?php echo $noQuestionsFound ? 'true' : 'false'; ?>;
        if (!noQuestionsFound && anyQuestionAnswered()) {
            submitBtn.removeAttribute('hidden');
        } else {
            submitBtn.setAttribute('hidden', 'hidden');
        }
    }

    // Event listener to check and update submit button visibility
    document.addEventListener('DOMContentLoaded', function() {
        updateSubmitButtonVisibility();
        const radioButtons = document.querySelectorAll('input[type="radio"]');
        for (let radioButton of radioButtons) {
            radioButton.addEventListener('change', updateSubmitButtonVisibility);
        }
    });
</script>