<?php
require("../api/db-connect.php");
session_start();

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}
// Check if form is submitted for toggling user status
if (isset($_POST['toggle_status']) && isset($_POST['stud_id'])) {
    $stud_id = $_POST['stud_id'];
    // Get current status of the user
    $stmt = $conn->prepare("SELECT stud_status FROM tbl_student WHERE stud_id = :stud_id");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['stud_status'] == 1 ? 0 : 1; // Toggle status

    // Update user status
    $updateStmt = $conn->prepare("UPDATE tbl_student SET stud_status = :new_status WHERE stud_id = :stud_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $updateStmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$recordsPerPage = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality
$sql = "SELECT s.*, y.year_level, p.program_name
        FROM tbl_student AS s
        JOIN tbl_year AS y ON s.year_id = y.year_id
        JOIN tbl_program AS p ON s.program_id = p.program_id";

if (!empty($search)) {
    $sql .= " WHERE (s.stud_lname LIKE '%$search%' OR s.stud_fname LIKE '%$search%' OR y.year_level LIKE '%$search%' OR s.stud_mname LIKE '%$search%' OR s.stud_no LIKE '%$search%') AND s.program_id = :program_id";
} else {
    $sql .= " WHERE s.program_id = :program_id";
}

$sql .= " ORDER BY s.stud_status DESC, y.year_level ASC LIMIT :offset, :recordsPerPage";

$result = $conn->prepare($sql);

$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

$countSql = "SELECT COUNT(*) as total FROM tbl_student WHERE program_id = :program_id";
if (!empty($search)) {
    $countSql .= " AND (stud_lname LIKE :search OR stud_fname LIKE :search)";
}

$countStmt = $conn->prepare($countSql);
$countStmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
if (!empty($search)) {
    $searchParam = "%$search%";
    $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
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
    <title>Student</title>
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
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Students</h1>
                        </div>
                        <div class="d-flex">
                            <a class="btn btn-outline-success btn-sm me-2" href="add_student.php"><i class="lni lni-plus"></i></a>
                            <a class="btn btn-outline-primary btn-sm" href="import_student.php"><i class="lni lni-upload"></i></a>
                        </div><br>
                        <!-- Search bar -->
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of Student</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Student No.</th>
                                        <th scope="col">Program</th>
                                        <th scope="col">Fullname</th>
                                        <th scope="col">Year Level</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->rowCount() > 0) : ?>
                                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                            <tr>
                                                <td><?php echo $row['stud_no']; ?></td>
                                                <td><?php echo $row['program_name']; ?></td>
                                                <td><?php echo $row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']; ?></td>
                                                <td><?php echo $row['year_level']; ?></td>
                                                <td>
                                                    <a class="btn btn-primary btn-sm" href="edit_student.php?stud_id=<?php echo $row['stud_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="stud_id" value="<?php echo $row['stud_id']; ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['stud_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>">
                                                            <?php if ($row['stud_status'] == 1) : ?>
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
                                            <td colspan="4" class="text-center">No records found for student.</td>
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