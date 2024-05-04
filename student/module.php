<?php
require("../api/db-connect.php");
session_start();

$course_id = $_GET['course_id'];

global $quizstatus;
global $qs;

$sql = "SELECT tbl_course.program_id, tbl_course.course_id 
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
                <div class="col-md-8">
                    <div class="text-center mb-4">
                        <h1>Module</h1>

                    </div>





                    <table class="table table-bordered border-secondary">
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
                                                                echo '<button class="btn btn-success btn-sm" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '\'"><i class="lni lni-invention"></i></button>';
                                                            } else {
                                                                echo '<button class="btn btn-success btn-sm" disabled><i class="lni lni-invention"></i></button>';
                                                            }

                                                            echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-eye eye-icon text-white"></i></button>';
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
                                                    echo '<button class="btn btn-success btn-sm" onclick="window.location.href=\'question.php?module_id=' . $row['module_id'] . '\'"><i class="lni lni-invention"></i></button>';
                                                } else {
                                                    echo '<button class="btn btn-success btn-sm" disabled><i class="lni lni-invention"></i></button>';
                                                }
                                                echo '<button class="btn btn-warning btn-sm eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-eye eye-icon text-white"></i></button>';
                                            }
                                            ?>
                                        </td>

                                        <td><?php echo $resultCount; ?></td>
                                        <td>


                                            <!-- Display result status -->
                                            <?php
                                            if ($resultCount > 0) {
                                                if ($percentage >= 50) {
                                                    echo "Passed";
                                                    $qs = 1;
                                                } else {
                                                    echo "Failed";
                                                    $qs = -INF;
                                                }
                                            } else if ($questionCount <= 0) {
                                                echo "N/A";
                                                $qs = 1;
                                            } else {
                                                echo "No Attempt";
                                                $qs = -INF;
                                                $qs = 1;
                                            }
                                            ?>


                                        </td>
                                        <td>
                                            <?php
                                            // Calculate and display pass rate
                                            if ($resultCount > 0) {
                                                if ($totalQuestions > 0) {
                                                    $passRate = (100 / $resultCount);
                                                    echo number_format($passRate, 1) . '%';
                                                } else {
                                                    echo '0%';
                                                    $qs = -INF;
                                                }
                                            } else {
                                                echo 'No Attempt';
                                                $qs = -INF;
                                            }
                                            ?>
                                        </td>
                                    </tr>


                            <?php
                                    $counter++; // Increment the counter
                                endforeach;
                            endif;
                            ?>

                            <tr>
                                <td>#</td>
                                <td><b>Quiz</b></td>
                                <td> <?php
                                        // Display quiz button based on availability of questions
                        
                                        if ($qs > 0) {

                                            echo '<button onclick="window.location.href=\'quiz.php?module_id=' . $module_id . '\'" class="btn btn-info mb-2">Quiz</button>';
                                        } else {
                                            echo '<button onclick="window.location.href=\'quiz.php?module_id=' . $module_id . '\'" class="btn btn-info mb-2" disabled>Quiz</button>';
                                        }
                                        ?>
                                </td>
                                <td> 0 </td>
                                <td>  </td>
                                <td>  </td>
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