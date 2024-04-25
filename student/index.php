<?php
session_start();
require("../api/db-connect.php");

if(isset($_SESSION['lock']) && $_SESSION['lock'] === true) {
    switch ($_SESSION['stud_id']) {
        case 1: 
            header("Location: firstyear.php");
            exit();
        case 2:
            header("Location: secondyear.php");
            exit();
        case 3: 
            header("Location: thirdyear.php");
            exit();
	case 4: 
            header("Location: fourthyear.php");
            exit();
        default:
            header("Location: index.php");
            exit();
    }
}

if (isset($_POST['submit'])) {
    if (!empty($_POST['g-recaptcha-response'])) {
        $captcha_response = $_POST['g-recaptcha-response'];
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => '6Lc4H3ApAAAAAIfb-ov9xqCzXvWYsHX9pOxWOy3t',
            'response' => $captcha_response
        );

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data)
            )
        );

        if (isset($_POST['txtUsername']) && isset($_POST['txtPassword'])) {
            function validate($data) {
                $data = trim($data);
                $data = stripslashes($data);
                $data = htmlspecialchars($data);
                return $data;
            }

            $username = validate($_POST['txtUsername']);
            $password = validate($_POST['txtPassword']);

            $stmt = $conn->prepare("SELECT * FROM tbl_student WHERE stud_no = :username");

            $stmt->bindParam(':username', $username);

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if($result && $result['stud_password'] === $password){
                $_SESSION['year_id'] = $result['year_id'];
                $_SESSION['stud_fname'] = $result['stud_fname'];
                $_SESSION['stud_lname'] = $result['stud_lname'];
                $_SESSION['program_id'] = $result['program_id'];
                $_SESSION['lock'] = true;
                $_SESSION['stud_id'] = $result['stud_id'];
                
                switch ($result['year_id']) {
                    case 1: 
                        header("Location: firstyear.php");
                        exit();
                    case 2:
                        header("Location: secondyear.php");
                        exit();
                    case 3: 
                        header("Location: thirdyear.php");
                        exit();
			case 4: 
            		header("Location: fourthyear.php");
            		exit();
                    default:
                        header("Location: index.php ");
                        exit();
                }
            } else {
                echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Error!",
                        text: "Incorrect Username or Password",
                        icon: "error"
                    });
                });
            </script>';
            }
        }
    } else {
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
            echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
            echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Error!",
                        text: "Please complete the CAPTCHA.",
                        icon: "error"
                    });
                });
            </script>';
    }
}
?>

<!DOCTYPE html>
<html class="fluid top-full sticky-top sidebar sidebar-full">
<head>
    <link rel="icon" href="../img/cea_logo.png" type="image/x-icon">
    <title>CEA Reviewer System</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8; IE=7; IE=EDGE" />

    <link rel="stylesheet" href="https://pensms.phinma.edu.ph/assets/css/admin/module.admin.page.login.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        .text-dark,
        .text-dark::before{
            color: #333 !important;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body class="login ">

<div id="login">
    <div><img src="../img/student.png" style="width:30vmin;display: block;margin:0 auto;"></div>
    <div class="container">
        <h1 class="glyphicons text-dark">CEA Reviewer System<i class="text-dark"></i></h1>
        <div class="wrapper">
            <div class="widget widget-heading-simple widget-body-gray">
                <div class="widget-body">
                    <form id="loginForm" method="post" action="">   
                        <label>Username</label>
                        <input type="text" class="form-control" placeholder="Your Username" name="txtUsername" required />
                        <label>Password</label>
                        <div class="password-toggle">
                            <input type="password" class="form-control" placeholder="Your Password" name="txtPassword" id="txtPassword" required />
                            <span class="password-toggle-icon fa fa-eye" onclick="togglePassword()"></span>
                        </div>
                        <div class="g-recaptcha" data-sitekey="6Lc4H3ApAAAAAIfb-ov9xqCzXvWYsHX9pOxWOy3t"></div>

                        <div class="loginLink">
                            <a class="password" href="forgotpass.php">Forgot your password?</a>
                        </div>
                        <div class="separator bottom clearfix"></div>
                        <div class="row">
                            <div class="col-md-8">
                            </div>
                            <div class="col-md-4 center">
                                <button class="btn btn-block btn-success" type="submit" name="submit">Sign In</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="widget-footer text-center">
                    <p><i class="fa fa-refresh"></i> Please enter your username and password</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        var passwordInput = document.getElementById("txtPassword");
        var icon = document.querySelector(".password-toggle-icon");

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>