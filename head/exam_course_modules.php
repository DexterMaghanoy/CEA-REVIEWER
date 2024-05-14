<?php
session_start();

require '../api/db-connect.php';

// Redirect to login page if session data is not set
if (!isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$program_id = $_SESSION['program_id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * 10;

// Retrieve modules for the specified course
if ($course_id) {
    $stmt = $conn->prepare("SELECT * FROM tbl_module WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_module");
}
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if a specific module ID is selected
if (isset($_GET['module_id'])) {
    $module_id = $_GET['module_id'];
}

// Pagination
$resultsPerPage = 10;
if ($user_id) {
    // Main query for retrieving results with provided filters
    $sql = "SELECT r.stud_id, s.stud_fname, s.stud_mname, s.stud_lname, c.course_name, m.module_name, r.created_at, r.total_questions, r.result_score, SUM(r.result_score) AS total_score
FROM tbl_result r
INNER JOIN tbl_student s ON r.stud_id = s.stud_id
INNER JOIN tbl_module m ON r.module_id = m.module_id
INNER JOIN tbl_course c ON m.course_id = c.course_id
WHERE c.program_id = :program_id";

    // Add conditions to filter by course ID and module ID if they are provided
    if ($course_id) {
        $sql .= " AND m.course_id = :course_id";
    }
    if (isset($module_id)) {
        $sql .= " AND r.module_id = :module_id";
    }

    if (!empty($search)) {
        // Add search conditions
        $sql .= " AND (s.stud_fname LIKE :search OR s.stud_lname LIKE :search OR c.course_name LIKE :search OR m.module_name LIKE :search)";
    }

    $sql .= " GROUP BY r.stud_id, c.course_id ";

    // Count query to calculate the total number of results with provided filters
    $countQuery = "SELECT COUNT(*) AS count FROM (
    SELECT r.stud_id
    FROM tbl_result r
    INNER JOIN tbl_student s ON r.stud_id = s.stud_id
    INNER JOIN tbl_module m ON r.module_id = m.module_id
    INNER JOIN tbl_course c ON m.course_id = c.course_id
    WHERE c.program_id = :program_id";

    // Add conditions to count query
    if ($course_id) {
        $countQuery .= " AND m.course_id = :course_id";
    }
    if (isset($module_id)) {
        $countQuery .= " AND r.module_id = :module_id";
    }

    if (!empty($search)) {
        // Add search conditions to count query
        $countQuery .= " AND (s.stud_fname LIKE :search OR s.stud_lname LIKE :search OR c.course_name LIKE :search OR m.module_name LIKE :search)";
    }

    $countQuery .= " GROUP BY r.stud_id, c.course_id ) AS sub";

    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->bindValue(':program_id', $program_id);
    if (!empty($search)) {
        $stmtCount->bindValue(':search', '%' . $search . '%');
    }
    if ($course_id) {
        $stmtCount->bindValue(':course_id', $course_id);
    }
    if (isset($module_id)) {
        $stmtCount->bindValue(':module_id', $module_id);
    }
    $stmtCount->execute();
    $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $totalCount = $countResult['count'];
    $totalPages = ceil($totalCount / $resultsPerPage);

    // Add pagination and execute main query
    $sql .= " LIMIT $resultsPerPage OFFSET $offset";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':program_id', $program_id);
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    if ($course_id) {
        $stmt->bindValue(':course_id', $course_id);
    }
    if (isset($module_id)) {
        $stmt->bindValue(':module_id', $module_id);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch the results

} else {
    // Redirect if user ID is not set in the session
}

// Fetch courses for the given program
$sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">
    <script src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>
        <div class="main p-3">


            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h1>Student Exam Report</h1>
                        </div>
                    </div>
                </div>
                <div class="row">

                    <div class="col-sm">
                        <div id="myChart" style="width:100%; max-width:600px; height:500px;"></div>

                        <script>
                            google.charts.load('current', {
                                'packages': ['corechart']
                            });
                            google.charts.setOnLoadCallback(drawChart);

                            function drawChart() {
                                const data = new google.visualization.DataTable();
                                data.addColumn('string', 'Module Name');
                                data.addColumn('number', 'Score');
                                data.addColumn({
                                    type: 'string',
                                    role: 'tooltip',
                                    'p': {
                                        'html': true
                                    }
                                }); // Add tooltip role

                                <?php foreach ($results as $row) : ?>
                                    <?php
                                    // Retrieve module_id from URL parameter if available
                                    $module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

                                    // Fetch attempts from tbl_result
                                    $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS attempts FROM tbl_result WHERE stud_id = :stud_id AND module_id = :module_id AND quiz_type = 3");
                                    $stmtAttempts->bindValue(':stud_id', $row['stud_id']);
                                    $stmtAttempts->bindValue(':module_id', $module_id);
                                    if (!$stmtAttempts->execute()) {
                                        echo "Error executing query: " . implode(" ", $stmtAttempts->errorInfo());
                                    } else {
                                        $attemptsData = $stmtAttempts->fetch(PDO::FETCH_ASSOC);
                                        $attempts = $attemptsData['attempts'];
                                        $passRate = ($attempts != 0) ? number_format(100 / $attempts, 2) : 0; // Calculate pass rate with 2 decimal places
                                        echo $passRate;
                                    }
                                    ?>

                                    data.addRow([
                                        '<?php echo $row['stud_lname'] . ' ' . $row['stud_fname']; ?>',
                                        <?php echo $attempts; ?>,
                                        '<?php echo $passRate; ?>' // Construct tooltip with pass rate
                                    ]);
                                <?php endforeach; ?>

                                const options = {
                                    title: 'Student Exam Performance',
                                    hAxis: {
                                        title: 'Pass Rate',
                                        textStyle: {
                                            fontSize: 12 // Adjust the font size of the axis labels
                                        }
                                    },
                                    vAxis: {
                                        title: 'Student Name'
                                    },
                                    chartArea: {
                                        width: '50%', // Adjust the width of the chart area
                                        height: '70%' // Adjust the height of the chart area
                                    }
                                };

                                const chart = new google.visualization.BarChart(document.getElementById('myChart'));
                                chart.draw(data, options);
                            }
                        </script>

                    </div>


                    <div class="col-sm">

                        <!-- Search form -->
                        <form action="" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo $search; ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <table id="resultTable" class="table table-bordered border-secondary">
                            <caption>List of Student Performance</caption>
                            <thead class="table-dark">
                                <tr style="text-align: center;">
                                    <th scope="col">Student Name</th>
                                    <th scope="col">Module Name</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Attempts</th>
                                    <th scope="col">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row) : ?>
                                    <tr style="text-align: center;">
                                        <td><?php echo $row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']; ?></td>
                                        <td><?php echo $row['module_name']; ?></td>
                                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            // Retrieve module_id from URL parameter if available
                                            $module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

                                            // Fetch attempts from tbl_result
                                            $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS attempts FROM tbl_result WHERE stud_id = :stud_id AND module_id = :module_id AND quiz_type = 3");
                                            $stmtAttempts->bindValue(':stud_id', $row['stud_id']);
                                            $stmtAttempts->bindValue(':module_id', $module_id);
                                            if (!$stmtAttempts->execute()) {
                                                echo "Error executing query: " . implode(" ", $stmtAttempts->errorInfo());
                                            } else {
                                                $attemptsData = $stmtAttempts->fetch(PDO::FETCH_ASSOC);
                                                $attempts = $attemptsData['attempts'];
                                                echo $attempts;
                                            }
                                            ?>


                                        </td>
                                        <td>

                                            <?php
                                            // Retrieve module_id from URL parameter if available
                                            $module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

                                            // Fetch attempts from tbl_result
                                            $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS attempts FROM tbl_result WHERE stud_id = :stud_id AND module_id = :module_id AND quiz_type = 3");
                                            $stmtAttempts->bindValue(':stud_id', $row['stud_id']);
                                            $stmtAttempts->bindValue(':module_id', $module_id);
                                            if (!$stmtAttempts->execute()) {
                                                echo "Error executing query: " . implode(" ", $stmtAttempts->errorInfo());
                                            } else {
                                                $attemptsData = $stmtAttempts->fetch(PDO::FETCH_ASSOC);
                                                $attempts = $attemptsData['attempts'];
                                                $passRate = ($attempts != 0) ? number_format(100 / $attempts, 2) : 0; // Calculate pass rate with 2 decimal places
                                                echo $passRate . "%";
                                            }
                                            ?>


                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>


                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&course_id=<?php echo $course_id; ?>&module_id=<?php echo $module_id; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>

                    </div>



                </div>

            </div>
        </div>
    </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add event listener to module dropdown items
            document.querySelectorAll('.module-item').forEach(item => {
                item.addEventListener('click', function(event) {
                    event.preventDefault();
                    // Get module ID and name from data attributes
                    const moduleId = this.getAttribute('data-module-id');
                    const moduleName = this.innerText;
                    // Update button text with the selected module name
                    document.getElementById('moduleDropdownText').innerText = moduleName;
                    // Redirect with selected module ID
                    window.location.href = `?course_id=<?php echo $course_id; ?>&module_id=${moduleId}`;
                });
            });
        });
    </script>


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