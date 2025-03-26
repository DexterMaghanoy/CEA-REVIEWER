<?php
session_start();
require("../api/db-connect.php");

// Check if user is logged in and program ID is set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : $_SESSION['program_id'];



// Fetch program name
$sqlProgramName = "SELECT program_name FROM tbl_program WHERE program_id = :program_id";
$stmtProgramName = $conn->prepare($sqlProgramName);
$stmtProgramName->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtProgramName->execute();
$program_name = $stmtProgramName->fetch(PDO::FETCH_ASSOC)['program_name'] ?? 'Unknown';

// Count total students
$sqlTotalStudents = "SELECT COUNT(*) as total FROM tbl_student WHERE program_id = :program_id";
$stmtTotalStudents = $conn->prepare($sqlTotalStudents);
$stmtTotalStudents->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$stmtTotalStudents->execute();
$totalCount = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$currentYear = date("Y"); // Get the current year
$startYear = 2020; // Define the starting year
$selectedYear = isset($_GET['created_at']) ? (int)$_GET['created_at'] : $currentYear;
$selectedQuizType = isset($_GET['quiz_type']) ? (int)$_GET['quiz_type'] : 1;
$selectedProgramId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : $program_id;

// Fetch top students
$sqlTopStudents = "SELECT 
                        s.stud_lname,
                        s.stud_fname,
                        s.stud_mname,
                        COUNT(r.result_status) AS result_status_count,
                        SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) AS passed_result_status_count,
                        COUNT(DISTINCT CASE WHEN r.result_status = 1 THEN r.module_id ELSE NULL END) AS passed_modules_count,
                        IF(COUNT(r.result_status) > 0, (SUM(CASE WHEN r.result_status = 1 THEN 1 ELSE 0 END) / COUNT(r.result_status)) * 100, 0) AS pass_rate
                    FROM 
                        tbl_student AS s
                    INNER JOIN 
                        tbl_result AS r ON s.stud_id = r.stud_id
                    WHERE
                        r.quiz_type = :quiz_type
                    AND
                        s.program_id = :program_id
                    AND
                        YEAR(r.created_at) = :created_year  
                    GROUP BY 
                        s.stud_id
                    HAVING
                        COUNT(r.result_status) > 0  
                    ORDER BY 
                        passed_modules_count DESC, pass_rate DESC 
                    LIMIT 5";

$stmtTopStudents = $conn->prepare($sqlTopStudents);
$stmtTopStudents->bindParam(':quiz_type', $selectedQuizType, PDO::PARAM_INT);
$stmtTopStudents->bindParam(':program_id', $selectedProgramId, PDO::PARAM_INT);
$stmtTopStudents->bindParam(':created_year', $selectedYear, PDO::PARAM_INT);
$stmtTopStudents->execute();
$totalRows = $stmtTopStudents->rowCount();

// Fetch all programs
$sqlPrograms = "SELECT program_id, program_name FROM tbl_program";
$stmtPrograms = $conn->prepare($sqlPrograms);
$stmtPrograms->execute();
$programs = $stmtPrograms->fetchAll(PDO::FETCH_ASSOC);

// Map quiz types
$quizTypes = [
    1 => "Tests",
    2 => "Quizzes",
    3 => "Exam"
];
$selectedQuizTypeName = $quizTypes[$selectedQuizType] ?? "Unknown";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboards</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <!-- Bootstrap CSS (if not already included) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<style>
    /* program */
    .dropdown-menu .dropdown-item:hover {
        background-color: #f8f9fa !important;
        /* Light gray background */
        color: #000 !important;
        /* Black text */
    }


    /* Keep dropdown open when hovered */
    .dropdown:hover .dropdown-menu {
        display: block;
    }

    /* Ensure smooth appearance */
    .dropdown-menu {
        display: none;
        opacity: 0;
        transition: opacity 0.2s ease-in-out;
    }

    .dropdown:hover .dropdown-menu {
        display: block;
        opacity: 1;
    }

    /* Change background and text color on hover */
    .dropdown-menu .dropdown-item:hover {
        background-color: #f8f9fa !important;
        /* Light gray */
        color: #000 !important;
        /* Black text */
    }

    /* Ensure the dropdown is properly positioned */
    .dropdown-menu {
        left: 0 !important;
        /* Align with the button */
        right: auto !important;
        min-width: 100%;
        /* Ensures the dropdown matches the button width */
    }

    /* Adjust alignment for adjacent elements like the date */
    .dropdown {
        position: relative;
        /* Keep position context for absolute positioning */
    }

    /* Prevent overlapping with other elements */
    .dropdown-menu {
        z-index: 1050;
        /* Ensures it's above other elements */
    }

    /* Make dropdown items gray when hovered */
    .dropdown-menu .dropdown-item:hover {
        background-color: #d6d6d6 !important;
        /* Light gray */
        color: #000 !important;
        /* Black text for contrast */
    }






    /* Year selection */
    .dropdown-container {
        position: relative;
        display: inline-block;
    }

    #yearDropdown {
        appearance: none;
        /* Remove default styles */
        background: #0d6efd;
        /* Bootstrap Primary Blue */
        color: white;
        border: none;
        padding: 10px 35px 10px 15px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
        outline: none;
    }

    /* Custom Arrow */
    .dropdown-container::after {
        content: "▾";
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        color: white;
        pointer-events: none;
    }

    /* Dropdown on hover */
    #yearDropdown:focus {
        background: #0056b3;
    }

    /* Style dropdown options */
    #yearDropdown option {
        background: white;
        color: black;
        padding: 5px;
    }
</style>

<body>

    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="container mt-4">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="programDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($program_name) ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="programDropdown">
                            <?php foreach ($programs as $program): ?>
                                <li>
                                    <a class="dropdown-item <?= ($program['program_id'] == $selectedProgramId) ? 'active' : ''; ?>"
                                        href="?program_id=<?= $program['program_id'] ?>&quiz_type=<?= $selectedQuizType ?>&created_at=<?= $selectedYear ?>">
                                        <?= htmlspecialchars($program['program_name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-md-4 text-center">
                    <h1>Leaderboards</h1>
                    <div class="dropdown-container">
                        <select id="yearDropdown">
                            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                <option value="<?= $year ?>" <?= ($year == $selectedYear) ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>



                <div class="col-md-4 text-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="quizDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($selectedQuizTypeName) ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="quizDropdown">
                            <?php foreach ($quizTypes as $type => $name): ?>
                                <li>
                                    <a class="dropdown-item <?= ($type == $selectedQuizType) ? 'active' : ''; ?>"
                                        href="?program_id=<?= $selectedProgramId ?>&quiz_type=<?= $type ?>&created_at=<?= $selectedYear ?>">
                                        <?= htmlspecialchars($name) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Rank</th>
                            <th>Full Name</th>
                            <th>Total Attempts</th>
                            <th>Modules Passed</th>
                            <th>Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1;
                        while ($row = $stmtTopStudents->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']) ?></td>
                                <td><?= $row['result_status_count'] ?></td>
                                <td><?= $row['passed_modules_count'] ?></td>
                                <td><?= number_format($row['pass_rate'], 2) ?>%</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("yearDropdown").addEventListener("change", function() {
            window.location.href = "?program_id=<?= $selectedProgramId ?>&quiz_type=<?= $selectedQuizType ?>&created_at=" + this.value;
        });
    </script>
</body>

</html>