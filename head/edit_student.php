<?php
session_start();

require("../api/db-connect.php");

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update'])) {
    $stud_id = $_POST['stud_id'];
    $program_id = $_SESSION['program_id'];
    $year_id = $_POST['year_id'];
    $stud_no = $_POST['stud_no'];
    $stud_fname = $_POST['stud_fname'];
    $stud_mname = $_POST['stud_mname'];
    $stud_lname = $_POST['stud_lname'];
    $stud_password = $_POST['stud_password'];

    if (empty($year_id) || empty($program_id) || empty($stud_no) || empty($stud_fname) || empty($stud_mname) || empty($stud_lname) ||  empty($stud_password)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input all fields.",
                    icon: "error"
                }).then(() => {
                    window.location.href = "student.php";
                });
            });
        </script>';
    } else {
        $sql = "UPDATE `tbl_student` SET 
        program_id = :program_id,
        year_id = :year_id,
        stud_no = :stud_no,
        stud_fname = :stud_fname,
        stud_mname = :stud_mname,
        stud_lname = :stud_lname,
        stud_password = :stud_password
        WHERE stud_id = :stud_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":program_id", $program_id);
        $stmt->bindParam(":year_id", $year_id);
        $stmt->bindParam(":stud_no", $stud_no);
        $stmt->bindParam(":stud_fname", $stud_fname);
        $stmt->bindParam(":stud_mname", $stud_mname);
        $stmt->bindParam(":stud_lname", $stud_lname);
        $stmt->bindParam(":stud_password", $stud_password);
        $stmt->bindParam(":stud_id", $stud_id);

        if ($stmt->execute()) {
            echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                        $(document).ready(function(){
                            Swal.fire({
                                title: "Success!",
                                text: "Student updated successfully.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "student.php";
                            });
                        });
                    </script>';
        } else {
            echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to update student.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "student.php";
                        });
                    });
                    </script>';
        }
    }
}

