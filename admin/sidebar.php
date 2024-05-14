<?php
$user1 = $_SESSION['user_fname'];
$user_id = $_SESSION['user_id'];

// Use JOIN to get user_type and course_name from related tables
$sql = "SELECT u.*, t.type_name, p.program_name
            FROM tbl_user u
            INNER JOIN tbl_type t ON u.type_id = t.type_id
            INNER JOIN tbl_program p ON u.program_id = p.program_id
            WHERE u.user_id = :user_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
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
            <h6> <a href="index.php">Hello, <?php echo $user1 ?>!<p style="text-align: center;font-size:13px;"><?php echo $user['type_name']; ?></p>
            </h6>
            </a>
        </div>
    </div>



    <ul class="sidebar-nav">
        <li class="sidebar-item">
            <a href="index.php" class="sidebar-link">
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
            <a href="user.php" class="sidebar-link">
                <i class="lni lni-users"></i>
                <span>Users</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="student.php" class="sidebar-link">
                <i class="lni lni-graduation"></i>
                <span>Students</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="program.php" class="sidebar-link">
                <i class="lni lni-library"></i>
                <span>Courses</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="course.php" class="sidebar-link">
                <i class="lni lni-book"></i>
                <span>Subjects</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="report.php" class="sidebar-link">
                <i class="lni lni-popup"></i>
                <span>Reports</span>
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