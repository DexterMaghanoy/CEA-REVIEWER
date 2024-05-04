<?php
$Student_user = $_SESSION['stud_fname'];


?>

<aside id="sidebar">
    <div class="d-flex">
        <button class="toggle-btn" type="button">
            <i class="lni lni-grid-alt"></i>
        </button>
        <div class="sidebar-logo mt-3">
            <h6> <a href="dashboard.php">Hello, <?php echo $Student_user ?>!<p style="text-align: center;font-size:13px;"><?php echo 'Student'; ?></p>
            </h6>
        </div>
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
            <a href="exam.php" class="sidebar-link">
                <i class="lni lni-pencil-alt"></i>
                <span>Exam</span>
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