<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

$course_id = $_GET['course_id'];

$recordsPerPage = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Build the SQL query with search functionality
$sql = "SELECT * FROM tbl_module WHERE course_id = :course_id";
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
    <style>
        /* Custom CSS for modal */
        .modal-lg-custom {
            max-width: 80%;
            /* Adjust the width as needed */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Module</h1>
                        </div>
                        <a class="btn btn-outline-success btn-sm" href="add_module.php?course_id=<?php echo $course_id ?>"><i class="lni lni-plus"></i></a><br><br>
                        <table class="table table-bordered border-secondary">
                            <caption>List of Module</caption>
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">No.</th>
                                    <th scope="col">Module Title</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->rowCount() > 0) : ?>
                                    <?php $count = 1; ?>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td>
                                                <?php echo $row['module_name']; ?>
                                            </td>
                                            <td>
                                                <a class="btn btn-success btn-sm view-module-btn" data-bs-toggle="modal" data-bs-target="#moduleModal" data-module-id="<?php echo $row['module_id']; ?>">View</a>
                                                <a class="btn btn-primary btn-sm" href="question.php?module_id=<?php echo $row['module_id']; ?>"><i class="lni lni-upload"></i></a>
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-module-id="<?php echo $row['module_id']; ?>"><i class="lni lni-eraser"></i></button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No records found for module.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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
        <div class="modal-dialog modal-dialog-centered modal-lg modal-lg-custom"> <!-- Added 'modal-dialog-centered' class -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moduleModalLabel">Module Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="moduleIframe" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Module Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this module?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="deleteModuleButton" href="#" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        // JavaScript to handle passing module_id to view PDF in modal
        const viewModuleButtons = document.querySelectorAll('.view-module-btn');
        const moduleIframe = document.getElementById('moduleIframe');

        viewModuleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const moduleId = this.getAttribute('data-module-id');
                moduleIframe.src = `pdf_viewer.php?module_id=${moduleId}`;
            });
        });
    </script>
</body>

</html>