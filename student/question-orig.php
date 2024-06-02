<?php
require_once "../api/db-connect.php"; // Adjust the path as needed
session_start();

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
}

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
    // Prepare SQL query to fetch courses for the given program
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();

    // Fetch the result and store it in a variable to use later

    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect to login page if session data is not set
    header("Location: ../index.php");

    exit();
}


function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function handleDatabaseError($errorMessage)
{
    error_log("Database Error: " . $errorMessage);
    header("Location: error.php");
    exit();
}

if (!isset($_GET['module_id'])) {
    handleDatabaseError("Module ID is not provided.");
}

$module_id = sanitizeInput($_GET['module_id']);

$sql = "SELECT * FROM tbl_question WHERE module_id = :module_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
if (!$stmt->execute()) {
    handleDatabaseError("Failed to fetch questions from the database.");
}
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$questionsFound = !empty($result);

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
            $sqlMaxAttempt = "SELECT COALESCE(MAX(attempt_id), 0) AS max_attempt FROM tbl_quiz_answers WHERE module_id = :module_id AND student_id = :student_id AND question_id = :question_id";
            $stmtMaxAttempt = $conn->prepare($sqlMaxAttempt);
            $stmtMaxAttempt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
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


            $quiz_type = 1;
            // Insert the answer into tbl_quiz_answers with the new attempt_id
            $sqlInsertAnswer = "INSERT INTO tbl_quiz_answers (module_id, student_id, question_id, chosen_answer, attempt_id, quiz_type, course_id) VALUES (:module_id, :student_id, :question_id, :chosen_answer, :attempt_id, :quiz_type, :course_id)";
            $stmtInsertAnswer = $conn->prepare($sqlInsertAnswer);
            $stmtInsertAnswer->bindParam(":module_id", $module_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":chosen_answer", $answer, PDO::PARAM_STR);
            $stmtInsertAnswer->bindParam(":attempt_id", $new_attempt_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":course_id", $course_id, PDO::PARAM_INT);

            if (!$stmtInsertAnswer->execute()) {
                handleDatabaseError("Failed to insert quiz answer into the database.");
            }
        }
        $quiz_type = 1;
        $passingScore = 0.5;
        $passStatus = ($score / $total_questions) >= $passingScore ? 1 : 0;
        $sql = "INSERT INTO tbl_result (module_id, stud_id, result_score, total_questions, quiz_type, course_id, program_id, result_status) 
                 VALUES (:module_id, :stud_id, :result_score, :total_questions, :quiz_type, :course_id, :program_id, :result_status)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
        $stmt->bindParam(":stud_id", $stud_id, PDO::PARAM_INT);
        $stmt->bindParam(":result_score", $score, PDO::PARAM_INT);
        $stmt->bindParam(":total_questions", $total_questions, PDO::PARAM_INT); // Pass the total questions attempted
        $stmt->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
        $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        $stmt->bindParam(":program_id", $program_id, PDO::PARAM_INT); // Bind program_id
        $stmt->bindParam(":result_status", $passStatus, PDO::PARAM_INT); // Bind result_status

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
                    window.location.href = "dashboard.php";
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
    <title>Questions</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <style>
        .question-text {
            font-size: 20px;
            /* Adjust the font size as needed */
            font-weight: bold;
            /* Optionally make the text bold */
            margin-bottom: 10px;
            /* Add some space between questions */




        }

        .form-check-input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #000;
            border-radius: 50%;
            outline: none;
            margin-right: 5px;
            /* Adjust the margin as needed */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>


        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8" style="background-color: #A1EEBD; border-radius: 10px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); border: 2px solid rgba(0, 0, 0, 0.1);">
                    <br> <br>
                    <center>
                        <h1 class="mb-4" style="font-size: 30px;">
                            <img height="35" src="./icons/test.gif" alt="" style="border-radius: 50%; border: 2px solid #000;">
                            Questions: <?php
                                        $sql = "SELECT m.module_name
            FROM tbl_module AS m
            WHERE m.module_id = :module_id
            AND m.course_id = :course_id";
                                        $stmtModule = $conn->prepare($sql);
                                        $stmtModule->bindParam(':module_id', $module_id, PDO::PARAM_INT); // Assuming $module_id is defined elsewhere
                                        $stmtModule->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                                        $stmtModule->execute();
                                        $Module = $stmtModule->fetch(PDO::FETCH_ASSOC);
                                        if ($Module !== false) {
                                            $Module = $Module['module_name'];
                                        } else {
                                            $Module = "Unknown";
                                        }
                                        ?> <span><?php echo "'" . $Module . "'"; ?> </span>
                        </h1>
                    </center>
                    <br> <br>

                    <form style="font-size: 20px;" id="quiz-form" method="post">
                        <input type="hidden" name="user_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                        <?php if (!empty($result)) : ?>
                            <?php $counter = 0; ?>
                            <?php foreach ($result as $key => $question) : ?>
                                <div class="question-box <?php echo $counter === 0 ? '' : 'd-none'; ?>">
                                    <div class="question-text"><?php echo $counter + 1 . ". " . sanitizeInput($question['question_text']); ?></div>
                                    <?php foreach (['A', 'B', 'C', 'D'] as $option) : ?>
                                        <?php $optionKey = 'question_' . $option; ?>
                                        <div style="padding-left: 100px;" class="form-check">
                                            <input class="form-check-input" type="radio" name="answer_<?php echo $question['question_id']; ?>" id="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>" value="<?php echo sanitizeInput($question[$optionKey]); ?>">
                                            <label class="form-check-label" for="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>"><?php echo sanitizeInput($question[$optionKey]); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $counter++; ?>
                            <?php endforeach; ?>
                            <br> <br>
                            <div class="container" style="background-color: #A1EEBD;">
                                <div class="row">
                                    <div class="col text-start">
                                        <button style="font-size: 20px;" id="back-btn" class="text-start btn btn-primary <?php echo $counter === 0 ? 'd-none' : ''; ?>" type="button">◁ Back</button>
                                    </div>
                                    <div class="col text-end">
                                        <button style="font-size: 20px;" id="submit-btn" type="submit" class="text-end btn btn btn-primary">Submit</button>
                                        <button style="font-size: 20px;" id="next-btn" class="text-end btn btn-primary <?php echo $counter === count($result) - 1 ? 'd-none' : ''; ?>" type="button">Next ▷</button>
                                    </div>
                                </div>
                            </div>
                        <?php else : ?>
                            <p>No questions found.</p>
                        <?php endif; ?>
                    </form>

                    <br>
                    <br>
                </div>
            </div>
        </div>



    </div>
