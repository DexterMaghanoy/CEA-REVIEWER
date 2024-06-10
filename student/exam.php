<?php
session_start();

require_once "../api/db-connect.php"; // Adjust the path as needed

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];

    // Prepare SQL query to fetch courses for the given program
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->execute();
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    header("Location: ../index.php");
    exit();
}

$questions = [];


if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];

    // Loop through each course and fetch up to 6 questions per course
    foreach ($courses as $course) {
        $course_id = $course['course_id'];
        // Modify the SQL query to select up to 6 questions related to the course_id
        $sql = "SELECT q.*, m.module_id 
                FROM tbl_question q 
                INNER JOIN tbl_course c ON q.course_id = c.course_id 
                INNER JOIN tbl_module m ON q.module_id = m.module_id 
                WHERE c.program_id = :program_id 
                AND q.course_id = :course_id 
                AND c.course_status = 1
                AND m.module_status = 1
                ORDER BY RAND() LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $courseQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add these questions to the overall questions array
        $questions = array_merge($questions, $courseQuestions);
    }

    // Shuffle the questions array if needed
    shuffle($questions);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['stud_id'])) {
        $stud_id = $_SESSION['stud_id'];

        // Retrieve submitted answers
        $answers = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'answer_') === 0) {
                $question_id = substr($key, strlen('answer_'));
                $answers[$question_id] = htmlspecialchars($value);
            }
        }

        // Calculate the score
        $score = 0;
        $total_questions = count($answers);
        $quiz_type = 3;

        // Fetch the max attempt_id for the current student and quiz type
        $sqlMaxAttempt = "SELECT COALESCE(MAX(attempt_id), 0) AS max_attempt 
                          FROM tbl_quiz_answers 
                          WHERE student_id = :student_id 
                          AND quiz_type = :quiz_type";
        $stmtMaxAttempt = $conn->prepare($sqlMaxAttempt);
        $stmtMaxAttempt->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
        $stmtMaxAttempt->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
        $stmtMaxAttempt->execute();
        $max_attempt_row = $stmtMaxAttempt->fetch(PDO::FETCH_ASSOC);
        $new_attempt_id = $max_attempt_row['max_attempt'] + 1;

        // Debugging: Log the new attempt_id
        error_log("New attempt_id: $new_attempt_id");

        foreach ($answers as $question_id => $answer) {
            // Fetch the course_id and module_id associated with the question_id
            $sqlFetchIds = "SELECT course_id, module_id FROM tbl_question WHERE question_id = :question_id";
            $stmtFetchIds = $conn->prepare($sqlFetchIds);
            $stmtFetchIds->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtFetchIds->execute();
            $rowIds = $stmtFetchIds->fetch(PDO::FETCH_ASSOC);
            $course_id = $rowIds['course_id'];
            $module_id = $rowIds['module_id'];

            // Fetch the correct answer for the question
            $sqlCorrectAnswer = "SELECT question_answer FROM tbl_question WHERE question_id = :question_id";
            $stmtCorrectAnswer = $conn->prepare($sqlCorrectAnswer);
            $stmtCorrectAnswer->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtCorrectAnswer->execute();
            $correct_answer = $stmtCorrectAnswer->fetchColumn();

            $answer_status = ($answer === $correct_answer) ? 1 : 0;
            $score += $answer_status;

            // Insert the answer into tbl_quiz_answers
            $sqlInsertAnswer = "INSERT INTO tbl_quiz_answers (module_id, student_id, question_id, chosen_answer, attempt_id, quiz_type, course_id, answer_status) 
                                VALUES (:module_id, :student_id, :question_id, :chosen_answer, :attempt_id, :quiz_type, :course_id, :answer_status)";
            $stmtInsertAnswer = $conn->prepare($sqlInsertAnswer);
            $stmtInsertAnswer->bindParam(":module_id", $module_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":student_id", $stud_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":question_id", $question_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":chosen_answer", $answer, PDO::PARAM_STR);
            $stmtInsertAnswer->bindParam(":attempt_id", $new_attempt_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":course_id", $course_id, PDO::PARAM_INT);
            $stmtInsertAnswer->bindParam(":answer_status", $answer_status, PDO::PARAM_INT);

            // Debugging: Log each insert statement
            error_log("Inserting answer for question_id: $question_id, chosen_answer: $answer, correct_answer: $correct_answer, answer_status: $answer_status");

            $stmtInsertAnswer->execute();
        }


        $sql = "SELECT pass_rate FROM tbl_passrate ORDER BY pass_id DESC LIMIT 1";
        $stmtPass_rate = $conn->prepare($sql);
        $stmtPass_rate->execute();
        $passRate = $stmtPass_rate->fetchColumn();

        $passingScore = $passRate / 100;

        $passStatus = ($score / $total_questions) >= $passingScore ? 1 : 0;

        // Insert the result into tbl_result
        $sql = "INSERT INTO tbl_result (course_id, program_id, module_id, stud_id, result_score, total_questions, quiz_type, result_status, attempt_id) 
                VALUES (:course_id, :program_id, :module_id, :stud_id, :result_score, :total_questions, :quiz_type, :result_status, :attempt_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        $stmt->bindParam(":program_id", $program_id, PDO::PARAM_INT);
        $stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
        $stmt->bindParam(":stud_id", $stud_id, PDO::PARAM_INT);
        $stmt->bindParam(":result_score", $score, PDO::PARAM_INT);
        $stmt->bindParam(":total_questions", $total_questions, PDO::PARAM_INT);
        $stmt->bindParam(":quiz_type", $quiz_type, PDO::PARAM_INT);
        $stmt->bindParam(":result_status", $passStatus, PDO::PARAM_INT);
        $stmt->bindParam(":attempt_id", $new_attempt_id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect to dashboard with success message
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
        function handleDatabaseError($message)
        {
            echo "Database Error: " . $message;
            exit();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Long Quiz</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" type="text/css">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
</head>
<style>
    .question-text {
        font-size: 30px;
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
    }
</style>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8" style="background-color: #F4D19B; border-radius: 10px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2); border: 2px solid rgba(0, 0, 0, 0.1);">
                    <br><br>
                    <h1 style="font-size: 35px;" id="quiz-title" class="text-center mb-4">
                        <img height="35" src="./icons/exam.gif" alt="" style="border-radius: 50%; border: 2px solid #000;">
                        EXAM
                    </h1>
                    <br><br>
                    <?php if (!empty($questions)) : ?>
                        <form style="font-size: 20px;" id="quiz-form" method="post">
                            <?php $counter = 0; ?>
                            <?php foreach ($questions as $key => $question) : ?>
                                <div class="question-box <?php echo $counter === 0 ? '' : 'd-none'; ?>">
                                    <div class="question-text"><?php echo $counter + 1 . ". " . htmlspecialchars($question['question_text']); ?></div>
                                    <?php
                                    // Create an array of options
                                    $options = ['A' => $question['question_A'], 'B' => $question['question_B'], 'C' => $question['question_C'], 'D' => $question['question_D']];

                                    // Shuffle the options
                                    shuffle($options);
                                    ?>
                                    <?php foreach ($options as $option => $optionText) : ?>
                                        <div style="padding-left: 100px;" class="form-check">
                                            <input class="form-check-input" type="radio" name="answer_<?php echo $question['question_id']; ?>" id="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>" value="<?php echo htmlspecialchars($optionText); ?>">
                                            <label class="form-check-label" for="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>"><?php echo htmlspecialchars($optionText); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $counter++; ?>
                            <?php endforeach; ?>
                            <br><br>
                            <div class="container" style="background-color: #F4D19B;">
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
                        <p>No questions found.</p>
                    <?php endif; ?>
                    <br><br>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>