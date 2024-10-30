<?php
require_once "../api/db-connect.php"; // Adjust the path as needed
session_start();

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
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Prepare SQL query to fetch questions related to the course with course_status = 1 and module_status = 1
    $sql = "SELECT q.*, m.module_id 
            FROM tbl_question q 
            INNER JOIN tbl_course c ON q.course_id = c.course_id 
            INNER JOIN tbl_module m ON q.module_id = m.module_id 
            WHERE q.course_id = :course_id 
            AND c.program_id = :program_id 
            AND c.course_status = 1
            AND m.module_status = 1
            ORDER BY RAND() LIMIT 15";

    // Prepare and execute the SQL query
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the questions
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Shuffle the questions
    shuffle($questions);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['stud_id'])) {
        $stud_id = $_SESSION['stud_id'];
        $quiz_type = 2; // Setting quiz_type to 2

        // Retrieve submitted answers
        $answers = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'answer_') === 0) {
                $question_id = substr($key, strlen('answer_'));
                $answers[$question_id] = htmlspecialchars($value); // Sanitize input
            }
        }

        // Check if this is the first attempt for this specific combination
        $sqlMaxAttempt = "SELECT COALESCE(MAX(attempt_id), 0) AS max_attempt 
                          FROM tbl_quiz_answers 
                          WHERE course_id = :course_id 
                          AND student_id = :student_id 
                          AND quiz_type = :quiz_type";
        $stmtMaxAttempt = $conn->prepare($sqlMaxAttempt);
        $stmtMaxAttempt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        $stmtMaxAttempt->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
        $stmtMaxAttempt->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
        $stmtMaxAttempt->execute();
        $max_attempt_row = $stmtMaxAttempt->fetch(PDO::FETCH_ASSOC);
        $new_attempt_id = $max_attempt_row['max_attempt'] + 1; // Increment the maximum attempt_id by 1

        // Calculate the score
        $score = 0;
        $total_questions = count($answers);
        foreach ($answers as $question_id => $answer) {
            // Fetch the correct answer and module_id for the question
            $sqlQuestionDetails = "SELECT question_answer, module_id FROM tbl_question WHERE question_id = :question_id";
            $stmtQuestionDetails = $conn->prepare($sqlQuestionDetails);
            $stmtQuestionDetails->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtQuestionDetails->execute();
            $questionDetails = $stmtQuestionDetails->fetch(PDO::FETCH_ASSOC);
            $correct_answer = $questionDetails['question_answer'];
            $module_id = $questionDetails['module_id'];

            if ($answer === $correct_answer) {
                $score++;
            }

            // Insert the answer into tbl_quiz_answers
            $sqlInsertAnswer = "INSERT INTO tbl_quiz_answers (course_id, module_id, student_id, question_id, chosen_answer, quiz_type, attempt_id, answer_status) 
                                VALUES (:course_id, :module_id, :student_id, :question_id, :chosen_answer, :quiz_type, :attempt_id, :answer_status)";
            $stmtInsertAnswer = $conn->prepare($sqlInsertAnswer);
            $stmtInsertAnswer->bindParam(":course_id", $course_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":module_id", $module_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":chosen_answer", $answer, PDO::PARAM_STR);
            $stmtInsertAnswer->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":attempt_id", $new_attempt_id, PDO::PARAM_INT); // Use the new attempt_id for all answers
            $answer_status = ($answer === $correct_answer) ? 1 : 0;
            $stmtInsertAnswer->bindParam(":answer_status", $answer_status, PDO::PARAM_INT);

            $stmtInsertAnswer->execute();
        }

        // Insert the result into tbl_result


        $sql = "SELECT pass_rate FROM tbl_passrate ORDER BY pass_id DESC LIMIT 1";
        $stmtPass_rate = $conn->prepare($sql);
        $stmtPass_rate->execute();
        $passRate = $stmtPass_rate->fetchColumn();

        $passingScore = $passRate / 100;

        $passStatus = ($score / $total_questions) >= $passingScore ? 1 : 0;

        $sqlInsertResult = "INSERT INTO tbl_result (course_id, program_id, module_id, stud_id, result_score, total_questions, quiz_type, result_status, attempt_id) 
                            VALUES (:course_id, :program_id, :module_id, :stud_id, :result_score, :total_questions, :quiz_type, :result_status, :attempt_id)";
        $stmtInsertResult = $conn->prepare($sqlInsertResult);
        $stmtInsertResult->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":program_id", $program_id, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":module_id", $module_id, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":stud_id", $stud_id, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":result_score", $score, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":total_questions", $total_questions, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":result_status", $passStatus, PDO::PARAM_INT);
        $stmtInsertResult->bindParam(":attempt_id", $new_attempt_id, PDO::PARAM_INT); // Use the new attempt_id

        $stmtInsertResult->execute();
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
    // Define a function to handle database errors
    function handleDatabaseError($message)
    {
        // You can customize this function to log errors, display a message, or perform any other actions as needed
        echo "Database Error: " . $message;
        exit(); // Terminate the script execution
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" type="text/css">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
</head>

<style>
    .question-text {
        font-size: 30px;
        /* Adjust the font size as needed */
        font-weight: bold;
        margin-bottom: 10px;
        padding-left: 20px;




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

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8" style="background-color: #7BD3EA; border-radius: 10px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); border: 2px solid rgba(0, 0, 0, 0.1);">
                    <br><br>
                    <h1 id="quiz-title" style="font-size: 35px;" class="text-center mb-4">
                        <img height="35" src="./icons/quiz.gif" alt="" style="border-radius: 50%; border: 2px solid #000;">
                        Quiz
                    </h1>
                    <br><br>
                    <?php if (!empty($questions)) : ?>

                        <form style="font-size: 20px;" id="quiz-form" method="post">
                            <?php $counter = 0; ?>
                            <?php foreach ($questions as $key => $question) : ?>
                                <div class="question-box <?php echo $counter === 0 ? '' : 'd-none'; ?>">
                                    <div class="question-text"><?php echo $counter + 1 . ". " . htmlspecialchars($question['question_text']); ?></div>
                                    <?php
                                    // Shuffle the options
                                    $shuffledOptions = ['A' => $question['question_A'], 'B' => $question['question_B'], 'C' => $question['question_C'], 'D' => $question['question_D']];
                                    shuffle($shuffledOptions);
                                    ?>
                                    <?php foreach ($shuffledOptions as $option => $optionText) : ?>
                                        <div style="padding-left: 100px;" class="form-check">
                                            <input class="form-check-input" type="radio" name="answer_<?php echo $question['question_id']; ?>" id="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>" value="<?php echo htmlspecialchars($optionText); ?>">
                                            <label class="form-check-label" for="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>"><?php echo htmlspecialchars($optionText); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $counter++; ?>
                            <?php endforeach; ?>
                            <br><br>
                            <div class="container" style="background-color: #7BD3EA;">
                                <div class="row">
                                    <div class="col text-start">
                                        <button style="font-size: 20px;" id="back-btn" class="text-start btn btn-primary <?php echo $counter === 0 ? 'd-none' : ''; ?>" type="button">◁ Back</button>
                                    </div>
                                    <div class="col text-end">
                                        <button style="font-size: 20px;" id="submit-btn" type="submit" class="text-end btn btn btn-primary">Submit</button>
                                        <button style="font-size: 20px;" id="next-btn" class="text-end btn btn-primary <?php echo $counter === count($questions) - 1 ? 'd-none' : ''; ?>" type="button">Next ▷</button>
                                    </div>
                                </div>
                            </div>
                        </form>






                    <?php else : ?>
                        <p style="padding-left: 50px; font-size: 20px">No Quiz found.</p>
                    <?php endif; ?>
                    <br><br>
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