<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

$module_id = $_GET['module_id'];

// Fetch course_id based on module_id
$sql_course_id = "SELECT course_id FROM tbl_module WHERE module_id = :module_id";
$stmt_course_id = $conn->prepare($sql_course_id);
$stmt_course_id->bindParam(':module_id', $module_id, PDO::PARAM_INT);
$stmt_course_id->execute();
$course_id_row = $stmt_course_id->fetch(PDO::FETCH_ASSOC);
$course_id = $course_id_row['course_id'];

$recordsPerPage = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Build the SQL query with search functionality and pagination
$sql = "SELECT * FROM tbl_question WHERE module_id = :module_id LIMIT :offset, :recordsPerPage";
$result = $conn->prepare($sql);
$result->bindParam(':module_id', $module_id, PDO::PARAM_INT);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_question WHERE module_id = :module_id";

$countStmt = $conn->prepare($countSql);
$countStmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
$countStmt->execute();
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $recordsPerPage);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>



        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-10">
                    <div class="text-center mb-2">
                        <h1>Question: <?php
                                        $sql_module_name = "SELECT module_name FROM tbl_module WHERE module_id = :module_id"; // Fixed variable name
                                        $stmt_module_name = $conn->prepare($sql_module_name); // Fixed variable name
                                        $stmt_module_name->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                                        $stmt_module_name->execute();
                                        $module_name = $stmt_module_name->fetch(PDO::FETCH_ASSOC);
                                        $moduleName = isset($module_name['module_name']) ? $module_name['module_name'] : "Unknown Module"; // Check if module name is fetched successfully
                                        echo htmlspecialchars($moduleName); // Use htmlspecialchars to prevent XSS attacks
                                        ?></h1>

                    </div>
                    <div class="d-flex mb-2">
                        <a class="btn btn-outline-success btn-sm me-2" href="add_question.php?program_id=<?php echo $program_id ?>&course_id=<?php echo $course_id ?>&module_id=<?php echo $module_id ?>"><i class="lni lni-plus"></i> Add Question</a>
                        <a class="btn btn-outline-primary btn-sm" href="import_question.php?program_id=<?php echo $program_id ?>&course_id=<?php echo $course_id ?>&module_id=<?php echo $module_id ?>"><i class="lni lni-upload"></i> Import Question</a>
                    </div>

                    <table class="table table-bordered border-secondary" style="table-layout: auto; width: 100%;">
                        <caption>List of Question</caption>
                        <thead>
                            <tr>
                                <th scope="col">Question</th>
                                <th scope="col">Option A</th>
                                <th scope="col">Option B</th>
                                <th scope="col">Option C</th>
                                <th scope="col">Option D</th>
                                <th scope="col">Answer</th>
                                <th scope="col" style="width: 100px" ;>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->rowCount() > 0) : ?>
                                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                    <tr>
                                        <td style="font-size: 13px; text-align: left;"><?php echo $row['question_text']; ?></td>
                                        <td style="font-size: 13px; text-align: left;"><?php echo $row['question_A']; ?></td>
                                        <td style="font-size: 13px; text-align: left;"><?php echo $row['question_B']; ?></td>
                                        <td style="font-size: 13px; text-align: left;"><?php echo $row['question_C']; ?></td>
                                        <td style="font-size: 13px; text-align: left;"><?php echo $row['question_D']; ?></td>
                                        <td style="font-size: 13px; text-align: left;"><?php echo $row['question_answer']; ?></td>
                                        <td>
                                            <a class="btn btn-success btn-sm" href="edit_question.php?question_id=<?php echo $row['question_id']; ?>"><i class="lni lni-pencil"></i></a>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-question-id="<?php echo $row['question_id']; ?>"><i class="lni lni-eraser"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="text-center">No questions found for module.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

    <!-- Add this modal at the end of your HTML -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this question?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="deleteModuleButton" href="#" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });

    // JavaScript to handle passing module_id to delete modal
    const deleteButtons = document.querySelectorAll('[data-bs-target="#deleteModal"]');
    const deleteModuleButton = document.getElementById('deleteModuleButton');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            deleteModuleButton.href = `delete_question.php?question_id=${questionId}`;
        });
    });
</script>

</html>