<?php
session_start();
require("../api/db-connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Build the SQL query
$sql = "SELECT * FROM tbl_program";
$result = $conn->prepare($sql);
$result->execute();

// Check if form is submitted for toggling program status
if (isset($_POST['toggle_status']) && isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];

    // Get current status of the program
    $stmt = $conn->prepare("SELECT program_status FROM tbl_program WHERE program_id = :program_id");
    $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['program_status'] == 1 ? 0 : 1; // Toggle status

    // Update program status
    $updateStmt = $conn->prepare("UPDATE tbl_program SET program_status = :new_status WHERE program_id = :program_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $updateStmt->execute();

    // Redirect to avoid form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Update pass rate
if (isset($_POST['pass_rate'])) {
    $passRate = (int)$_POST['pass_rate'];

    $sql = "UPDATE tbl_passrate SET pass_rate = :pass_rate ORDER BY pass_id DESC LIMIT 1";
    $stmtPass_rate = $conn->prepare($sql);
    $stmtPass_rate->bindParam(':pass_rate', $passRate, PDO::PARAM_INT);
    $stmtPass_rate->execute();

    $sql = "SELECT pass_rate FROM tbl_passrate ORDER BY pass_id DESC LIMIT 1";
    $stmtPass_rate = $conn->prepare($sql);
    $stmtPass_rate->execute();
    $passRate = $stmtPass_rate->fetchColumn(); // Fetch the single pass_rate value
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php include 'back.php'; ?>

        <div class="container mt-3">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="text-center">
                        <h1>Courses</h1>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="add_course.php"><i class="lni lni-plus"></i></a><br><br>

                    <div class="row align-items-center">
                        <div style="padding-bottom: 10px;" class="col-sm-8 text-end">
                            <h5><strong>Current Pass Rate:</strong></h5>
                        </div>
                        <div class="col-sm-4">
                            <?php
                            $sql = "SELECT pass_rate FROM tbl_passrate ORDER BY pass_id DESC LIMIT 1";
                            $stmtPass_rate = $conn->prepare($sql);
                            $stmtPass_rate->execute();
                            $passRate = $stmtPass_rate->fetchColumn();
                            ?>
                            <style>
                                .custom-input-outline {
                                    border-color: #3498db;
                                    /* Change to your desired outline color */
                                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                                    /* Adjust shadow properties as needed */
                                }

                                .input-group {
                                    display: flex;
                                    align-items: center;
                                }

                                .input-group input {
                                    flex: 1;
                                }

                                .input-group button {
                                    margin-left: 10px;
                                }
                            </style>

                            <div class="input-group mb-3">
                                <input type="number" class="form-control form-control-lg custom-input-outline" id="pass_rate" name="pass_rate" min="0" max="100" step="1">
                                <button class="btn btn-primary" id="confirm-pass-rate">Confirm</button>
                            </div>

                            <div id="update-message"></div>
                            <input type="hidden" id="initial-pass-rate" value="<?= $passRate ?>">
                        </div>

                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const passRateInput = document.getElementById('pass_rate');
                                const initialPassRate = document.getElementById('initial-pass-rate').value; // Get initial pass rate from hidden field
                                const updateMessage = document.getElementById('update-message');
                                const confirmButton = document.getElementById('confirm-pass-rate');

                                passRateInput.value = initialPassRate;

                                confirmButton.addEventListener('click', function() {
                                    const selectedPassRate = passRateInput.value;
                                    updatePassRate(selectedPassRate);
                                });

                                function updatePassRate(passRate) {
                                    const xhr = new XMLHttpRequest();
                                    xhr.open('POST', 'courses.php');
                                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                                    xhr.onload = function() {
                                        if (xhr.status === 200) {
                                            Swal.fire({
                                                title: "Success!",
                                                text: "Pass rate updated successfully.",
                                                icon: "success"
                                            }).then(() => {
                                                window.location.href = "courses.php";
                                            });
                                        } else {
                                            Swal.fire({
                                                title: "Failed!",
                                                text: "Pass rate update failed.",
                                                icon: "error"
                                            }).then(() => {
                                                window.location.href = "courses.php";
                                            });
                                        }
                                    };

                                    const data = `pass_rate=${passRate}`;
                                    xhr.send(data);
                                }
                            });
                        </script>




                    </div>
                    <div class="table-responsive">
                        <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                            <caption>List of Programs</caption>
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">No.</th>
                                    <th scope="col">Program</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->rowCount() > 0) : ?>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) : ?>
                                        <tr>
                                            <td><?= $row['program_id']; ?></td>
                                            <td><?= $row['program_name']; ?></td>
                                            <td>
                                                <a class="btn btn-primary btn-sm" href="edit_course.php?program_id=<?= $row['program_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="program_id" value="<?= $row['program_id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?= $row['program_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>">
                                                        <i class="lni lni-checkmark-circle"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No records found for programs.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
        <script>
            const hamBurger = document.querySelector(".toggle-btn");
            hamBurger.addEventListener("click", function() {
                document.querySelector("#sidebar").classList.toggle("expand");
            });
        </script>
</body>

</html>