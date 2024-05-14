<?php
session_start();
require("../api/db-connect.php");

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

// Check if form is submitted for toggling user status
if (isset($_POST['toggle_status']) && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    // Get current status of the course
    $stmt = $conn->prepare("SELECT course_status FROM tbl_course WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['course_status'] == 1 ? 0 : 1; // Toggle status

    // Update course status
    $updateStmt = $conn->prepare("UPDATE tbl_course SET course_status = :new_status WHERE course_id = :course_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $updateStmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$recordsPerPage = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality
$sql = "SELECT c.*, u.user_lname, u.user_fname, p.program_name
        FROM tbl_course AS c
        JOIN tbl_user AS u ON c.user_id = u.user_id
        JOIN tbl_program AS p ON c.program_id = p.program_id
        WHERE c.program_id = :program_id";

if (!empty($search)) {
    $sql .= " AND (c.course_code LIKE :search OR c.course_name LIKE :search OR u.user_lname LIKE :search OR u.user_fname LIKE :search OR p.program_name LIKE :search)";
}

$sql .= " ORDER BY c.course_status DESC, p.program_name ASC LIMIT :offset, :recordsPerPage";

try {
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->bindParam(':offset', $offset, PDO::PARAM_INT);
    $result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);

    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }

    $result->execute();

    // Count total number of records
    $countSql = "SELECT COUNT(*) as total FROM tbl_course WHERE program_id = :program_id";
    if (!empty($search)) {
        $countSql .= " AND (course_code LIKE :search OR course_name LIKE :search)";
    }

    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);

    if (!empty($search)) {
        $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCount / $recordsPerPage);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course</title>
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
            <?php
            include 'back.php';
            ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="text-center">
                        <h1>Course</h1>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="add_course.php"><i class="lni lni-plus"></i></a><br><br>
                    <!-- Search bar -->
                    <form action="" method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search...">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered border-secondary">
                            <caption>List of Course</caption>
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Assigned</th>
                                    <th scope="col">Code</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->rowCount() > 0) : ?>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <tr>
                                            <td><?php echo $row['user_lname'] . ', ' . $row['user_fname'] ?></td>
                                            <td><?php echo $row['course_code']; ?></td>
                                            <td><?php echo $row['course_name']; ?></td>
                                            <td>
                                                <a class="btn btn-info btn-sm" href="edit_course.php?course_id=<?php echo $row['course_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                <a class="btn btn-primary btn-sm" href="view_module.php?course_id=<?php echo $row['course_id']; ?>"><i class="lni lni-radio-button"></i></a>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['course_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>">
                                                        <?php if ($row['course_status'] == 1) : ?>
                                                            <i class="lni lni-checkmark-circle"></i> <!-- Green circle icon for activated -->
                                                        <?php else : ?>
                                                            <i class="lni lni-checkmark-circle"></i> <!-- Yellow circle icon for deactivated -->
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No records found for course.</td>
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