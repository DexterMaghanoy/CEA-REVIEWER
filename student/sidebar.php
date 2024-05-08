<?php

$Student_user = $_SESSION['stud_fname'];
require '../api/db-connect.php';

?>
<style>
    .custom-alert {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff;
        border: 1px solid #ccc;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        z-index: 9999;
    }

    .custom-alert h2 {
        margin-top: 0;
        font-size: 24px;
        color: #333;
    }

    .custom-alert p {
        font-size: 16px;
        color: #555;
    }

    .custom-alert button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 5px;
    }
</style>



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
            <a href="exam.php" class="sidebar-link" onclick="showAlert()">
                <i class="lni lni-pencil-alt"></i>
                <span>Exam</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="report_questions.php" class="sidebar-link">
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

<script>
    function showAlert() {
        // if (1 == 1) {
        //     alert("Exam Unavailable"); // Alert message
        //     window.location.href = 'exam.php'; // Redirect to exam.php
        // } else {
        //     window.location.href = 'index.php'; // Redirect to index.php
        // }
    }
</script>