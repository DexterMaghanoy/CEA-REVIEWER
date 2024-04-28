<?php 
require("../api/db-connect.php");
session_start();

$course_id = $_GET['course_id'];

if(isset($_SESSION['program_id']) && isset($_SESSION['year_id'])) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
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
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($result->rowCount() > 0): ?>
                                <?php foreach ($result as $row): ?>
                                    <tr>
                                        <td><?php echo $row['module_number']; ?></td>
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
                                            
                                            // If the user has completed the quiz, disable the link
                                            if ($resultCount > 0) {
                                                echo '<button class="btn btn-secondary btn-sm" disabled><i class="lni lni-invention"></i></button>';
                                            } else {
                                                // Otherwise, display the link normally
                                                echo '<button class="btn btn-success btn-sm action-test-btn" data-bs-toggle="modal" data-bs-target="question.php" data-module-id="' . $row['module_id'] . '"><i class="lni lni-invention"></i></button>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center mb-4">
                            <h1>No records found for module.</h1>
                        </div>
                    <?php endif; ?>
                                </tbody>
                            </table>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous">
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
            window.location.href = `question.php?module_id=${moduleId}`;
        });
    });

    // Optional: JavaScript for toggling the sidebar
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function () {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>


</body>
<style>
      @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

::after,
::before {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

a {
    text-decoration: none;
}

li {
    list-style: none;
}

h1 {
    font-weight: 600;
    font-size: 1.5rem;
}

body {
    font-family: 'Poppins', sans-serif;
}

.wrapper {
    display: flex;
}

.main {
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
    transition: all 0.35s ease-in-out;
    background-color: #fafbfe;
}

#sidebar {
    width: 70px;
    min-width: 70px;
    z-index: 1000;
    transition: all .25s ease-in-out;
    background-color: #0e2238;
    display: flex;
    flex-direction: column;
}

#sidebar.expand {
    width: 260px;
    min-width: 260px;
}

.toggle-btn {
    background-color: transparent;
    cursor: pointer;
    border: 0;
    padding: 1rem 1.5rem;
}

.toggle-btn i {
    font-size: 1.5rem;
    color: #FFF;
}

.sidebar-logo {
    margin: auto 0;
}

.sidebar-logo a {
    color: #FFF;
    font-size: 1.15rem;
    font-weight: 600;
}

#sidebar:not(.expand) .sidebar-logo,
#sidebar:not(.expand) a.sidebar-link span {
    display: none;
}

.sidebar-nav {
    padding: 2rem 0;
    flex: 1 1 auto;
}

a.sidebar-link {
    padding: .625rem 1.625rem;
    color: #FFF;
    display: block;
    font-size: 0.9rem;
    white-space: nowrap;
    border-left: 3px solid transparent;
}

.sidebar-link i {
    font-size: 1.1rem;
    margin-right: .75rem;
}

a.sidebar-link:hover {
    background-color: rgba(255, 255, 255, .075);
    border-left: 3px solid #3b7ddd;
}

.sidebar-item {
    position: relative;
}

#sidebar:not(.expand) .sidebar-item .sidebar-dropdown {
    position: absolute;
    top: 0;
    left: 70px;
    background-color: #0e2238;
    padding: 0;
    min-width: 15rem;
    display: none;
}

#sidebar:not(.expand) .sidebar-item:hover .has-dropdown+.sidebar-dropdown {
    display: block;
    max-height: 15em;
    width: 100%;
    opacity: 1;
}

#sidebar.expand .sidebar-link[data-bs-toggle="collapse"]::after {
    border: solid;
    border-width: 0 .075rem .075rem 0;
    content: "";
    display: inline-block;
    padding: 2px;
    position: absolute;
    right: 1.5rem;
    top: 1.4rem;
    transform: rotate(-135deg);
    transition: all .2s ease-out;
}

#sidebar.expand .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
    transform: rotate(45deg);
    transition: all .2s ease-out;
}
</style>
</html>
