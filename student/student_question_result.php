<?php
session_start();
require '../api/db-connect.php';

global $results;

if (!isset($_SESSION['program_id']) || !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

$program_id = $_SESSION['program_id'];
$stud_id = $_SESSION['stud_id'];

// Get GET parameters
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

// Fetch courses for the given program
$sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

// Find course name
$courseName = '';
foreach ($courses as $course) {
    if ($course['course_id'] == $course_id) {
        $courseName = $course['course_name'];
        break;
    }
}

// Build the result query
$sql = "SELECT tbl_result.result_score, tbl_result.total_questions, tbl_module.module_name, tbl_result.attempt_id, tbl_result.created_at as date_created
        FROM tbl_result
        INNER JOIN tbl_module ON tbl_result.module_id = tbl_module.module_id
        WHERE tbl_result.quiz_type = 1
          AND tbl_result.course_id = :course_id
          AND tbl_result.stud_id = :stud_id";

if (!empty($module_id)) {
    $sql .= " AND tbl_result.module_id = :module_id";
}

$sql .= " ORDER BY tbl_result.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
if (!empty($module_id)) {
    $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
}
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch the latest pass rate
$passRateSql = "SELECT pass_rate FROM tbl_passrate ORDER BY created_at DESC LIMIT 1";
$passRateStmt = $conn->prepare($passRateSql);
$passRateStmt->execute();
$passRateData = $passRateStmt->fetch(PDO::FETCH_ASSOC);
$passRate = $passRateData['pass_rate'] ?? 0; // Fallback to 0 if no rate is found


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Result</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <link rel="stylesheet" href="mobile-desktop.css" type="text/css">
</head>

<body>

    <div class="mt-5" id="topBar">
        <?php include 'topNavBar.php'; ?>
    </div>

    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main p-3">
            <div class="container">
                <div class="row justify-content-center mt-2">
                    <div class="col-md-12">
                        <div class="text-center mb-4">
                            <h1><?php echo "Subject: " . htmlspecialchars($courseName); ?></h1>
                        </div>

                        <!-- Search Bar -->
                        <form method="GET" class="mb-4">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                            <input type="hidden" name="stud_id" value="<?php echo htmlspecialchars($stud_id); ?>">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by module name" name="search" id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchButton"><i class="lni lni-close"></i></button>
                            </div>
                        </form>

                        <!-- Results Table -->
                        <div class="table-responsive">
                            <table id="resultTable" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
                                <caption>List of Scores</caption>
                                <thead class="table-dark">
                                    <tr style="text-align: center;">
                                        <th data-column="0">Attempt No.</th>
                                        <th data-column="1">Title</th>
                                        <th data-column="2">Score</th>
                                        <th data-column="3">Result</th>
                                        <th data-column="4">Date</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if ($results) : ?>
                                        <?php foreach ($results as $row) : ?>
                                            <tr style="text-align: center;">
                                                <td><?php echo $row['attempt_id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['module_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $res = ($row['result_score'] / $row['total_questions']) * 100;
                                                    $color = $res >= $passRate ? 'green' : 'red';
                                                    echo "<span style='color: $color;'>{$row['result_score']} / {$row['total_questions']}</span>";
                                                    ?>
                                                </td>
                                                <td style="color:<?php echo $res >= $passRate ? 'green' : 'red'; ?>">
                                                    <?php echo $res >= $passRate ? 'Passed' : 'Failed'; ?>
                                                </td>
                                                <td><?php echo date("M d, Y", strtotime($row['date_created'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    const hamBurger = document.querySelector(".toggle-btn");
    const sidebar = document.querySelector("#sidebar");
    const mainContent = document.querySelector(".main");
    const searchInput = document.getElementById("searchInput");
    const clearSearchButton = document.getElementById("clearSearchButton");

    hamBurger.addEventListener("click", function() {
        sidebar.classList.toggle("expand");
        mainContent.classList.toggle("expand");
    });

    function toggleClearButton() {
        clearSearchButton.style.display = searchInput.value !== "" ? "block" : "none";
    }

    toggleClearButton();

    searchInput.addEventListener("keyup", function() {
        toggleClearButton();
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll("#resultTable tbody tr");

        rows.forEach(row => {
            const moduleName = row.children[1].textContent.toLowerCase();
            row.style.display = moduleName.includes(value) ? "" : "none";
        });
    });

    clearSearchButton.addEventListener("click", function() {
        searchInput.value = "";
        toggleClearButton();
        const rows = document.querySelectorAll("#resultTable tbody tr");
        rows.forEach(row => row.style.display = "");
    });
</script>

<script>
    const table = document.getElementById("resultTable");
    let sortDirection = {};

    document.querySelectorAll("#resultTable thead th").forEach((header, index) => {
        header.style.cursor = "pointer";
        header.addEventListener("click", () => {
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll("tr"));
            const column = parseInt(header.dataset.column);

            const isAscending = sortDirection[column] = !sortDirection[column];

            rows.sort((a, b) => {
                const cellA = a.children[column].innerText.trim();
                const cellB = b.children[column].innerText.trim();

                // If it's a number, sort numerically
                const numA = parseFloat(cellA);
                const numB = parseFloat(cellB);

                if (!isNaN(numA) && !isNaN(numB)) {
                    return isAscending ? numA - numB : numB - numA;
                }

                // If it's a date (column 4), sort by date
                if (column === 4) {
                    return isAscending ?
                        new Date(cellA) - new Date(cellB) :
                        new Date(cellB) - new Date(cellA);
                }

                // Otherwise sort as string
                return isAscending ?
                    cellA.localeCompare(cellB) :
                    cellB.localeCompare(cellA);
            });

            // Reattach sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });
</script>