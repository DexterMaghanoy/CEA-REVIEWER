<?php
session_start();
require("../api/db-connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Toggle status if form is submitted
if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    $stmt = $conn->prepare("SELECT user_status FROM tbl_user WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['user_status'] == 1 ? 0 : 1;

    $updateStmt = $conn->prepare("UPDATE tbl_user SET user_status = :new_status WHERE user_id = :user_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $updateStmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$recordsPerPage = 7;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$sql = "SELECT u.*, t.type_name, p.program_name
        FROM tbl_user AS u
        JOIN tbl_type AS t ON u.type_id = t.type_id
        JOIN tbl_program AS p ON u.program_id = p.program_id
        ORDER BY u.user_status DESC, t.type_id ASC
        LIMIT :offset, :recordsPerPage";

$result = $conn->prepare($sql);
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

$countSql = "SELECT COUNT(*) as total FROM tbl_user";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php include 'back.php'; ?>
        <div class="container mt-2">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="text-center mt-3">
                        <h1>User Accounts</h1>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="add_faculty.php"><i class="lni lni-plus"></i></a><br><br>

                    <!-- Live Search -->
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" id="liveSearch" class="form-control" placeholder="Search...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom" id="userTable">
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
                                                        <i class="lni lni-checkmark-circle"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        // Sidebar toggle
        const hamBurger = document.querySelector(".toggle-btn");
        hamBurger.addEventListener("click", function() {
            document.querySelector("#sidebar").classList.toggle("expand");
        });

        // Live search filtering
        document.getElementById("liveSearch").addEventListener("keyup", function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll("#userTable tbody tr");

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(query) ? "" : "none";
            });
        });
    </script>
</body>

</html>