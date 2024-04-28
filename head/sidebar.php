<?php 
    $user1 = $_SESSION['user_fname'];
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
            <a href="profile.php" class="sidebar-link">
                <i class="lni lni-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="faculty.php" class="sidebar-link">
                <i class="lni lni-agenda"></i>
                <span>Faculty</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="student.php" class="sidebar-link">
                <i class="lni lni-graduation"></i>
                <span>Student</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="course.php" class="sidebar-link">
                <i class="lni lni-library"></i>
                <span>Course</span>
            </a>
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