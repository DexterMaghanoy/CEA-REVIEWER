<?php
session_start();
require("../api/db-connect.php");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../index.php");
    exit();
}

// Check if form is submitted for toggling user status
if (isset($_POST['toggle_status']) && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    // Get current status of the user
    $stmt = $conn->prepare("SELECT course_status FROM tbl_course WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['course_status'] == 1 ? 0 : 1; // Toggle status

    // Update user status
    $updateStmt = $conn->prepare("UPDATE tbl_course SET course_status = :new_status WHERE course_id = :course_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $updateStmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$recordsPerPage = 7; // Update to display 10 records per page

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality
$sql = "SELECT c.*, u.user_lname, u.user_fname, p.program_name
        FROM tbl_course AS c
        JOIN tbl_user AS u ON c.user_id = u.user_id
        JOIN tbl_program AS p ON c.program_id = p.program_id";

if (!empty($search)) {
    $sql .= " WHERE c.course_code LIKE :search OR c.course_name LIKE :search OR u.user_lname LIKE :search OR u.user_fname LIKE :search OR p.program_name LIKE :search";
}

$sql .= " ORDER BY c.course_status DESC, p.program_name ASC LIMIT :offset, :recordsPerPage";

$result = $conn->prepare($sql);

if (!empty($search)) {
    $searchParam = '%' . $search . '%';
    $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_course";
if (!empty($search)) {
    $countSql .= " WHERE course_code LIKE :search OR course_name LIKE :search";
}

$countStmt = $conn->prepare($countSql);
if (!empty($search)) {
    $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
$countStmt->execute();
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $recordsPerPage);

// Fetch all courses to populate the dropdown
$stmt = $conn->prepare("SELECT program_id, program_name FROM tbl_program");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects</title>
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

        <?php
        include 'back.php';
        ?>
        <div class="container mt-2">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="text-center mt-3">
                        <h1>Subjects: <?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?></h1>

                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="add_subject.php"><i class="lni lni-plus"></i></a><br><br>
                    <!-- Search bar -->
                    <div class="row">
                        <div class="col">
                            <!-- Course ID Dropdown -->




                            <!-- Course ID Dropdown -->
                            <div class="mb-3">
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="" <?php echo (isset($_GET['search']) && $_GET['search'] === '') ? 'selected' : ''; ?>>
                                        <?php echo isset($_GET['search']) ? $_GET['search'] : 'Select Program'; ?>
                                    </option>

                                    <?php foreach ($courses as $course) : ?>
                                        <option value="<?= htmlspecialchars($course['program_id']); ?>" data-program-name="<?= htmlspecialchars($course['program_name']); ?>">
                                            <?= htmlspecialchars($course['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    document.getElementById('program_id').addEventListener('change', function() {
                                        var selectedProgram = this.options[this.selectedIndex];
                                        var programName = selectedProgram.getAttribute('data-program-name');
                                        if (programName) {
                                            window.location.href = 'subjects.php?search=' + encodeURIComponent(programName);
                                        }
                                    });
                                });
                            </script>




                        </div>
                        <div class="col">
                            <form action="" method="GET" class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search...">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">

                            <caption>List of Course</caption>
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Code</th>

                                    <th scope="col">Subject</th>
                                    <th scope="col">Teacher</th>

                                    <th scope="col">Course</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->rowCount() > 0) : ?>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <tr>
                                            <td><?php echo $row['course_code']; ?></td>
                                            <td><?php echo $row['course_name']; ?></td>
                                            <td><?php echo $row['user_lname'] . ', ' . $row['user_fname'] ?></td>
                                            <td><?php echo $row['program_name']; ?></td>
                                            <td>
                                                <a class="btn btn-info btn-sm" href="edit_subject.php?course_id=<?php echo $row['course_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                <a class="btn btn-primary btn-sm" href="view_module.php?course_id=<?php echo $row['course_id']; ?>"><i class="lni lni-radio-button"></i></a>
                                            </td>
                                            <td>
                                                <!-- Inside the table row for toggling status -->
                                                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display: inline;" id="statusForm_<?php echo $row['course_id']; ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['course_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>" onclick="submitForm(<?php echo $row['course_id']; ?>)">
                                                        <?php if ($row['course_status'] == 1) : ?>
                                                            <i class="lni lni-checkmark-circle"></i>
                                                        <?php else : ?>
                                                            <i class="lni lni-checkmark-circle"></i>
                                                        <?php endif; ?>
                                                    </button>
                                                </form>


                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No records found for course.</td>
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
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
</script>

</html>