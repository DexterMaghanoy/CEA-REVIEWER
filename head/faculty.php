<?php
require("../api/db-connect.php");
session_start();

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
    $type_id = 3;
} else {
    header("Location: ../login.php");
    exit();
}

// Check if form is submitted for toggling user status
if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    // Get current status of the user
    $stmt = $conn->prepare("SELECT user_status FROM tbl_user WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['user_status'] == 1 ? 0 : 1; // Toggle status

    // Update user status
    $updateStmt = $conn->prepare("UPDATE tbl_user SET user_status = :new_status WHERE user_id = :user_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $updateStmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$recordsPerPage = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality
$sql = "SELECT u.*, t.type_name, p.program_name
        FROM tbl_user AS u
        JOIN tbl_type AS t ON u.type_id = t.type_id
        JOIN tbl_program AS p ON u.program_id = p.program_id";

if (!empty($search)) {
    $sql .= " WHERE (u.user_lname LIKE '%$search%' OR u.user_fname LIKE '%$search%' OR t.type_name LIKE '%$search%' OR u.user_mname LIKE '%$search%' OR p.program_name LIKE '%$search%') AND u.program_id = :program_id AND u.type_id = :type_id";
} else {
    $sql .= " WHERE u.program_id = :program_id AND u.type_id = :type_id";
}

$sql .= " ORDER BY u.user_status DESC, t.type_id ASC LIMIT :offset, :recordsPerPage";

$result = $conn->prepare($sql);

$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->bindParam(':type_id', $type_id, PDO::PARAM_INT);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_user WHERE program_id = :program_id AND type_id = 2";
if (!empty($search)) {
    $countSql .= " AND (user_lname LIKE '%$search%' OR user_fname LIKE '%$search%')";
}

$countStmt = $conn->prepare($countSql);
$countStmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
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
    <title>Faculty</title>
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
                            <h1>Faculty</h1>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="add_faculty.php"><i class="lni lni-plus"></i></a><br><br>
                        <!-- Search bar -->
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of Faculty</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Role</th>
                                        <th scope="col">Program</th>
                                        <th scope="col">Fullname</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->rowCount() > 0) : ?>
                                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                            <tr>
                                                <td><?php echo $row['type_name']; ?></td>
                                                <td><?php echo $row['program_name']; ?></td>
                                                <td><?php echo $row['user_lname'] . ', ' . $row['user_fname'] . ' ' . $row['user_mname']; ?></td>
                                                <td>
                                                    <a class="btn btn-primary btn-sm" href="edit_faculty.php?user_id=<?php echo $row['user_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['user_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>">
                                                            <?php if ($row['user_status'] == 1) : ?>
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
                                            <td colspan="4" class="text-center">No records found for faculty.</td>
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