if (isset($_GET['stud_id'])) {
    $stud_id = $_GET['stud_id'];
    $sql = "SELECT * FROM tbl_student WHERE stud_id = :stud_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":stud_id", $stud_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $stud_id = $row['stud_id'];
        $year_id = $row['year_id'];
        $stud_no = $row['stud_no'];
        $stud_fname = $row['stud_fname'];
        $stud_mname = $row['stud_mname'];
        $stud_lname = $row['stud_lname'];
        $stud_password = $row['stud_password'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
</head>

<body>
    <div class="wrapper">
    <?php
        include 'sidebar.php';
        ?>
        <div class="main py-3">
            <div class="text-center mb-4">
                <h1>Edit Student</h1>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <form action="edit_student.php" method="post">
                            <!-- Year Select -->
                            <div class="mb-3">
                                <label for="year_id" class="form-label">Year</label>
                                <select class="form-select" id="year_id" name="year_id">
                                    <?php
                                    $sqlYear = "SELECT year_id, year_level FROM tbl_year";
                                    $stmtYear = $conn->prepare($sqlYear);
                                    $stmtYear->execute();
                                    $years = $stmtYear->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($years as $year) {
                                        $selected = ($year_id == $year['year_id']) ? "selected" : "";
                                        echo "<option value='" . $year['year_id'] . "' $selected>" . $year['year_level'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Number Input -->
                            <div class="mb-3">
                                <label for="stud_no" class="form-label">Student No.</label>
                                <input type="text" class="form-control" id="stud_no" name="stud_no" value="<?php echo $stud_no; ?>" required>
                            </div>

                            <!-- First Name Input -->
                            <div class="mb-3">
                                <label for="stud_fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="stud_fname" name="stud_fname" value="<?php echo $stud_fname; ?>" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Middle Name Input -->
                            <div class="mb-3">
                                <label for="stud_mname" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="stud_mname" name="stud_mname" value="<?php echo $stud_mname; ?>" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Last Name Input -->
                            <div class="mb-3">
                                <label for="stud_lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="stud_lname" name="stud_lname" value="<?php echo $stud_lname; ?>" pattern="[A-Za-z]+" title="Please enter only alphabetic characters" required>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="stud_password" class="form-label">Password</label>
                                <div class="password-input-container">
                                    <input type="password" class="form-control" id="stud_password" name="stud_password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 12 or more characters" value="<?php echo $stud_password; ?>" required>
                                    <span class="toggle-password" onclick="togglePasswordVisibility()"><i class="far fa-eye-slash"></i></span>
                                </div>
                            </div>
                            <!-- Hidden Employee ID and Submit Button -->
                            <input type="hidden" name="stud_id" value="<?php echo $stud_id; ?>">
                            <input type="submit" class="btn btn-success mt-2" value="Update" name="update">
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    ::after,
    ::before {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    a {
        text-decoration: none;
    }

    li {
        list-style: none;
    }

    h1 {
        font-weight: 600;
        font-size: 1.5rem;
    }

    body {
        font-family: 'Poppins', sans-serif;
    }

    .wrapper {
        display: flex;
    }

    .main {
        min-height: 100vh;
        width: 100%;
        overflow: hidden;
        transition: all 0.35s ease-in-out;
        background-color: #fafbfe;
    }

    #sidebar {
        width: 70px;
        min-width: 70px;
        z-index: 1000;
        transition: all .25s ease-in-out;
        background-color: #0e2238;
        display: flex;
        flex-direction: column;
    }

    #sidebar.expand {
        width: 260px;
        min-width: 260px;
    }

    .toggle-btn {
        background-color: transparent;
        cursor: pointer;
        border: 0;
        padding: 1rem 1.5rem;
    }

    .toggle-btn i {
        font-size: 1.5rem;
        color: #FFF;
    }

    .sidebar-logo {
        margin: auto 0;
    }

    .sidebar-logo a {
        color: #FFF;
        font-size: 1.15rem;
        font-weight: 600;
    }

    #sidebar:not(.expand) .sidebar-logo,
    #sidebar:not(.expand) a.sidebar-link span {
        display: none;
    }

    .sidebar-nav {
        padding: 2rem 0;
        flex: 1 1 auto;
    }

    a.sidebar-link {
        padding: .625rem 1.625rem;
        color: #FFF;
        display: block;
        font-size: 0.9rem;
        white-space: nowrap;
        border-left: 3px solid transparent;
    }

    .sidebar-link i {
        font-size: 1.1rem;
        margin-right: .75rem;
    }

    a.sidebar-link:hover {
        background-color: rgba(255, 255, 255, .075);
        border-left: 3px solid #3b7ddd;
    }

    .sidebar-item {
        position: relative;
    }

    #sidebar:not(.expand) .sidebar-item .sidebar-dropdown {
        position: absolute;
        top: 0;
        left: 70px;
        background-color: #0e2238;
        padding: 0;
        min-width: 15rem;
        display: none;
    }

    #sidebar:not(.expand) .sidebar-item:hover .has-dropdown+.sidebar-dropdown {
        display: block;
        max-height: 15em;
        width: 100%;
        opacity: 1;
    }

    #sidebar.expand .sidebar-link[data-bs-toggle="collapse"]::after {
        border: solid;
        border-width: 0 .075rem .075rem 0;
        content: "";
        display: inline-block;
        padding: 2px;
        position: absolute;
        right: 1.5rem;
        top: 1.4rem;
        transform: rotate(-135deg);
        transition: all .2s ease-out;
    }

    #sidebar.expand .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
        transform: rotate(45deg);
        transition: all .2s ease-out;
    }

    .form-container {
        max-width: 400px;
        margin: 0 auto;
        background-color: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0px 0px 15px 0px rgba(0, 0, 0, 0.1);
    }

    .form-container label {
        font-weight: bold;
    }

    .form-control {
        border-radius: 5px;
    }

    .btn-custom {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
        border-radius: 5px;
    }

    .btn-custom:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    .password-input-container {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        bottom: 0;
        right: 10px;
        cursor: pointer;
        margin-bottom: 6px;
    }
</style>
<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });

    function togglePasswordVisibility() {
        var passwordInput = document.getElementById("stud_password");
        var icon = document.querySelector('.toggle-password i');

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            passwordInput.type = "password";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
</script>

</html>