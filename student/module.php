<?php
require("../api/db-connect.php");
session_start();

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
}

global $quizstatus;
global $qs;
global $NA_counter;
global $percentage;
global $questionCount;

$sql = "SELECT tbl_course.program_id, tbl_course.course_id, tbl_course.course_name 
        FROM tbl_course 
        INNER JOIN tbl_program ON tbl_course.program_id = tbl_program.program_id
        WHERE tbl_course.user_id = :user_id and tbl_course.course_status = 1";

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id and course_status = 1";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id);
    $result->execute();


    // Fetch module_id based on course_id
    $moduleSql = "SELECT module_id FROM tbl_module WHERE course_id = :course_id and module_status = 1";
    $moduleStmt = $conn->prepare($moduleSql);
    $moduleStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);

    $moduleStmt->execute();

    // Array to store questions
    $all_questions = [];

    // Check if there are rows returned
    if ($moduleStmt->rowCount() > 0) {
        // Loop through each module
        while ($moduleRow = $moduleStmt->fetch(PDO::FETCH_ASSOC)) {
            $module_id = $moduleRow['module_id'];

            // Fetch questions for this module
            $questionSql = "SELECT * FROM tbl_question WHERE module_id = :module_id";
            $questionStmt = $conn->prepare($questionSql);
            $questionStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
            $questionStmt->execute();

            // Check if there are questions for this module
            if ($questionStmt->rowCount() > 0) {
                // Fetch questions and add them to the array
                $module_questions = $questionStmt->fetchAll(PDO::FETCH_ASSOC);
                $all_questions = array_merge($all_questions, $module_questions);
            }
        }
    }
    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    // Build the SQL query for module retrieval
    $recordsPerPage = 5;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $recordsPerPage;

    $sql = "SELECT m.*, c.course_name 
    FROM tbl_module AS m
    INNER JOIN tbl_course AS c ON m.course_id = c.course_id 
    WHERE m.course_id = :course_id 
    AND c.program_id = (
        SELECT program_id 
        FROM tbl_course 
        WHERE course_id = :course_id
    )
    AND m.module_status = 1";

    $result = $conn->prepare($sql);
    $result->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $result->execute();


    // Count total number of records
    $countSql = "SELECT COUNT(*) as total FROM tbl_module WHERE course_id = :course_id  AND module_status = 1";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCount / $recordsPerPage);
} else {
    header("Location: ../index.php");
    exit();
}
// SQL query with proper indentation

// Fetch the latest pass rate
$passRateSql = "SELECT pass_rate FROM tbl_passrate ORDER BY created_at DESC LIMIT 1";
$passRateStmt = $conn->prepare($passRateSql);
$passRateStmt->execute();
$passRateData = $passRateStmt->fetch(PDO::FETCH_ASSOC);
$passRate = $passRateData['pass_rate'] ?? 0; // Fallback to 0 if no rate is found



?>

<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <link rel="stylesheet" href="mobile-desktop.css" type="text/css">


</head>




