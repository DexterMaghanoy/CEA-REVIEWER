<?php
session_start();
require("../api/db-connect.php");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header("Location: ../index.php");
    exit();
}

// Build the SQL query with search functionality
$sql = "SELECT * FROM tbl_program";

$result = $conn->prepare($sql);
$result->execute();

// Check if form is submitted for toggling user status
if (isset($_POST['toggle_status']) && isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];
    // Get current status of the user
    $stmt = $conn->prepare("SELECT program_status FROM tbl_program WHERE program_id = :program_id");
    $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_status = $row['program_status'] == 1 ? 0 : 1; // Toggle status

    // Update user status
    $updateStmt = $conn->prepare("UPDATE tbl_program SET program_status = :new_status WHERE program_id = :program_id");
    $updateStmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
    $updateStmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $updateStmt->execute();

    // Redirect to avoid form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>

        <?php
        include 'back.php';
        ?>


        <div class="container mt-3">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="text-center">
                        <h1>Courses</h1>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="add_program.php?"><i class="lni lni-plus"></i></a><br><br>
                    <div class="table-responsive">
                        <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">

                            <caption>List of Program</caption>
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
                                            <td><?php echo $row['program_id']; ?></td>
                                            <td><?php echo $row['program_name']; ?></td>
                                            <td>
                                                <a class="btn btn-primary btn-sm" href="edit_program.php?program_id=<?php echo $row['program_id']; ?>"><i class="lni lni-pencil"></i></a>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="program_id" value="<?php echo $row['program_id']; ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['program_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>">
                                                        <?php if ($row['program_status'] == 1) : ?>
                                                            <i class="lni lni-checkmark-circle"></i> <!-- Green circle icon for activated -->
                                                        <?php else : ?>
                                                            <i class="lni lni-checkmark-circle"></i> <!-- Yellow circle icon for deactivated -->
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No records found for student.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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