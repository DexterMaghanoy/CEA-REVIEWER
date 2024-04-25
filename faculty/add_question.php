<?php
session_start();

require '../api/db-connect.php';

if(isset($_SESSION['program_id'])){
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['save'])) {

    $module_id = $_POST['module_id'];
    $question_text = $_POST['question_text'];
    $question_A = $_POST['question_A'];
    $question_B = $_POST['question_B'];
    $question_C = $_POST['question_C'];
    $question_D = $_POST['question_D'];
    $question_answer = $_POST['question_answer'];
    

    if (empty($module_id) || empty($question_text) || empty($question_A) || empty($question_B) || empty($question_C) || empty($question_D) || empty($question_D)) {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "Please input the fields.",
                    icon: "error"
                });
            });
        </script>';
    } else {
            // Insert new program
            $sql = "INSERT INTO `tbl_question`(`module_id`, `question_text`, `question_A`, `question_B`, `question_C`, `question_D`, `question_answer`)
             VALUES (:module_id,:question_text,:question_A,:question_B,:question_C,:question_D,:question_answer)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":module_id", $module_id);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_A", $question_A);
            $stmt->bindParam(":question_B", $question_B);
            $stmt->bindParam(":question_C", $question_C);
            $stmt->bindParam(":question_D", $question_D);
            $stmt->bindParam(":question_answer", $question_answer);

            if ($stmt->execute()) {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
                echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Success!",
                            text: "Question added successfully.",
                            icon: "success"
                        }).then(() => {
                            window.location.href = "course.php";
                        });
                    });
                </script>';
            } else {
                echo '<script>
                    $(document).ready(function(){
                        Swal.fire({
                            title: "Failed!",
                            text: "Failed to add question.",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "course.php";
                        });
                    });
                    </script>';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
</head>
<body>
<div class="wrapper">
    
<?php
include '../sidebar.php';
?>
        <div class="main py-3">
    <div class="text-center mb-4">
        <h1>Add Question</h1>
    </div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <form action="add_question.php" method="post" >

                <!-- Module Number Input -->
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="1" required></textarea>
                    </div>

                    <!-- Option Input -->
                    <div class="mb-3">
                        <label for="question_A" class="form-label">Option A</label>
                        <textarea class="form-control" id="question_A" name="question_A" rows="1" required></textarea>
                    </div>

                     <!-- Option Input -->
                     <div class="mb-3">
                        <label for="question_B" class="form-label">Option B</label>
                        <textarea class="form-control" id="question_B" name="question_B" rows="1" required></textarea>
                    </div>
                    
                     <!-- Option Input -->
                     <div class="mb-3">
                        <label for="question_C" class="form-label">Option C</label>
                        <textarea class="form-control" id="question_C" name="question_C" rows="1" required></textarea>
                    </div>

                     <!-- Option Input -->
                     <div class="mb-3">
                        <label for="question_D" class="form-label">Option D</label>
                        <textarea class="form-control" id="question_D" name="question_D" rows="1" required></textarea>
                    </div>

                     <!-- Option Input -->
                     <div class="mb-3">
                        <label for="question_answer" class="form-label">Answer</label>
                        <textarea class="form-control" id="question_answer" name="question_answer" rows="1" required></textarea>
                    </div>
                    
                    <!-- Hidden Employee ID and Submit Button -->
                    <input type="hidden" name="module_id" value="<?php echo $_GET['module_id']; ?>">
                    <input type="submit" class="btn btn-success mt-2" value="Save" name="save">
                </form>
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
</style>
<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
  document.querySelector("#sidebar").classList.toggle("expand");
});
</script>
</html>