<body>

    <div id="topBar">

        <?php
        include 'topNavBar.php';
        ?>

    </div>
    <div class="wrapper">
        <?php
        include "sidebar.php";
        ?>
        <!-- <div class="main p-3"> -->
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-12">

                    <div class="text-center mt-4 mb-4">
                        <h1>Subject: <?php
                                        $sql = "SELECT c.course_name
                                        FROM tbl_course AS c
                                        WHERE c.course_id = :course_id
                                        AND c.program_id = (
                                        SELECT program_id
                                        FROM tbl_course
                                        WHERE course_id = :course_id)";

                                        $stmtModule = $conn->prepare($sql);
                                        $stmtModule->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                                        $stmtModule->execute();
                                        $Module = $stmtModule->fetch(PDO::FETCH_ASSOC);

                                        if ($Module !== false) {
                                            echo $Module['course_name'];
                                        } else {
                                            echo "Unknown";
                                        }
                                        ?></h1>

                    </div>
                    <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                        <caption>List of Modules</caption>
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">No.</th>
                                <th scope="col">Module Title</th>
                                <th scope="col">Test & Answer🔑</th>
                                <th scope="col">Attempts</th>
                                <th scope="col">Remarks</th>
                                <th scope="col">Pass Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1; // Initialize counter variable outside the loop
                            if ($result->rowCount() > 0) :
                                foreach ($result as $row) :
                            ?>
                                    <tr>
                                        <td><?php echo $counter; ?></td>
                                        <td>

                                            <a href="#" class="view-module-btn" data-bs-toggle="modal" data-bs-target="#moduleModal" data-module-id="<?php echo $row['module_id']; ?>" data-module-name="<?php echo $row['module_name']; ?>"><?php echo $row['module_name']; ?> 🔎</a>

                                            </a>
                                        </td>

                                        <td>


                                            <?php
                                            // Check if the module has questions available
                                            $sql = "SELECT COUNT(*) AS question_count FROM tbl_question WHERE module_id = :module_id";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->bindParam(":module_id", $row['module_id'], PDO::PARAM_INT);
                                            $stmt->execute();
                                            $questionCount = $stmt->fetch(PDO::FETCH_ASSOC)['question_count'];

                                            // Initialize a variable to track if all buttons are disabled
                                            $allButtonsDisabled = true;

                                            // Check if the quiz has been completed by the user
                                            $sql = "SELECT COUNT(*) AS count FROM tbl_result WHERE module_id = :module_id AND stud_id = :stud_id AND quiz_type = 1";

                                            $stmt = $conn->prepare($sql);
                                            $stmt->bindParam(":module_id", $row['module_id'], PDO::PARAM_INT);
                                            $stmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                            $stmt->execute();
                                            $resultCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                                            // Retrieve the user's score for the module if attempted
                                            if ($resultCount > 0) {
                                                // Fetch the user's latest result
                                                $sql = "SELECT tbl_result.result_score, COUNT(tbl_question.question_id) AS total_questions
    FROM tbl_result 
    LEFT JOIN tbl_question ON tbl_result.module_id = tbl_question.module_id
    WHERE tbl_result.result_id = (
        SELECT MAX(result_id) 
        FROM tbl_result 
        WHERE module_id = :module_id 
        AND stud_id = :stud_id
        AND quiz_type = 1
    )";

                                                $stmt = $conn->prepare($sql);
                                                $stmt->bindParam(":module_id", $row['module_id'], PDO::PARAM_INT);
                                                $stmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                                $stmt->execute();
                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                                // Calculate pass rate if result exists
                                                if ($result) {
                                                    $resultScore = $result['result_score'];
                                                    $totalQuestions = $result['total_questions'];

                                                    if ($totalQuestions > 0) {
                                                        $percentage = ($resultScore / $totalQuestions) * 100;

                                                        // Display appropriate action buttons based on percentage
                                                        if ($percentage >= $passRate) {
                                                            echo '<button class="btn btn-success btn-sm" disabled><i style = "color: black;" class="lni lni-remove-file"></i></button>';
                                                            echo '<button class="btn btn-warning btn-sm eye-icon-btn" style="border: 1px solid black; box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);" onclick="window.location.href=\'question-answers.php?module_id=' . $row['module_id'] . '\'"><i class="lni lni-key"></i></button>';
                                                        } else {
                                                            // Check if questions are available for retake
                                                            if ($questionCount > 0) {
                                                                echo '<button class="btn btn-success btn-sm" style="border: 1px solid black; box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '&course_id=' . $course_id . '\'"><i style="color: black;" class="lni lni-remove-file"></i></button>';

                                                                echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-key"></i></button>';
                                                                $allButtonsDisabled = false;
                                                            } else {
                                                                echo '<button class="btn btn-success btn-sm" style="border: 1px solid black;" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '&course_id=' . $course_id . '\'"><i style="color: black; border: 1px solid black; padding: 2px;" class="lni lni-remove-file"></i></button>';
                                                                echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-key"></i></i></button>';
                                                            }
                                                        }
                                                    } else {
                                                        echo "No questions available";
                                                    }
                                                } else {
                                                    echo "No result found";
                                                }
                                            } else {
                                                // Check if questions are available for first attempt
                                                if ($questionCount > 0) {
                                                    echo '<button class="btn btn-success btn-sm" style="border: 1px solid black; box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '&course_id=' . $course_id . '\'"><i style="color: black;" class="lni lni-remove-file"></i></button>';
                                                    echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-key"></i></i></button>';
                                                    $allButtonsDisabled = false;
                                                } else {
                                                    echo '<button class="btn btn-success btn-sm" disabled><i style = "color: black;" class="lni lni-remove-file"></i></button>';
                                                    echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-key"></i></i></button>';
                                                }
                                            }

                                            // Check if all buttons are disabled and trigger an alert
                                            if ($allButtonsDisabled) {
                                                // echo '<script>alert("Hello World!");</script>';

                                            }
                                            ?>
                                        </td>

                                        <td><?php echo $resultCount;

                                            ?>
                                        </td>
                                        <td>



                                            <?php


                                            // $passRate = 0;

                                            // Calculate and display pass rate
                                            if ($resultCount > 0 && $percentage >= $passRate) {
                                                if ($totalQuestions > 0) {
                                                    echo '<a href="student_question_result.php?course_id=' . $course_id . '&module_id=' . $row['module_id'] . '&stud_id=' . $stud_id . '" style="color: green;"><strong>Passed 👁</strong></a>';
                                                } else {
                                                    echo '<span style="color: grey;"><strong>n/a</strong></span>';

                                                    $qs = -1;
                                                }
                                            } else {
                                                if ($questionCount <= 0) {

                                                    echo 'No Questions';
                                                } else {

                                                    if ($resultCount > 0) {
                                                        echo '<a href="student_question_result.php?course_id=' . $course_id . '&module_id=' . $row['module_id'] . '&stud_id=' . $stud_id . '" style="color: red;"><strong>Failed 👁</strong></a>';

                                                        $qs = -1;
                                                    } else {
                                                        echo '<span style="color: grey;"><strong>n/a</strong></span>';
                                                        $qs = -1;
                                                    }
                                                }
                                            }


                                            // echo $passRate;
                                            ?>

                                        </td>
                                        <td>
                                            <?php

                                            // Calculate and display pass rate
                                            if ($resultCount > 0 && $percentage >= $passRate) {
                                                if ($totalQuestions > 0) {
                                                    // Calculate pass rate based on attempt number
                                                    $passRate = (1 / $resultCount) * 100;
                                                    echo number_format($passRate, 1) . '%';
                                                } else {
                                                    echo '<span style="color: grey;"><strong>n/a</strong></span>';
                                                }
                                            } else {
                                                if ($questionCount <= 0) {
                                                    echo 'No questions';
                                                } else {
                                                    echo '<span style="color: grey;"><strong>n/a</strong></span>';
                                                }
                                            }

                                            ?>
                                        </td>





                                    </tr>


                                <?php
                                    $counter++; // Increment the counter
                                endforeach;
                            else :
                                $qs = -1;

                                ?>
                                <tr>
                                    <td colspan="6">No modules Found</td>
                                </tr>
                            <?php
                            endif;
                            ?>



                            <?php

                            // Display quiz button based on availability of questions
                            if ($qs >= 0) {
                                $quiz_button = "";
                            } else {
                                $quiz_button = "disabled";
                            }
                            ?>


                            <tr>
                                <td>#</td>
                                <td><b>Quiz</b></td>
                                <td>


                                    <?php

                                    $course_id = filter_var($_GET['course_id'], FILTER_SANITIZE_NUMBER_INT);

                                    $sql = "SELECT COUNT(*) AS quiz_result_count FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND quiz_type = 2";
                                    $qstmt = $conn->prepare($sql);
                                    $qstmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                                    $qstmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                    $qstmt->execute();
                                    $qresult = $qstmt->fetch(PDO::FETCH_ASSOC);

                                    if ($qstmt->errorCode() !== '00000') {
                                        echo "Error: " . $qstmt->errorInfo()[2];
                                    } else {
                                        $quizAttempts = $qresult['quiz_result_count'];

                                        // Check if the user has already passed the quiz
                                        $sql_passed = "SELECT COUNT(*) AS passed_count FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND quiz_type = 2";
                                        $stmt_passed = $conn->prepare($sql_passed);
                                        $stmt_passed->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                                        $stmt_passed->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                        $stmt_passed->execute();
                                        $passed_result = $stmt_passed->fetch(PDO::FETCH_ASSOC);

                                        if ($passed_result['passed_count'] > 0) {
                                            // User has already passed the quiz, disable the button

                                            echo '<button class="btn btn-info mb-2" disabled><i style = "color: black;" class="lni lni-remove-file"></i></button>';
                                            $quiz_result_final = '<a href="student_quiz_result.php?course_id=' . $course_id . '&stud_id=' . $stud_id . '" style="color: green;"><strong>Passed 👁</strong></a>';
                                        } else {
                                            // User hasn't passed the quiz, enable the button
                                            echo '<button onclick="window.location.href=\'quiz.php?course_id=' . $course_id . '\'" class="btn btn-info mb-2" ' . $quiz_button . '><i style = "color: black;" class="lni lni-remove-file"></i></button>';

                                            $quiz_result_final = 'echo \'<a href="student_quiz_result.php?course_id=' . $course_id . '&stud_id=' . $stud_id . '" style="color: red;"><strong>Failed 👁</strong></a>\';';

                                            // $quiz_result_final = "";

                                        }
                                    }
                                    ?>
                                </td>
                                <td>

                                    <?php

                                    $course_id = filter_var($_GET['course_id'], FILTER_SANITIZE_NUMBER_INT);

                                    $sql = "SELECT COUNT(*) AS quiz_result_count FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND quiz_type = 2";
                                    $qstmt = $conn->prepare($sql);
                                    $qstmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                                    $qstmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                    $qstmt->execute();
                                    $qresult = $qstmt->fetch(PDO::FETCH_ASSOC);

                                    if ($qstmt->errorCode() !== '00000') {
                                        echo "Error: " . $qstmt->errorInfo()[2];
                                    } else {
                                        $quizAttempts = $qresult['quiz_result_count'];
                                        echo $quizAttempts;
                                    }
                                    ?>

                                </td>
                                <td>
                                    <?php
                                    // echo $passed_result['passed_count'];

                                    global $QuizResultCount;
                                    global $QuizResult;

                                    // Temporary values
                                    $result_score = 0;
                                    $total_questions = 0;

                                    // Execute SQL query to fetch data
                                    $sql = "SELECT stud_id, course_id, result_score, total_questions
            FROM tbl_result
            WHERE quiz_type = 2 AND stud_id = :stud_id AND course_id = :course_id
            ORDER BY result_id DESC
            LIMIT 1";

                                    $stmt = $conn->prepare($sql);
                                    $stmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $QuizResult = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($QuizResult !== false) {
                                        // Assign fetched values to variables
                                        $stud_id = $QuizResult['stud_id'];
                                        $course_id = $QuizResult['course_id'];
                                        $result_score = $QuizResult['result_score'];
                                        $total_questions = $QuizResult['total_questions'];

                                        // ✅ Define the pass rate

                                        // Fetch the latest pass rate
                                        $passRateSql = "SELECT pass_rate FROM tbl_passrate ORDER BY created_at DESC LIMIT 1";
                                        $passRateStmt = $conn->prepare($passRateSql);
                                        $passRateStmt->execute();
                                        $passRateData = $passRateStmt->fetch(PDO::FETCH_ASSOC);
                                        $passRate = $passRateData['pass_rate'] ?? 0; // Fallback to 0 if no rate is found




                                        // Check if passed
                                        if ($quizAttempts > 0 && $result_score > 0 && ($result_score / $total_questions) * 100 >= $passRate) {
                                            echo $quiz_result_final;
                                        } else {
                                            if ($quizAttempts > 0) {
                                                echo '<a href="student_quiz_result.php?course_id=' . $course_id . '&stud_id=' . $stud_id . '" style="color: red;"><strong>Failed 👁</strong></a>';
                                            } else {
                                                echo '<span style="color: grey;"><strong>n/a</strong></span>';
                                            }
                                        }
                                    } else {
                                        // Handle the case where no result is found
                                        echo '<span style="color: grey;"><strong>n/a</strong></span>';
                                    }
                                    ?>
                                </td>


                                <td>
                                    <?php

                                    if ($quizAttempts > 0) {
                                        if ($result_score > 0 && ($result_score / $total_questions) * 100 >= $passRate) {
                                            // Calculate and display the quiz percentage
                                            $QuizPercentage = 100 / $quizAttempts;
                                            echo number_format($QuizPercentage) . '%';
                                        } else {
                                            echo '0.0%';
                                        }
                                    } else {
                                        echo '<span style="color: grey;"><strong>n/a</strong></span>';
                                    }
                                    ?>
                                </td>

                            </tr>

                        </tbody>
                    </table>


                </div>
            </div>
        </div>
        <!-- </div> -->
    </div>

    <!-- Module Modal -->
    <div class="modal fade" id="moduleModal" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <style>
                    .modal-header {
                        background-color: #007bff;
                        color: #fff;
                        padding: 5px;
                    }
                </style>

                <div class="modal-header">
                    <h5 class="modal-title" id="moduleModalLabel">Module: <span id="dynamicModuleName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="moduleIframe" style="width: 100%; height: 80vh;" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>




</body>

</html>





<script>
    const viewModuleButtons = document.querySelectorAll('.view-module-btn');
    const actionTestButtons = document.querySelectorAll('.action-test-btn');
    const moduleIframe = document.getElementById('moduleIframe');
    const dynamicModuleName = document.getElementById('dynamicModuleName'); // Added line

    // Function to handle opening modal with module content
    function openModuleModal(moduleId, moduleName) {
        moduleIframe.src = `pdf_viewer.php?module_id=${moduleId}`;
        dynamicModuleName.textContent = moduleName; // Added line to update module name in modal header
        $('#moduleModal').modal('show'); // Trigger modal manually using jQuery
    }

    viewModuleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const moduleId = this.getAttribute('data-module-id');
            const moduleName = this.getAttribute('data-module-name'); // Added line to get module name
            openModuleModal(moduleId, moduleName);
        });
    });

    // Handle action test button clicks
    actionTestButtons.forEach(button => {
        button.addEventListener('click', function() {
            const moduleId = this.getAttribute('data-module-id');
            const targetPage = this.getAttribute('data-bs-target');
            if (targetPage) {
                window.location.href = targetPage + `?module_id=${moduleId}`;
            }
        });
    });

    // Optional: JavaScript for toggling the sidebar
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>