<?php
require("../api/db-connect.php");
session_start();

$course_id = $_GET['course_id'];

if (isset($_SESSION['program_id']) && isset($_SESSION['year_id'])) {
    $program_id = $_SESSION['program_id'];
    $year_id = $_SESSION['year_id'];

    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id AND year_id = :year_id AND sem_id = 1";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id);
    $result->bindParam(':year_id', $year_id);
    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);

    // Build the SQL query for module retrieval
    $recordsPerPage = 5;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $recordsPerPage;

    $sql = "SELECT tbl_module.*, tbl_course.course_name 
            FROM tbl_module 
            INNER JOIN tbl_course ON tbl_module.course_id = tbl_course.course_id 
            WHERE tbl_module.course_id = :course_id";

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
    header("Location: ../login.php");
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



        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Module</h1>
                        </div>
                        <table class="table table-bordered border-secondary">
                            <caption>List of Module</caption>
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">No.</th>
                                    <th scope="col">Module Title</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Attempts</th>
                                    <th scope="col">Result</th>
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
                                                // Check if the quiz has been completed by the user
                                                $sql = "SELECT COUNT(*) AS count FROM tbl_result WHERE module_id = :module_id AND stud_id = :stud_id";
                                                $stmt = $conn->prepare($sql);
                                                $stmt->bindParam(":module_id", $row['module_id'], PDO::PARAM_INT);
                                                $stmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                                $stmt->execute();
                                                $resultCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                                                // Retrieve the user's score for the module if attempted
                                                if ($resultCount > 0) {
                                                    $sql = "SELECT tbl_result.result_score, COUNT(tbl_question.question_id) AS total_questions
                                                    FROM tbl_result 
                                                    LEFT JOIN tbl_question ON tbl_result.module_id = tbl_question.module_id
                                                    WHERE tbl_result.result_id = (
                                                        SELECT MAX(result_id) 
                                                        FROM tbl_result 
                                                        WHERE module_id = :module_id 
                                                        AND stud_id = :stud_id
                                                    )";


                                                    $stmt = $conn->prepare($sql);
                                                    $stmt->bindParam(":module_id", $row['module_id'], PDO::PARAM_INT);
                                                    $stmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                                    $stmt->execute();
                                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                                    // Check if the result exists and calculate percentage
                                                    if ($result) {
                                                        $resultScore = $result['result_score'];
                                                        $totalQuestions = $result['total_questions'];

                                                        if ($totalQuestions > 0) {
                                                            $percentage = ($resultScore / $totalQuestions) * 100;
                                                            if ($percentage > 50) {
                                                                // Disable Take Quiz button
                                                                echo '<button style="margin-left: 5px;" class="btn btn-success btn-sm action-test-btn" data-bs-toggle="modal" data-bs-target="question.php" data-module-id="' . $row['module_id'] . '" disabled><i class="lni lni-invention"></i></button>';

                                                                // Enable View Answers button
                                                                echo '<button style="margin-left: 5px;" class="btn btn-warning btn-sm action-test-btn eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '">';
                                                                echo '<i class="lni lni-eye eye-icon text-white"></i></button>';
                                                            } else {
                                                                // Disable both buttons if not passed
                                                                echo '<button style="margin-left: 5px;" class="btn btn-success btn-sm action-test-btn" data-bs-toggle="modal" data-bs-target="question.php" data-module-id="' . $row['module_id'] . '"><i class="lni lni-invention"></i></button>';
                                                                echo '<button style="margin-left: 5px;" class="btn btn-warning btn-sm action-test-btn eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '" disabled>';
                                                                echo '<i class="lni lni-eye eye-icon text-white"></i></button>';
                                                            }
                                                        } else {
                                                            echo "No questions available";
                                                        }
                                                    } else {
                                                        echo "No result found";
                                                    }
                                                } else {
                                                    // For the first iteration, enable both buttons
                                                    if ($counter == 1) {
                                                        echo '<button style="margin-left: 5px;" class="btn btn-success btn-sm action-test-btn" data-bs-toggle="modal" data-bs-target="question.php" data-module-id="' . $row['module_id'] . '"><i class="lni lni-invention"></i></button>';
                                                        echo '<button style="margin-left: 5px;" class="btn btn-warning btn-sm action-test-btn eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '"disabled>';
                                                        echo '<i class="lni lni-eye eye-icon text-white"></i></button>';
                                                    } else {
                                                        // For subsequent iterations, disable both buttons
                                                        echo '<button style="margin-left: 5px;" class="btn btn-success btn-sm action-test-btn" data-bs-toggle="modal" data-bs-target="question.php" data-module-id="' . $row['module_id'] . '"><i class="lni lni-invention"></i></button>';
                                                        echo '<button style="margin-left: 5px;" class="btn btn-warning btn-sm action-test-btn eye-icon-btn" data-bs-toggle="modal" data-bs-target="question-answers.php" data-module-id="' . $row['module_id'] . '"disabled>';
                                                        echo '<i class="lni lni-eye eye-icon text-white"></i></button>';
                                                    }
                                                }
                                                ?>

                                            </td>
                                            <td>
                                                <?php
                                                // Display the attempt count
                                                echo $resultCount;
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Check if the user has attempted the quiz
                                                if ($resultCount > 0) {
                                                    // Retrieve the user's score for the module
                                                    $sql = "SELECT tbl_result.result_score, COUNT(tbl_question.question_id) AS total_questions
                                                    FROM tbl_result 
                                                    LEFT JOIN tbl_question ON tbl_result.module_id = tbl_question.module_id
                                                    WHERE tbl_result.module_id = :module_id 
                                                    AND tbl_result.stud_id = :stud_id
                                                    AND tbl_result.result_id = (
                                                        SELECT MAX(result_id) 
                                                        FROM tbl_result 
                                                        WHERE module_id = :module_id 
                                                        AND stud_id = :stud_id
                                                    )";


                                                    $stmt = $conn->prepare($sql);
                                                    $stmt->bindParam(":module_id", $row['module_id'], PDO::PARAM_INT);
                                                    $stmt->bindParam(":stud_id", $_SESSION['stud_id'], PDO::PARAM_INT);
                                                    $stmt->execute();
                                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                                    // Check if the result exists and calculate percentage
                                                    if ($result) {
                                                        $resultScore = $result['result_score'];
                                                        $totalQuestions = $result['total_questions'];

                                                        if ($totalQuestions > 0) {
                                                            $percentage = ($resultScore / $totalQuestions) * 100;
                                                            if ($percentage >= 50) {
                                                                echo "Passed";
                                                            } else {
                                                                echo "Failed";
                                                            }
                                                        } else {
                                                            echo "No questions available";
                                                        }
                                                    } else {
                                                        echo "No result found";
                                                    }
                                                } else {
                                                    echo "No attempt";
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                <?php
                                        $counter++; // Increment the counter
                                    endforeach;
                                endif;
                                ?>


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
        </div>
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