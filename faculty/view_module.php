<?php
session_start();
require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

$course_id = $_GET['course_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';

$recordsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Build the SQL query with search functionality
$sql = "SELECT * FROM tbl_module WHERE course_id = :course_id";
if (!empty($search)) {
    $sql .= " AND module_name LIKE :search";
}
$sql .= " LIMIT :limit OFFSET :offset";

$result = $conn->prepare($sql);
$result->bindParam(':course_id', $course_id, PDO::PARAM_INT);
if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $result->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$result->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$modules = $result->fetchAll(PDO::FETCH_ASSOC);

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_module WHERE course_id = :course_id";
if (!empty($search)) {
    $countSql .= " AND module_name LIKE :search";
}

$countStmt = $conn->prepare($countSql);
$countStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
if (!empty($search)) {
    $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$countStmt->execute();
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $recordsPerPage);

// Check if the request is a POST request and handle accordingly
if (isset($_POST['moduleId']) && isset($_POST['moduleStatus'])) {
    $moduleId = $_POST['moduleId'];
    $moduleStatus = $_POST['moduleStatus'];

    // Update module status in the database
    $updateSql = "UPDATE tbl_module SET module_status = :moduleStatus WHERE module_id = :moduleId";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':moduleStatus', $moduleStatus, PDO::PARAM_INT);
    $updateStmt->bindParam(':moduleId', $moduleId, PDO::PARAM_INT);
    $updateSuccess = $updateStmt->execute();

    // Check if the update was successful
    if ($updateSuccess) {
        // Fetch the updated module status
        $fetchSql = "SELECT module_status FROM tbl_module WHERE module_id = :moduleId";
        $fetchStmt = $conn->prepare($fetchSql);
        $fetchStmt->bindParam(':moduleId', $moduleId, PDO::PARAM_INT);
        $fetchStmt->execute();
        $moduleData = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        if ($moduleData) {
            // Include the module status in the response
            echo json_encode(['success' => true, 'moduleStatus' => $moduleData['module_status']]);
        } else {
            // Handle error if module data not found
            echo json_encode(['success' => false, 'message' => 'Failed to fetch updated module status']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update module status']);
    }

    exit(); // Stop further execution
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
        <?php include 'sidebar.php'; ?>
        <div class="container">
            <div class="row justify-content-center mt-4">
                <div class="col-md-12">
                    <div class="text-center mb-4">
                        <?php
                        $courseSql = "SELECT course_name FROM tbl_course WHERE course_id = :course_id";
                        $courseStmt = $conn->prepare($courseSql);
                        $courseStmt->bindParam(':course_id', $_GET['course_id'], PDO::PARAM_INT);
                        $courseStmt->execute();
                        $SubjectName = $courseStmt->fetch(PDO::FETCH_ASSOC);

                        // Check if course name is fetched successfully
                        if ($SubjectName) {
                            $courseName = $SubjectName['course_name'];
                        } else {
                            $courseName = "Unknown Course"; // Default value if course name not found
                        }
                        ?>

                        <h1>Module: <span style="font-weight: normal;"><?php echo htmlspecialchars($courseName); ?></span></h1>


                    </div>

                    <form action="" method="GET" class="mb-4" id="searchForm">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                        </div>
                    </form>

                    <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                        <caption>List of Modules</caption>
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">No.</th>
                                <th scope="col">Module Title</th>
                                <th scope="col">Action</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            // Build the SQL query with search functionality
                            $sql = "SELECT * FROM tbl_module WHERE course_id = :course_id";
                            if (!empty($search)) {
                                $sql .= " AND module_name LIKE :search";
                            }
                            $result = $conn->prepare($sql);
                            $result->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                            if (!empty($search)) {
                                $searchTerm = '%' . $search . '%';
                                $result->bindParam(':search', $searchTerm, PDO::PARAM_STR);
                            }
                            $result->execute();
                            ?>


                            <?php if ($result->rowCount() > 0) : ?>
                                <?php $count = 1; ?>
                                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo $row['module_name']; ?></td>
                                        <td>
                                            <a class="btn btn-success btn-sm view-module-btn" data-bs-toggle="modal" data-bs-target="#moduleModal" data-module-id="<?php echo $row['module_id']; ?>">
                                                <i class="lni lni-eye" style="font-size: 1.2rem;"></i>
                                            </a>
                                            <a class="btn btn-primary btn-sm" href="question.php?program_id=<?php echo $program_id ?>&module_id=<?php echo $row['module_id']; ?>&course_id=<?php echo $course_id; ?>">
                                                <i class="lni lni-upload" style="font-size: 1.2rem;"></i>
                                            </a>
                                        </td>

                                        <td class="text-center align-middle">
                                            <?php
                                            $buttonType = ($row['module_status'] == 1) ? 'checked' : '';
                                            ?>
                                            <label class="switch m-0">
                                                <input id="toggleSwitch_<?php echo $row['module_id']; ?>" type="checkbox" <?php echo $buttonType; ?> onclick="toggleModuleStatus(<?php echo $row['module_id']; ?>)">
                                                <span class="slider round"></span>
                                            </label>

                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr id="noResultsRow" style="display: none;">
                                    <td colspan="4" class="text-center">No results found.</td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">No records found for modules.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="moduleModal" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-light">
                    <h5 class="modal-title" id="moduleModalLabel">Module Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="moduleIframe" style="width: 100%; height: 75vh;" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        const viewModuleButtons = document.querySelectorAll('.view-module-btn');
        const moduleIframe = document.getElementById('moduleIframe');
        const moduleModalLabel = document.getElementById('moduleModalLabel');

        viewModuleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const moduleId = this.getAttribute('data-module-id');
                const moduleName = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                moduleModalLabel.textContent = `Module: "${moduleName}"`;
                moduleIframe.src = `pdf_viewer.php?module_id=${moduleId}`;
            });
        });


        const searchInput = document.getElementById('searchInput');
        const moduleRows = document.querySelectorAll('table tbody tr');

        searchInput.addEventListener('input', function() {
            const searchText = this.value.trim().toLowerCase();

            moduleRows.forEach(function(row) {
                const moduleName = row.cells[1].textContent.trim().toLowerCase();

                if (moduleName.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

    <script>
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
    </script>
    <script>
        function toggleModuleStatus(moduleId) {
            const toggleSwitch = document.querySelector(`#toggleSwitch_${moduleId}`);
            const moduleStatus = toggleSwitch.checked ? 1 : 0;

            const formData = new FormData();
            formData.append('moduleId', moduleId);
            formData.append('moduleStatus', moduleStatus);

            fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    } else {
                        throw new Error('Network response was not ok');
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Update the checked state of the toggle switch
                        toggleSwitch.checked = !toggleSwitch.checked;
                    } else {
                        console.error('Failed to update module status');
                    }
                })
                .catch(error => {
                    console.error('Error updating module status:', error);
                });
        }
    </script>

    <script>
        searchInput.addEventListener('input', function() {
            const searchText = this.value.trim().toLowerCase();
            let hasResults = false;

            moduleRows.forEach(function(row) {
                const moduleNameCell = row.cells[1];
                if (moduleNameCell) {
                    const moduleName = moduleNameCell.textContent.trim().toLowerCase();

                    if (moduleName.includes(searchText)) {
                        row.style.display = '';
                        hasResults = true;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });

            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = hasResults ? 'none' : '';
            }
        });
    </script>