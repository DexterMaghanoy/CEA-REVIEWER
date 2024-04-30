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

// Fetching questions and correct answers from the database
$module_id = sanitizeInput($_GET['module_id']);

$sql = "SELECT q.*, qa.chosen_answer AS user_answer, q.question_answer AS correct_answer, qa.attempt_id
        FROM tbl_question q 
        LEFT JOIN tbl_quiz_answers qa ON q.question_id = qa.question_id
        WHERE q.module_id = :module_id AND qa.student_id = :student_id
        ORDER BY qa.attempt_id"; // Order by attempt_id to group by attempts

$stmt = $conn->prepare($sql);
$stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
$stmt->bindParam(":student_id", $_SESSION['stud_id'], PDO::PARAM_INT);
if (!$stmt->execute()) {
    handleDatabaseError("Failed to fetch questions from the database.");
}
$questions_and_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group questions and answers by attempt_id
$grouped_questions_and_answers = [];
foreach ($questions_and_answers as $item) {
    $attempt_id = $item['attempt_id'];
    $grouped_questions_and_answers[$attempt_id][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Answers</title>
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
        .correct-answer {
            color: green;
        }

        .wrong-answer {
            color: red;
        }

        .correct-indicator::after {
            content: " (✔)";
            color: green;
            font-weight: bold;
        }

        .attempt-separator {
            margin-top: 20px;
            border-top: 2px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <main id="content">
            <div class="mb-3">
                <h1 id="quiz-title">Answer Key</h1>
            </div>
            <?php if (!empty($grouped_questions_and_answers)) : ?>
                <?php $attemptCounter = 1; ?>
                <?php foreach ($grouped_questions_and_answers as $questions_and_answers) : ?>
                    <div class="attempt-separator">
                    <br>
                        <h2>Attempt <?php echo $attemptCounter++; ?></h2>
                        <br>
                    </div>
                    <?php foreach ($questions_and_answers as $question) : ?>
                        <div class="question-box <?php echo ($question['user_answer'] === $question['correct_answer']) ? 'correct-answer' : 'wrong-answer'; ?>">
                            <div class="question-text"><?php echo sanitizeInput($question['question_text']); ?></div>
                            <?php foreach (['A', 'B', 'C', 'D'] as $option) : ?>
                                <?php $optionKey = 'question_' . $option; ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answer_<?php echo $question['question_id']; ?>" id="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>" value="<?php echo sanitizeInput($question[$optionKey]); ?>" <?php echo ($question['user_answer'] === $question[$optionKey]) ? 'checked' : ''; ?> disabled>
                                    <label class="form-check-label <?php echo ($question[$optionKey] === $question['correct_answer']) ? 'correct-indicator' : ''; ?>" for="option<?php echo $option; ?>_<?php echo $question['question_id']; ?>"><?php echo sanitizeInput($question[$optionKey]); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No questions found.</p>
            <?php endif; ?>
        </main>
        <!-- Include Bootstrap 5 JS and Popper.js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    </div>
</body>


</html>


<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>