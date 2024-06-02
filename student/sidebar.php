<?php
$Student_user = $_SESSION['stud_fname'];
$stud_id = $_SESSION['stud_id'];
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

            <?php
            // Prepare the query to fetch user details
            $user_ProgName = $conn->prepare("SELECT s.*, p.program_name
                FROM tbl_student s
                INNER JOIN tbl_program p ON s.program_id = p.program_id
                WHERE s.stud_id = :stud_id");
            $user_ProgName->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
            $user_ProgName->execute();

            // Fetch user details
            if ($user_ProgName->rowCount() > 0) {
                $userProgName = $user_ProgName->fetch(PDO::FETCH_ASSOC);
                $programName_students = $userProgName['program_name'];
            } else {
                $programName_students = 'Unknown Program';
            }
            ?>
            <h6>
                <a href="dashboard.php">Hello, <?php echo htmlspecialchars($Student_user); ?>!
                    <p style="text-align: center; font-size: 13px;"><?php echo htmlspecialchars($programName_students) . "  " . " Student"; ?></p>
                </a>
            </h6>
        </div>
    </div>
    <ul title="Dashboard"  class="sidebar-nav">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link">
                <i class="lni lni-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li title="Profile"  class="sidebar-item">
            <a href="profile.php" class="sidebar-link">
                <i class="lni lni-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li  title="Subjects" class="sidebar-item">
            <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse" data-bs-target="#auth" aria-expanded="false" aria-controls="auth">
                <i class="lni lni-agenda"></i>
                <span>Subjects</span>
            </a>
            <?php
            $displayedCourseIDs = [];
            if (!empty($courses)) :
            ?>
                <ul id="auth" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                    <?php foreach ($courses as $row) :

                        if (!in_array($row['course_id'], $displayedCourseIDs)) :
                            $displayedCourseIDs[] = $row['course_id'];
                    ?>
                            <li class="sidebar-item">
                                <a href="module.php?course_id=<?php echo htmlspecialchars($row['course_id']); ?>" class="sidebar-link"><?php echo htmlspecialchars($row['course_name']); ?></a>
                            </li>
                    <?php endif;
                    endforeach; ?>
                </ul>
            <?php endif; ?>
        </li>
        <?php
        try {
            // Prepare SQL query to count distinct subjects
            $sql = "SELECT COUNT(DISTINCT c.course_id) AS total_courses
            FROM tbl_course c
            WHERE c.program_id = :program_id";

            // Define parameters for the query
            $params = [
                ':program_id' => 1 // Example program_id, replace with your logic
            ];

            // Execute query
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Fetch the total number of courses
            $totalCourses = $stmt->fetchColumn();

            // Prepare SQL query to count distinct subjects with result_status = 1
            $sql = "SELECT COUNT(DISTINCT r.course_id) AS exam_count
            FROM tbl_result r
            JOIN tbl_module m ON r.module_id = m.module_id
            JOIN tbl_course c ON r.course_id = c.course_id
            WHERE r.stud_id = :stud_id
            AND c.program_id = :program_id
            AND r.quiz_type = 2
            AND r.result_status = 1";

            // Define parameters for the query
            $params = [
                ':stud_id' => $stud_id,
                ':program_id' => 1 // Example program_id, replace with your logic
            ];

            // Execute query
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Fetch the count of courses with result_status = 1
            $examCount = $stmt->fetchColumn();

            // Check if all courses have result_status = 1
            $allCoursesCompleted = ($examCount == $totalCourses);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>
        <li title="Exam"  class="sidebar-item">
            <?php if ($allCoursesCompleted) : ?>
                <a class="sidebar-link" style="color: green;" onclick="showAlertProceed();">
                    <i class="lni lni-pencil-alt"></i>
                    <span>Exam</span>
                </a>

            <?php else : ?>
                <a class="sidebar-link" style="color: red; cursor: pointer;" onclick="showAlert();">
                    <i class="lni lni-pencil-alt"></i>
                    <span>Exam</span>
                </a>
            <?php endif; ?>
        </li>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">


        <script>
            function showAlertProceed() {
                $(document).ready(function() {
                    Swal.fire({
                        title: "Prepare for the Exam",
                        text: "Best of luck with your exam! Remember to choose the best answers.",
                        icon: "info"
                    }).then(() => {
                        window.location.href = "exam.php";
                    });
                });
            }
        </script>


        <script>
            function showAlert() {
                $(document).ready(function() {
                    Swal.fire({
                        title: "Incomplete Requirements",
                        text: "Please complete all quizzes before attempting the exam.",
                        icon: "error"
                    }).then(() => {
                        window.location.href = "dashboard.php";
                    });
                });
            }
        </script>




        <li title="Report" class="sidebar-item">
            <a href="report_questions.php" class="sidebar-link">
                <i class="lni lni-popup"></i>
                <span>Report</span>
            </a>
        </li>
    </ul>
    <div title="Logout" class="sidebar-footer">
        <a href="../logout.php" class="sidebar-link">
            <i class="lni lni-exit"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>


<script>
    // Disable right-click context menu
    document.addEventListener('contextmenu', function(event) {
        event.preventDefault();
    });
</script>


