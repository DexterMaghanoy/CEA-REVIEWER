<?php
require("../api/db-connect.php");
session_start(); // Start the session

if (!isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['update'])) {
    // Sanitize input values
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
    $program_id = $_SESSION['program_id'];
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $course_code = filter_input(INPUT_POST, 'course_code', FILTER_SANITIZE_STRING);
    $course_name = filter_input(INPUT_POST, 'course_name', FILTER_SANITIZE_STRING);

    // Validate input data
    if (empty($user_id) || empty($course_code) || empty($course_name)) {
        echo '<script>alert("Please input all fields.");</script>';
    } else {
        try {
            // Prepare and execute the SQL query using prepared statements
            $sql = "UPDATE `tbl_course` SET 
                    user_id = :user_id,
                    course_code = :course_code,
                    course_name = :course_name
                    WHERE course_id = :course_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":course_code", $course_code);
            $stmt->bindParam(":course_name", $course_name);
            $stmt->bindParam(":course_id", $course_id);
            if ($stmt->execute()) {
                // Redirect after successful update
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Subject updated successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "subjects.php";
                            });
                        });
                    </script>';
            } else {
                echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Failed!",
                        text: "Failed to update Subject.",
                        icon: "error"
                    }).then(() => {
                        window.location.href = "subjects.php";
                    });
                });
                </script>';
            }
        } catch (PDOException $e) {
            echo '<script>alert("Database Error: ' . $e->getMessage() . '");</script>';
        }
    }
}

// Retrieve course data for editing
if (isset($_GET['course_id'])) {
    $course_id = filter_input(INPUT_GET, 'course_id', FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT * FROM tbl_course WHERE course_id = :course_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":course_id", $course_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $course_id = $row['course_id'];
        $user_id = $row['user_id'];
        $course_code = $row['course_code'];
        $course_name = $row['course_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>


        <div class="container">

            <div class="text-center mt-4 mb-4">
                <?php
                $courseSql = "SELECT course_name FROM tbl_course WHERE course_id = :course_id";
                $courseStmt = $conn->prepare($courseSql);
                $courseStmt->bindParam(':course_id', $_GET['course_id'], PDO::PARAM_INT);
                $courseStmt->execute();
                $SubjectName = $courseStmt->fetch(PDO::FETCH_ASSOC);

                // Check if course name is fetched successfully
                if ($SubjectName) {
                    $courseName = $SubjectName['course_name'];
                } else {
                    $courseName = "Unknown Course"; // Default value if course name not found
                }
                ?>

                <h1>Edit Subject: <span style="font-weight: normal;"><?php echo htmlspecialchars($courseName); ?></span></h1>


            </div>
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <form action="edit_course.php" method="post">
                        <!-- Faculty Select -->
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Faculty</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <?php
                                $sqlUser = "SELECT user_id, user_fname, user_lname, user_mname FROM tbl_user WHERE program_id = :program_id AND type_id = 3";
                                $stmtUser = $conn->prepare($sqlUser);
                                $stmtUser->bindParam(":program_id", $_SESSION['program_id']);
                                $stmtUser->execute();
                                $users = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($users as $user) {
                                    $selected = ($user_id == $user['user_id']) ? "selected" : "";
                                    echo "<option value='" . $user['user_id'] . "' $selected>" . $user['user_lname'] . ', ' . $user['user_fname'] . ' ' . $user['user_mname'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Course Code Input -->
                        <div class="mb-3">
                            <label for="course_code" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo $course_code; ?>" required>
                        </div>

                        <!-- Course Name Input -->
                        <div class="mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo $course_name; ?>" required>
                        </div>

                        <!-- Hidden Course ID and Submit Button -->
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>

</html>