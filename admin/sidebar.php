<?php

if (!isset($_SESSION['original_user_id'])) {
    $_SESSION['original_user_id'] = $_SESSION['user_id'];
}


$user1 = $_SESSION['user_fname'];
$sidebar_user_id = $_SESSION['original_user_id'];
$sql = "SELECT u.*, t.type_name, p.program_name
            FROM tbl_user u
            INNER JOIN tbl_type t ON u.type_id = t.type_id
            INNER JOIN tbl_program p ON u.program_id = p.program_id
            WHERE u.user_id = :user_id";
$stmtSidebar = $conn->prepare($sql);
$stmtSidebar->bindParam(':user_id', $sidebar_user_id, PDO::PARAM_INT);
$stmtSidebar->execute();

if ($stmtSidebar->rowCount() > 0) {
    $user = $stmtSidebar->fetch(PDO::FETCH_ASSOC);
}
?>


<aside id="sidebar">
    <div class="d-flex">
        <button class="toggle-btn" type="button">
            <i class="lni lni-grid-alt"></i>
        </button>
        <div class="sidebar-logo mt-3">
            <h6>
                <a href="index.php">Hello, <?php echo htmlspecialchars($user['user_fname']); ?>!</a>
                <p style="text-align: center; font-size: 13px; color: white;">
                    <?php echo htmlspecialchars($user['type_name']); ?>
                </p>
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
            <a href="subjects.php" class="sidebar-link">
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