<?php 

require '../api/db-connect.php';

$Student_user = $_SESSION['stud_fname'];
if(isset($_SESSION['program_id']) && isset($_SESSION['year_id'])) {
    $program_id = $_SESSION['program_id'];
    $year_id = $_SESSION['year_id'];

    // Prepare SQL query to fetch courses for the given program and year
    $sql = "SELECT * FROM tbl_course WHERE program_id = :program_id AND year_id = :year_id AND sem_id = 1";
    $result = $conn->prepare($sql);
    $result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $result->bindParam(':year_id', $year_id, PDO::PARAM_INT);
    $result->execute();

    // Fetch the result and store it in a variable to use later
    $courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect to login page if session data is not set
    header("Location: ../login.php");
    exit();
}


// Check if stud_id and course_id are set
if(isset($_SESSION['stud_id']) && isset($_GET['course_id'])) {
    $stud_id = $_SESSION['stud_id'];
    $course_id = $_GET['course_id'];

    // Define pagination variables
    $recordsPerPage = 5;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $recordsPerPage;

    // Define search term
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Prepare SQL query to fetch student scores for the specified course
    $sql = "SELECT tbl_result.result_score, tbl_module.course_id, tbl_module.module_number, tbl_module.module_name
            FROM tbl_result
            INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
            WHERE tbl_result.stud_id = :stud_id AND tbl_module.course_id = :course_id";

    // Prepare and execute the query
    $result = $conn->prepare($sql);
    $result->bindParam(':stud_id', $stud_id);
    $result->bindParam(':course_id', $course_id);
    $result->execute();

    // Fetch all results
    $results = $result->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records
    $countSql = "SELECT COUNT(*) as total FROM tbl_result 
                 INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
                 WHERE tbl_result.stud_id = :stud_id AND tbl_module.course_id = :course_id";

    // Add search condition if applicable
    if (!empty($search)) {
        $countSql .= " AND (stud_lname LIKE '%$search%' OR stud_fname LIKE '%$search%')";
    }

    // Prepare and execute the count query
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':stud_id', $stud_id);
    $countStmt->bindParam(':course_id', $course_id);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCount / $recordsPerPage);
} else {
    // Handle case when stud_id or course_id is not set
    // Redirect or display an error message
}
?>
<aside id="sidebar">
    <div class="d-flex">
        <button class="toggle-btn" type="button">
            <i class="lni lni-grid-alt"></i>
        </button>
        <div class="sidebar-logo mt-3">
        <h6> <a href="dashboard.php">Hello, <?php echo $Student_user ?>!<p style="text-align: center;font-size:13px;"><?php echo 'Student'; ?></p>
            </h6>  </div>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link">
                <i class="lni lni-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>


        <li class="sidebar-item">
            <a href="profile.php" class="sidebar-link">
                <i class="lni lni-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#auth" aria-expanded="false" aria-controls="auth">
                <i class="lni lni-agenda"></i>
                <span>Course</span>
            </a>
            <?php if (!empty($courses)) : ?>
                <ul id="auth" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <?php foreach ($courses as $row) : ?>
                        <li class="sidebar-item">
                            <a href="module.php?course_id=<?php echo $row['course_id']; ?>" class="sidebar-link"><?php echo $row['course_name']; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </li>
        <li class="sidebar-item">
            <a href="report.php" class="sidebar-link">
                <i class="lni lni-popup"></i>
                <span>Report</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-link">
            <i class="lni lni-exit"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>