</body>


</html>

<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const questions = document.querySelectorAll('.question-box');
        const backButton = document.getElementById('back-btn');
        const nextButton = document.getElementById('next-btn');
        const submitButton = document.getElementById('submit-btn');
        let currentQuestionIndex = 0;

        function showQuestion(index) {
            questions.forEach((question, i) => {
                question.classList.toggle('d-none', i !== index);
            });
        }

        function updateButtonVisibility() {
            backButton.classList.toggle('d-none', currentQuestionIndex === 0);
            nextButton.classList.toggle('d-none', currentQuestionIndex === questions.length - 1);
            submitButton.classList.toggle('d-none', currentQuestionIndex !== questions.length - 1);
        }

        function isAnyOptionSelected() {
            const currentQuestion = questions[currentQuestionIndex];
            return currentQuestion.querySelector('input[type="radio"]:checked') !== null;
        }

        function updateNextButtonVisibility() {
            nextButton.disabled = !isAnyOptionSelected();
        }

        function updateSubmitButtonVisibility() {
            submitButton.disabled = !isAnyOptionSelected();
        }

        backButton.addEventListener('click', function() {
            currentQuestionIndex--;
            showQuestion(currentQuestionIndex);
            updateButtonVisibility();
            updateNextButtonVisibility();
            updateSubmitButtonVisibility();
        });

        nextButton.addEventListener('click', function() {
            currentQuestionIndex++;
            showQuestion(currentQuestionIndex);
            updateButtonVisibility();
            updateNextButtonVisibility();
            updateSubmitButtonVisibility();
        });

        questions.forEach(question => {
            question.addEventListener('change', function() {
                updateNextButtonVisibility();
                updateSubmitButtonVisibility();
            });
        });

        showQuestion(currentQuestionIndex);
        updateButtonVisibility();
        updateNextButtonVisibility();
        updateSubmitButtonVisibility();
    });
</script>



<!-- Include Bootstrap 5 JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>