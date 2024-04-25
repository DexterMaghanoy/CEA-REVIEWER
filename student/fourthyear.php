<?php 
session_start();

require '../api/db-connect.php';

if(isset($_SESSION['program_id'])){
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../student/4thyr.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
</head>
<body>
    <div class="wrapper">
        <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <i class="lni lni-grid-alt"></i>
                </button>
                <div class="sidebar-logo">
                    <a href="#">Dashboard</a>
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
                    <a href="report.php" class="sidebar-link">
                        <i class="lni lni-popup"></i>
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
        <div class="main p-3">
            <div class="text-center">
                <h1>
                    Dashboard
                </h1>
            </div>
            <div class="container mt-5">
            <div class="row">
    
    <style>
    
    .card-bg1 {
        background-image: url('https://www.gstatic.com/classroom/themes/Honors.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px; 
        width: 85%;
        
    }
    .card-bg2 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_bookclub.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px;
        width: 85%;
    }
    .card-bg3 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_breakfast.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px;
        width: 85%; 
    }
    .card-bg4 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_backtoschool.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px; 
        width: 85%;
    }
    .card-bg5 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_code.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px; 
        width: 85%; 
    }
    .card-bg6 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_graduation.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px; 
        width: 85%;
    }
    .card-bg7 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_reachout.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px; 
        width: 85%; 
    }
    .card-bg8 {
        background-image: url('https://www.gstatic.com/classroom/themes/img_read.jpg');
        background-size: 200%;
        background-repeat: no-repeat;
        background-position: top center;
        height: 220px; 
        width: 85%;
    }
    
    .card-body {
        height: 50%; 
        overflow: auto; 
        width: 70%; 
    }
    .card-text:hover {
        text-decoration: underline;
    }
    .card {
        margin-bottom: 20px; 
    }

</style>


<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg1">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php");

    
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 0"); 
    $stmt->execute([$program_id]);

    
    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>


        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg2">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php"); 
    
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 1"); 
    $stmt->execute([$program_id]);

    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg3">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php"); 

    
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 2"); 
    $stmt->execute([$program_id]);


    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg4">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php"); 

    
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 3"); 
    $stmt->execute([$program_id]);

    
    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg5">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php"); 

   
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 4"); 
    $stmt->execute([$program_id]);

    
    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg6">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php"); 

    
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 5"); 
    $stmt->execute([$program_id]);

    
    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg7">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php");

    
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 6"); 
    $stmt->execute([$program_id]);

    
    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        // Display course_code and course_name for the enrolled Fourth Year subject
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card text-dark rounded-3 shadow card-bg8">
        <div class="card-body">
        <?php
try {
    require("../api/db-connect.php"); // Include your database connection file here

    // Prepare and execute the SQL query to fetch one enrolled Fourth Year course for the student
    $stmt = $conn->prepare("SELECT course_code, course_name 
    FROM tbl_course 
    WHERE course_status = 1 
    AND program_id = ? 
    AND year_id = 4
    LIMIT 1
    OFFSET 7"); // Limit to one record
    $stmt->execute([$program_id]);

    // Fetch the enrolled Fourth Year course for the student
    $enrolled_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($enrolled_course) {
        // Display course_code and course_name for the enrolled Fourth Year subject
        echo '<a href="activity.php" class="card-link">';
        echo '<h5><p class="card-text" style="color: white;">' . $enrolled_course['course_code'] . ': ' . $enrolled_course['course_name'] . '</p></h5>';
        echo '</a>';
    } else {
        echo '<p class="card-text">No enrolled Fourth Year course found for the student.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="card-text">Database Error: ' . $e->getMessage() . '</p>';
}
?>
        </div>
    </div>
</div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
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
</style>
<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
  document.querySelector("#sidebar").classList.toggle("expand");
});
</script>
</html>