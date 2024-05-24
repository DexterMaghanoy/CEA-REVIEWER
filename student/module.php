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
        WHERE tbl_course.user_id = :user_id";


if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id);
    $result->execute();


    // Fetch module_id based on course_id
    $moduleSql = "SELECT module_id FROM tbl_module WHERE course_id = :course_id";
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
    $countSql = "SELECT COUNT(*) as total FROM tbl_module WHERE course_id = :course_id";
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


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php include "sidebar.php"; ?>
        <!-- <div class="main p-3"> -->
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-12">
                    <div class="text-center mb-4">
                        <h1>Subject: <?php


                                        $sql = "
    SELECT c.course_name, c.course_id
    FROM tbl_course AS c
    WHERE c.course_id = :course_id
      AND c.program_id = (
          SELECT program_id
          FROM tbl_course
          WHERE course_id = :course_id
      );
";

                                        $stmtModule = $conn->prepare($sql);
                                        $stmtModule->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                                        $stmtModule->execute();
                                        $Module = $stmtModule->fetch(PDO::FETCH_ASSOC);
                                        if ($row !== false) {
                                            $Module = $row['course_name'];
                                        } else {
                                            $Module = "Unknown";
                                        }
                                        echo  $Module;
                                        ?></h1>
                    </div>
                    <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                        <caption>List of Modules</caption>
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">No.</th>
                                <th scope="col">Module Title</th>
                                <th scope="col">Action</th>
                                <th scope="col">Attempts</th>
                                <th scope="col">Result</th>
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
                                            <a href="#" class="view-module-btn" data-bs-toggle="modal" data-bs-target="#moduleModal" data-module-id="<?php echo $row['module_id']; ?>"><?php echo $row['module_name']; ?></a>
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
                                                        if ($percentage >= 50) {
                                                            echo '<button class="btn btn-success btn-sm" disabled><i class="lni lni-invention"></i></button>';
                                                            echo '<button class="btn btn-warning btn-sm eye-icon-btn" onclick="window.location.href=\'question-answers.php?module_id=' . $row['module_id'] . '\'"><i class="lni lni-eye eye-icon text-white"></i></button>';
                                                        } else {
                                                            // Check if questions are available for retake
                                                            if ($questionCount > 0) {
                                                                echo '<button class="btn btn-success btn-sm" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '&course_id=' . $course_id . '\'"><i class="lni lni-invention"></i></button>';
                                                                echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-eye eye-icon text-white"></i></button>';
                                                                $allButtonsDisabled = false;
                                                            } else {
                                                                echo '<button class="btn btn-success btn-sm" disabled><i class="lni lni-invention"></i></button>';
                                                                echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-eye eye-icon text-white"></i></button>';
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
                                                    echo '<button class="btn btn-success btn-sm" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '&course_id=' . $course_id . '\'"><i class="lni lni-invention"></i></button>';
                                                    echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-eye eye-icon text-white"></i></button>';
                                                    $allButtonsDisabled = false;
                                                } else {
                                                    echo '<button class="btn btn-success btn-sm" disabled><i class="lni lni-invention"></i></button>';
                                                    echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-eye eye-icon text-white"></i></button>';
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
                                            $passRate = 0;

                                            // Calculate and display pass rate
                                            if ($resultCount > 0 && $percentage >= 50) {
                                                if ($totalQuestions > 0) {
                                                    echo 'Pass';
                                                } else {
                                                    echo 'N/A ';
                                                    $qs = -1;
                                                }
                                            } else {
                                                if ($questionCount <= 0) {

                                                    echo 'No Questions';
                                                } else {

                                                    if ($resultCount > 0) {
                                                        echo 'Failed';
                                                        $qs = -1;
                                                    } else {
                                                        echo 'N/A';
                                                        $qs = -1;
                                                    }
                                                }
                                            }



                                            ?>

                                        </td>
                                        <td>
                                            <?php

                                            // Calculate and display pass rate
                                            if ($resultCount > 0 && $percentage >= 50) {
                                                if ($totalQuestions > 0) {
                                                    // Calculate pass rate based on attempt number
                                                    $passRate = (1 / $resultCount) * 100;
                                                    echo number_format($passRate, 1) . '%';
                                                } else {
                                                    echo 'N/A';
                                                }
                                            } else {
                                                if ($questionCount <= 0) {
                                                    echo 'No questions';
                                                } else {
                                                    echo 'N/A';
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
                                        $sql_passed = "SELECT COUNT(*) AS passed_count FROM tbl_result WHERE course_id = :course_id AND stud_id = :stud_id AND quiz_type = 2 AND result_score >= total_questions * 0.5";
                                        $stmt_passed = $conn->prepare($sql_passed);
                                        $stmt_passed->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                                        $stmt_passed->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                        $stmt_passed->execute();
                                        $passed_result = $stmt_passed->fetch(PDO::FETCH_ASSOC);

                                        if ($passed_result['passed_count'] > 0) {
                                            // User has already passed the quiz, disable the button
                                            echo '<button class="btn btn-info mb-2" disabled><i class="lni lni-invention"></i></button>';
                                            $quiz_result_final = "Pass";
                                        } else {
                                            // User hasn't passed the quiz, enable the button
                                            echo '<button onclick="window.location.href=\'quiz.php?course_id=' . $course_id . '\'" class="btn btn-info mb-2" ' . $quiz_button . '><i class="lni lni-invention"></i></button>';
                                            $quiz_result_final = "-";
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
                                    global $QuizResultCount;
                                    global $QuizResult;

                                    // Temporary values
                                    $result_score = 0;
                                    $total_questions = 0;

                                    // Execute SQL query to fetch data
                                    $sql = "SELECT stud_id, course_id, result_score, total_questions
            FROM tbl_result
            WHERE quiz_type = 2
            ORDER BY result_id DESC
            LIMIT 1";

                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    $QuizResult = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($QuizResult !== false) {
                                        // Assign fetched values to variables
                                        $stud_id = $QuizResult['stud_id'];
                                        $course_id = $QuizResult['course_id'];
                                        $result_score = $QuizResult['result_score'];
                                        $total_questions = $QuizResult['total_questions'];

                                        // Calculate the pass rate and display pass/fail status here
                                        $passRate = 0;

                                        if ($result_score > 0 && ($result_score / $total_questions) * 100 >= 50) {
                                            echo $quiz_result_final;
                                        } else {
                                            echo  $quiz_result_final;;
                                        }
                                    } else {
                                        // Handle the case where no result is found
                                        echo "-";
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php

                                    if ($quizAttempts > 0) {
                                        if ($result_score > 0 && ($result_score / $total_questions) * 100 >= 50) {
                                            // Calculate and display the quiz percentage
                                            $QuizPercentage = 100 / $quizAttempts;
                                            echo number_format($QuizPercentage) . '%';
                                        } else {
                                            echo '-';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>

                            </tr>

                        </tbody>
                    </table>


                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&course_id=<?php echo $course_id; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <!-- </div> -->
    </div>

    <!-- Module Modal -->
    <div class="modal fade" id="moduleModal" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- Adjust modal size as needed -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moduleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="moduleIframe" style="width: 100%; height: 80vh;" frameborder="0"></iframe> <!-- Set height to 80vh (80% of the viewport height) -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
    <script>
        const viewModuleButtons = document.querySelectorAll('.view-module-btn');
        const actionTestButtons = document.querySelectorAll('.action-test-btn');
        const moduleIframe = document.getElementById('moduleIframe');

        // Function to handle opening modal with module content
        function openModuleModal(moduleId) {
            moduleIframe.src = `pdf_viewer.php?module_id=${moduleId}`;
            $('#moduleModal').modal('show'); // Trigger modal manually using jQuery
        }

        viewModuleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const moduleId = this.getAttribute('data-module-id');
                openModuleModal(moduleId);
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


</body>

</html>