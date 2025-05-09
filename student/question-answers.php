<?php
require_once "../api/db-connect.php";
session_start();


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
$student_id = $_SESSION['stud_id'];

$sql = "SELECT q.*, qa.chosen_answer AS user_answer, q.question_answer AS correct_answer, qa.attempt_id, m.module_name
        FROM tbl_question q 
        LEFT JOIN tbl_quiz_answers qa ON q.question_id = qa.question_id
        LEFT JOIN tbl_module m ON q.module_id = m.module_id
        WHERE q.module_id = :module_id AND qa.student_id = :student_id AND quiz_type = 1
        ORDER BY qa.attempt_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":module_id", $module_id, PDO::PARAM_INT);
$stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
if (!$stmt->execute()) {
    handleDatabaseError("Failed to fetch questions from the database.");
}
$questions_and_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_questions_and_answers = [];
foreach ($questions_and_answers as $item) {
    $attempt_id = $item['attempt_id'];
    $grouped_questions_and_answers[$attempt_id][] = $item;
}

// Extract the module name from the first record (assuming all records have the same module name)
$module_name = isset($questions_and_answers[0]) ? $questions_and_answers[0]['module_name'] : 'Unknown Module';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Answers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">

    <link rel="stylesheet" href="mobile-desktop.css" type="text/css">
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

        .attempt-table td {
            cursor: pointer;
        }

        .attempt-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to bottom right, #e0f7fa, #80deea);
            border-radius: 10px;
            overflow: hidden;
        }

        .attempt-row {
            background-color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.3s ease;
        }

        .attempt-row:hover {
            background-color: rgba(255, 255, 255, 1);
        }

        .attempt-cell {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .attempt-cell span {
            float: right;
        }

        .attempt-cell span.red {
            color: red;
            font-size: 30px;
        }

        .attempt-cell span.green {
            color: green;
            font-size: 25px;
        }
    </style>

</head>

<body>
    <div class="mt-5" id="topBar">

        <?php
        include 'topNavBar.php';
        ?>
    </div>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>

        <div class="container">
            <div class="mb-5 mt-5">
                <center>
                    <h1 id="quiz-title">Answer Key: <?php echo $module_name ?></h1>
                </center>
            </div>
            <div class="row">
                <div class="col-sm-3"></div>
                <div class="col-sm">
                    <?php if (!empty($grouped_questions_and_answers)) : ?>
                        <table style="text-align: center;" class="table table-hover attempt-table">
                            <tbody>
                                <?php $attemptCounter = 1; ?>
                                <?php $totalAttempts = count($grouped_questions_and_answers); ?>
                                <?php foreach ($grouped_questions_and_answers as $questions_and_answers) : ?>
                                    <tr class="attempt-row" data-toggle="modal" data-target="#attemptModal<?php echo $attemptCounter; ?>" style="border: 1px solid #dee2e6; padding: 5px;">
                                        <td class="attempt-cell">
                                            Attempt <?php echo $attemptCounter; ?>
                                            <?php if ($attemptCounter !== $totalAttempts) : ?>
                                                <span style="color: red; font-size: 30px; float: right;">×</span>
                                            <?php else : ?>
                                                <span style="color: green; font-size: 25px; float: right;">✔</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $attemptCounter++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Modal -->
                        <?php $attemptCounter = 1; ?>
                        <?php foreach ($grouped_questions_and_answers as $questions_and_answers) : ?>


                            <div class="modal fade" id="attemptModal<?php echo $attemptCounter; ?>" tabindex="-1" role="dialog" aria-labelledby="attemptModalLabel<?php echo $attemptCounter; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><?php echo $module_name ?>: Attempt <?php echo $attemptCounter++; ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" style="max-height: 530px; overflow-y: auto;">
                                            <div class="modal-body-content">
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
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeButton">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>



                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No questions found.</p>
                    <?php endif; ?>



                </div>
                <div class="col-sm-3"></div>
            </div>
        </div>
    </div>
</body>

</html>

<!-- Include Bootstrap 5 JS and Popper.js -->
<script>
    // Trigger modal on row click
    document.querySelectorAll('.attempt-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var target = this.getAttribute('data-target');
            if (target) {
                var modal = document.querySelector(target);
                if (modal) {
                    var modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                }
            }
        });
    });

    // Add event listener for clicking the "Close" button inside each modal
    document.querySelectorAll('.modal-footer .btn-secondary').forEach(function(button) {
        button.addEventListener('click', function() {
            var modal = this.closest('.modal');
            if (modal) {
                var modalInstance = new bootstrap.Modal(modal);
                modalInstance.hide();
            }
        });
    });

    const hamBurger = document.querySelector(".toggle-btn");
    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>