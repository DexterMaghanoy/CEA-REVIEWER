<?php
    session_start();
    require("../api/db-connect.php");
    
    if(isset($_SESSION['user_id'])){
        $user_id = $_SESSION['user_id'];
    } else {
      header("Location: ../index.php");
        exit();
    }

    // Use JOIN to get user_type and course_name from related tables
    $sql = "SELECT u.*, t.type_name, p.program_name
            FROM tbl_user u
            INNER JOIN tbl_type t ON u.type_id = t.type_id
            INNER JOIN tbl_program p ON u.program_id = p.program_id
            WHERE u.user_id = :user_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Check if the query was successful and if there is a user with the given emp_id
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
    <div class="wrapper">
    <?php
        include 'sidebar.php';
        ?>
        <div class="main p-3">
    <div class="text-center">
        <h1>Profile</h1>
    </div>
<section class="vh-90">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col col-lg-6 mb-4 mb-lg-0">
        <div class="card mb-3" style="border-radius: .5rem;">
          <div class="row g-0">
            <div class="col-md-4 gradient-custom text-center text-white"
              style="border-top-left-radius: .5rem; border-bottom-left-radius: .5rem;">
              <img src="data:image/jpg;base64,<?php echo base64_encode($user['user_image']); ?>" alt="Avatar" class="rounded-circle img-fluid my-5" style="width: 100px;">
              <h5><?php echo $user['user_fname'] . ' ' . $user['user_lname'] ?></h5>
              <p><?php echo $user['type_name'];?></p>
              <i class="far fa-edit mb-5"></i>
            </div>
            <div class="col-md-8">
              <div class="card-body p-4">
                <h6>Information</h6>
                <hr class="mt-0 mb-4">
                <div class="row pt-1">
                  <div class="col-6 mb-3">
                    <h6>Department</h6>
                    <p class="text-muted">College of Engineering and Architecture</p>
                  </div>
                  <div class="col-6 mb-3">
                    <h6>Fullname</h6>
                    <p class="text-muted"><?php echo $user['user_lname'] . ', ' . $user['user_fname'] . ' ' . $user['user_mname']; ?></p>
                  </div>
                </div>
                <h6>Account</h6>
                <hr class="mt-0 mb-4">
                <div class="row pt-1">
                  <div class="col-6 mb-3">
                    <h6>Username</h6>
                    <p class="text-muted"><?php echo $user['user_name']; ?></p>
                  </div>
                  <div class="col-6 mb-3">
                    <h6>Password</h6>
                    <p class="text-muted"><?php echo $user['user_password']; ?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
</body>
<script>
const hamBurger = document.querySelector(".toggle-btn");

hamBurger.addEventListener("click", function () {
  document.querySelector("#sidebar").classList.toggle("expand");
});
</script>
</html>