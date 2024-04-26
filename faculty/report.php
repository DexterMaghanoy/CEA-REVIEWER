<?php 
session_start();

require '../api/db-connect.php';

// Pagination
$resultsPerPage = 10; // Number of results per page
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];

    // Query with search functionality
    $sql = "SELECT s.stud_fname, s.stud_mname, s.stud_lname, c.course_name, m.module_name, SUM(r.result_score) AS total_score
            FROM tbl_result r
            INNER JOIN tbl_student s ON r.stud_id = s.stud_id
            INNER JOIN tbl_module m ON r.module_id = m.module_id
            INNER JOIN tbl_course c ON m.course_id = c.course_id
            WHERE r.user_id = :user_id ";

    if (!empty($search)) {
        $sql .= "AND (s.stud_fname LIKE :search OR s.stud_lname LIKE :search OR c.course_name LIKE :search OR m.module_name LIKE :search) ";
    }

    $sql .= "GROUP BY s.stud_id, c.course_id ";

    // Count total number of results for pagination
    $countQuery = "SELECT COUNT(*) AS count FROM ($sql) AS sub";
    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if (!empty($search)) {
        $stmtCount->bindValue(':search', '%' . $search . '%');
    }
    $stmtCount->execute();
    $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $totalCount = $countResult['count'];
    $totalPages = ceil($totalCount / $resultsPerPage);

    // Add pagination to the main query
    $sql .= " LIMIT $resultsPerPage OFFSET $offset";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Student Report</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
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
                            <h1>Student Report</h1>
                        </div>
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered border-secondary">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Student</th>
                                        <th scope="col">Course</th>
                                        <th scope="col">Total Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($result)): ?>
                                        <?php foreach ($result as $row): ?>
                                            <tr>
                                                <td><?php echo $row['stud_lname'] . ', ' . $row['stud_fname']?></td>
                                                <td><?php echo $row['course_name']; ?></td>
                                                <td><?php echo $row['total_score']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No records found for students.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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