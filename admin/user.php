<?php 
session_start();
require("../api/db-connect.php");

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../index.php");
    exit();
}

// Check if form is submitted for toggling user status
if(isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
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

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$recordsPerPage = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the SQL query with search functionality
$sql = "SELECT u.*, t.type_name, p.program_name
        FROM tbl_user AS u
        JOIN tbl_type AS t ON u.type_id = t.type_id
        JOIN tbl_program AS p ON u.program_id = p.program_id
        WHERE t.type_id != 1";


if (!empty($search)) {
    $sql .= " WHERE u.user_lname LIKE '%$search%' OR u.user_fname LIKE '%$search%' OR t.type_name LIKE '%$search%' OR u.user_mname LIKE '%$search%' OR p.program_name LIKE '%$search%'";
}

$sql .= " ORDER BY u.user_status DESC, t.type_id ASC LIMIT :offset, :recordsPerPage";

$result = $conn->prepare($sql);

$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

// Count total number of records
$countSql = "SELECT COUNT(*) as total FROM tbl_user";
if (!empty($search)) {
    $countSql .= " WHERE user_lname LIKE '%$search%' OR user_fname LIKE '%$search%'";
}

$countStmt = $conn->prepare($countSql);
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
    <title>User</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
    <div class="wrapper">
    <?php
        include 'sidebar.php';
        ?>
        <?php include 'back.php'; ?>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="text-center">
                            <h1>User</h1>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="add_faculty.php?"><i class="lni lni-plus"></i></a><br><br>
                        <!-- Search bar -->
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search...">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of User</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Role</th>
                                        <th scope="col">Program</th>
                                        <th scope="col">Fullname</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->rowCount() > 0): ?>
                                        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo $row['type_name']; ?></td>
                                                <td><?php echo $row['program_name']; ?></td>
                                                <td><?php echo $row['user_lname'] . ', ' . $row['user_fname'] . ' ' . $row['user_mname']; ?></td>
                                                <td>
                                                <a class="btn btn-primary btn-sm" href="edit_faculty.php?user_id=<?php echo $row['user_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['user_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>">
                                                        <?php if ($row['user_status'] == 1): ?>
                                                            <i class="lni lni-checkmark-circle"></i> <!-- Green circle icon for activated -->
                                                        <?php else: ?>
                                                            <i class="lni lni-checkmark-circle"></i> <!-- Yellow circle icon for deactivated -->
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No records found for user.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
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
<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
document.querySelector("#sidebar").classList.toggle("expand");
});
</script>
</html>