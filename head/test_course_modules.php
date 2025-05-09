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
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * 10;

// Retrieve modules for the specified course, ensuring the module is active (module_status = 1)
if ($course_id) {
    $stmt = $conn->prepare("SELECT * FROM tbl_module WHERE course_id = :course_id AND module_status = 1");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_module WHERE module_status = 1");
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
WHERE c.program_id = :program_id and m.module_status = 1";  // Ensure only active modules are considered

    // Add conditions to filter by course ID and module ID if they are provided
    if ($course_id) {
        $sql .= " AND m.course_id = :course_id";
    }
    if (isset($module_id)) {
        $sql .= " AND r.module_id = :module_id";
    }

    $sql .= " GROUP BY r.stud_id, c.course_id ";

    // Count query to calculate the total number of results with provided filters
    $countQuery = "SELECT COUNT(*) AS count FROM (
    SELECT r.stud_id
    FROM tbl_result r
    INNER JOIN tbl_student s ON r.stud_id = s.stud_id
    INNER JOIN tbl_module m ON r.module_id = m.module_id
    INNER JOIN tbl_course c ON m.course_id = c.course_id
    WHERE c.program_id = :program_id AND m.module_status = 1";  // Ensure only active modules are considered

    // Add conditions to count query
    if ($course_id) {
        $countQuery .= " AND m.course_id = :course_id";
    }
    if (isset($module_id)) {
        $countQuery .= " AND r.module_id = :module_id";
    }

    $countQuery .= " GROUP BY r.stud_id, c.course_id ) AS sub";

    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->bindValue(':program_id', $program_id);
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
    if ($course_id) {
        $stmt->bindValue(':course_id', $course_id);
    }
    if (isset($module_id)) {
        $stmt->bindValue(':module_id', $module_id);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
}

$sql = "SELECT * FROM tbl_course WHERE program_id = :program_id";
$result = $conn->prepare($sql);
$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

$course_id = $_GET['course_id'];

$courseQuery = $conn->prepare("SELECT course_name FROM tbl_course WHERE course_id = :course_id");
$courseQuery->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$courseQuery->execute();
$course = $courseQuery->fetch(PDO::FETCH_ASSOC);

$module_id = $_GET['module_id'];

$moduleQuery = $conn->prepare("SELECT module_name FROM tbl_module WHERE module_id = :module_id AND module_status = 1");  // Ensure only active modules are considered
$moduleQuery->bindParam(':module_id', $module_id, PDO::PARAM_INT);
$moduleQuery->execute();
$module = $moduleQuery->fetch(PDO::FETCH_ASSOC);


// Check if $module contains any data
if ($module && isset($module['module_name'])) {
    $module['module_name'];
} else {
    $module['module_name'] =  "No Records";
}

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


        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-8">
                    <div class="text-center mb-4">
                        <h1>

                            <?php
                            echo '<h2>' . $course['course_name'] . '</h2>';
                            // echo '<h5>' . $module['module_name'] . '</h5>';
                            ?>

                        </h1>
                    </div>
                </div>
            </div>
            <div class="row">

                <div class="col-sm">
                    <div id="myChart" style="border: 1px solid lightblue;
                                    padding: 10px;
                                    box-sizing: border-box;
                                    border-radius: 15px; 
                                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                    height: 525px;">
                    </div>

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
                                $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS attempts FROM tbl_result WHERE stud_id = :stud_id AND module_id = :module_id AND quiz_type = 1");
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
                                    <?php echo 100 / $attempts; ?>,
                                    '<?php echo $passRate; ?>' // Construct tooltip with pass rate
                                ]);
                            <?php endforeach; ?>

                            const options = {
                                title: 'Student Performance by Module',
                                hAxis: {
                                    title: 'Pass Rate',
                                    minValue: 0,
                                    maxValue: 100

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
                    <?php include 'module_dropdown.php'; ?>

                    <form id="searchForm" class="mb-3" onsubmit="return false;">
                        <div class="input-group mt-2">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search..." onkeyup="filterTable()">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">✕</button>
                        </div>
                    </form>

                    <table id="studentTable" class="table table-bordered table-custom" style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));">
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
                        <tbody id="dataBody">
                            <?php if (empty($results)) : ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No data to show</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($results as $row) : ?>
                                    <tr style="text-align: center;">
                                        <td>
                                            <?php $module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null; ?>
                                            <a href="student_record_test.php?student_id=<?php echo $row['stud_id']; ?>&module_id=<?php echo $module_id; ?>">
                                                <?php echo htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $row['module_name']; ?></td>
                                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS attempts FROM tbl_result WHERE stud_id = :stud_id AND module_id = :module_id AND quiz_type = 1");
                                            $stmtAttempts->bindValue(':stud_id', $row['stud_id']);
                                            $stmtAttempts->bindValue(':module_id', $module_id);
                                            if ($stmtAttempts->execute()) {
                                                $attemptsData = $stmtAttempts->fetch(PDO::FETCH_ASSOC);
                                                echo $attemptsData['attempts'];
                                            } else {
                                                echo "Error";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stmtAttempts = $conn->prepare("SELECT COUNT(*) AS attempts FROM tbl_result WHERE stud_id = :stud_id AND module_id = :module_id AND quiz_type = 1");
                                            $stmtAttempts->bindValue(':stud_id', $row['stud_id']);
                                            $stmtAttempts->bindValue(':module_id', $module_id);
                                            if ($stmtAttempts->execute()) {
                                                $attemptsData = $stmtAttempts->fetch(PDO::FETCH_ASSOC);
                                                $attempts = $attemptsData['attempts'];
                                                $passRate = ($attempts != 0) ? number_format(100 / $attempts, 2) : 0;
                                                echo $passRate . "%";
                                            } else {
                                                echo "Error";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tbody id="noDataRow" style="display: none;">
                            <tr>
                                <td colspan="5" style="text-align: center;">No data found</td>
                            </tr>
                        </tbody>
                    </table>

                    <script>
                        function filterTable() {
                            const input = document.getElementById("searchInput");
                            const filter = input.value.toLowerCase();
                            const dataBody = document.getElementById("dataBody");
                            const rows = dataBody.getElementsByTagName("tr");
                            const noDataRow = document.getElementById("noDataRow");

                            let visibleCount = 0;

                            for (let i = 0; i < rows.length; i++) {
                                const row = rows[i];
                                const cells = row.getElementsByTagName("td");
                                let matchFound = false;

                                for (let j = 0; j < cells.length; j++) {
                                    const cell = cells[j];
                                    if (cell && cell.textContent.toLowerCase().includes(filter)) {
                                        matchFound = true;
                                        break;
                                    }
                                }

                                row.style.display = matchFound ? "" : "none";
                                if (matchFound) visibleCount++;
                            }

                            noDataRow.style.display = visibleCount === 0 ? "" : "none";
                        }

                        function clearSearch() {
                            document.getElementById("searchInput").value = "";
                            filterTable();
                        }
                    </script>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&course_id=<?php echo $course_id; ?>&module_id=<?php echo $module_id; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
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


        // Add an event listener to the search input field
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value.trim(); // Trim whitespace from the input value
            fetchSearchResults(searchValue); // Call function to fetch search results
        });

        // Function to fetch search results via AJAX
        function fetchSearchResults(searchQuery) {
            // Make an AJAX request to the server
            fetch(`search.php?search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json()) // Parse response as JSON
                .then(data => {
                    // Update HTML content with the filtered results
                    // You need to implement this based on your specific HTML structure
                    console.log(data); // Log the fetched data for testing
                })
                .catch(error => console.error('Error fetching search results:', error));
        }
    </script>



</body>

</html>