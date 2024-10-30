<?php
$Student_user = $_SESSION['stud_fname'];
$stud_id = $_SESSION['stud_id'];
require '../api/db-connect.php';
?>

<link rel="stylesheet" href="./css/topNavBar.css">
<script defer src="./scripts/topNavBar.js"></script>

<nav class="navbar bg-primary fixed-top" style="box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);">
    <div class="container-fluid">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link">
                <i class="lni lni-home"></i>
            </a>
        </li>
        <li title="Profile" class="sidebar-item">
            <a href="profile.php" class="sidebar-link">
                <i class="lni lni-user"></i>
            </a>
        </li>

        <li title="Report" class="sidebar-item">
            <a href="report_questions.php" class="sidebar-link">
                <i class="fa-regular fa-newspaper"></i>
            </a>
        </li>

        <?php
        try {
            // Prepare SQL query to count distinct subjects
            $Subjectsql = "SELECT COUNT(DISTINCT c.course_id) AS total_courses
            FROM tbl_course c
            WHERE c.program_id = :program_id";

            // Define parameters for the query
            $params = [
                ':program_id' => 1 // Example program_id, replace with your logic
            ];

            // Execute query
            $topNavBarstmt = $conn->prepare($Subjectsql);
            $topNavBarstmt->execute($params);

            // Fetch the total number of courses
            $TopNavBartotalCourses = $topNavBarstmt->fetchColumn();

            // Prepare SQL query to count distinct subjects with result_status = 1
            $topNavBarQuizsql = "SELECT COUNT(DISTINCT r.course_id) AS exam_count
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
            $topNavBarQuizstmt = $conn->prepare($topNavBarQuizsql);
            $topNavBarQuizstmt->execute($params);

            // Fetch the count of courses with result_status = 1
            $TopNavBarexamCount = $topNavBarQuizstmt->fetchColumn();

            // Check if all courses have result_status = 1
            $allCoursesCompleted = ($TopNavBarexamCount == $TopNavBartotalCourses);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $TopNavBarsql = "SELECT 
        COUNT(*) AS quiz_type_3_count
    FROM 
        tbl_result r
    JOIN 
        tbl_module m ON r.module_id = m.module_id
    JOIN 
        tbl_course c ON r.course_id = c.course_id
    WHERE 
        r.stud_id = :stud_id
        AND c.program_id = :program_id
        AND r.quiz_type = 3
        AND r.result_status = 1";

        $TopNavBarstmt = $conn->prepare($TopNavBarsql);
        $TopNavBarstmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
        $TopNavBarstmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
        $TopNavBarstmt->execute();
        $resultTopBar = $TopNavBarstmt->fetch(PDO::FETCH_ASSOC);
        $top_bar_quiz_type_3_count = $resultTopBar['quiz_type_3_count'];
        ?>

        <li title="Exam" class="nav-item">
            <?php if ($top_bar_quiz_type_3_count > 0) : ?>
                <a class="active" style="color: lightgreen; cursor: pointer;" onclick="showAlertDone();">
                    <i class="lni lni-pencil-alt"></i>
                    <span style="color: lightgreen;">Exam</span>
                </a>

            <?php elseif ($allCoursesCompleted) : ?>
                <a class="active" style="color: black; cursor: pointer;" onclick="showAlertProceed();">
                    <i class="lni lni-pencil-alt" style="color: black;"></i>
                    <span>Exam</span>
                </a>

            <?php else : ?>
                <a class="active" style="color: red; cursor: pointer;" onclick="showAlert();">
                    <i class="lni lni-pencil-alt" style="color: red;"></i>
                    <span>Exam</span>
                </a>
            <?php endif; ?>
        </li>

        <li class="navbar-toggler custom-toggler btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </li>

        <div style="background: linear-gradient(to right, #E6F2F8, #FFFFFF);" class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
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

            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">
                    <a href="dashboard.php">Hello, <?php echo htmlspecialchars($Student_user); ?>!
                        <p style="font-size: 13px; color: black;"><?php echo htmlspecialchars($programName_students) . "  " . " Student"; ?></p>
                    </a>
                </h5>
                <button style="margin-bottom: 15px; margin-right: 10px" type="button" class="btn-close btn-close-red" data-bs-dismiss="offcanvas" aria-label="Close Navigation Menu">
                </button>
            </div>

            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item">
                        <a class="active" href="dashboard.php">
                            <i class="fa fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="active" href="profile.php">
                            <i class="fa-solid fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>

                    <li class="nav-item subjects-container">
                        <a class="active" href="#" data-bs-toggle="collapse" data-bs-target="#auth" aria-expanded="false" aria-controls="auth">
                            <i class="fa-solid fa-book"></i>
                            <span>Subjects</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </a>
                        <ul id="auth" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                            <?php
                            // Fetch courses from the database
                            $subjectSql = "SELECT course_id, course_name FROM tbl_course WHERE program_id = :program_id";
                            $Subjectstmt = $conn->prepare($subjectSql);
                            $Subjectstmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
                            $Subjectstmt->execute();
                            $Subjectcourses = $Subjectstmt->fetchAll(PDO::FETCH_ASSOC);

                            $processedCourseIDs = [];
                            foreach ($Subjectcourses as $subject_row) :
                                if (!in_array($subject_row['course_id'], $processedCourseIDs)) :
                                    $processedCourseIDs[] = $subject_row['course_id'];
                            ?>
                                    <li class="sidebar-item">
                                        <a style="color:black;" href="module.php?course_id=<?php echo htmlspecialchars($subject_row['course_id']); ?>" class="sidebar-link"><?php echo htmlspecialchars(' 🔵 ' . $subject_row['course_name']); ?></a>
                                    </li>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </ul>
                    </li>

                    <?php
                    try {
                        // Prepare SQL query to count distinct subjects
                        $CountSubjectsql = "SELECT COUNT(DISTINCT c.course_id) AS total_courses
                FROM tbl_course c
                WHERE c.program_id = :program_id";

                        // Define parameters for the query
                        $params = [
                            ':program_id' => 1 // Example program_id, replace with your logic
                        ];

                        // Execute query
                        $stmt = $conn->prepare($CountSubjectsql);
                        $stmt->execute($params);

                        // Fetch the total number of courses
                        $TopNavBartotalCourses = $stmt->fetchColumn();

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
                        $allCoursesCompleted = ($examCount == $TopNavBartotalCourses);
                    } catch (PDOException $e) {
                        echo "Error: " . $e->getMessage();
                    }

                    $sql = "SELECT 
                COUNT(*) AS quiz_type_3_passed_count
            FROM 
                tbl_result r
            JOIN 
                tbl_module m ON r.module_id = m.module_id
            JOIN 
                tbl_course c ON r.course_id = c.course_id
            WHERE 
                r.stud_id = :stud_id
                AND c.program_id = :program_id
                AND r.quiz_type = 3
                AND r.result_status = 1";

                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
                    $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $resultTopBar = $stmt->fetch(PDO::FETCH_ASSOC);

                    $top_bar_quiz_type_3_count = $resultTopBar['quiz_type_3_passed_count'];


                    ?>

                    <li title="Exam" class="nav-item">
                        <?php if ($top_bar_quiz_type_3_count > 0) : ?>
                            <a class="active" style="color: green; cursor: pointer;" onclick="showAlertDone();">
                                <i class="lni lni-pencil-alt"></i>
                                <span>Exam</span>
                            </a>


                        <?php elseif ($allCoursesCompleted) : ?>


                            <a class="active" style="color: black; cursor: pointer;" onclick="showAlertProceed();">
                                <i class="lni lni-pencil-alt"></i>
                                <span>Exam</span>
                            </a>
                        <?php else : ?>
                            <a class="active" style="color: red; cursor: pointer;" onclick="showAlert();">
                                <i class="lni lni-pencil-alt"></i>
                                <span>Exam</span>
                            </a>
                        <?php endif; ?>
                    </li>

                    <li title="Report" class="active">
                        <a class="active" href="report_questions.php">
                            <i class="fa-regular fa-newspaper"></i>
                            <span>Report</span>
                        </a>
                    </li>

                    <li title="Report" class="active mb-5">
                        <a class="active" href="leaderboards-tests.php">
                            <i class="lni lni-bar-chart"></i>
                            <span>Leaderboards</span>
                        </a>
                    </li>


                </ul>


                <li class="mt-5">
                    <a href="../logout.php" class="active">
                        <i class="lni lni-exit"></i>
                        <span>Logout</span>
                    </a>
                </li>

            </div>



        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const subjectsLink = document.querySelector('.subjects-container > a');
        const examItem = document.getElementById('examItem');
        const navList = examItem.parentElement;

        subjectsLink.addEventListener('click', function() {
            setTimeout(function() {
                if (subjectsLink.getAttribute('aria-expanded') === 'true') {
                    navList.appendChild(examItem);
                }
            }, 300); // wait for the collapse animation to complete
        });
    });
</script>


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

    function showAlertDone() {
        Swal.fire({
            title: "Exam Completed",
            text: "You have already passed the exam. Please proceed to the reports page to view your records.",
            icon: "success"
        }).then(() => {
            window.location.href = "dashboard.php";
        });
    }
</script>

<script>
    console.log(<?php echo json_encode($resultTopBar); ?>);
</script>