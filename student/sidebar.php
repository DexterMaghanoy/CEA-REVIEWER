<?php
$user_id = $_SESSION['stud_id'];
// Use JOIN to get user_type and course_name from related tables
$sql = "SELECT s.*, y.year_level, p.program_name FROM tbl_student s INNER JOIN tbl_year y ON s.year_id = y.year_id INNER JOIN tbl_program p ON s.program_id = p.program_id WHERE s.stud_id = :stud_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':stud_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if the query was successful and if there is a user with the given emp_id
if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data
}
?>

<aside id="sidebar">
    <div class="d-flex">
        <button class="toggle-btn" type="button">
            <i class="lni lni-grid-alt"></i>
        </button>
        <div class="sidebar-logo mt-3">
            <h6>
                <a href="dashboard.php">Hello, <?php echo $user['stud_fname'] ?>!<p style="text-align: center;font-size:13px;"><?php echo $user['year_level']; ?></p>
            </h6>
        </a>
    </div>
</div>
<ul class="sidebar-nav">
    <li class="sidebar-item">
        <a href="profile.php" class="sidebar-link">
            <i class="lni lni-user"></i>
            <span>Profile</span>
        </a>
    </li>
    <li class="sidebar-item">
        <a href="module.php" class="sidebar-link">
            <i class="lni lni-book"></i>
            <span>Course</span>
        </a>
    <li class="sidebar-item">
        <a href="report.php" class="sidebar-link">
            <i class="lni lni-agenda"></i>
            <span>Report</span>
        </a>
    </li>
</ul>
<div class="sidebar-footer">
    <a href="logout.php" class="sidebar-link">
        <i class="lni lni-exit"></i>
        <span>Logout</span>
    </a>
</div>
</aside>