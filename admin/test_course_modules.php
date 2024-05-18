<?php
session_start();

require '../api/db-connect.php';

// Redirect to login page if session data is not set
if (!isset($_SESSION['program_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * 10;

// Retrieve the module name if module_id is set
$module_name = '';
if ($module_id) {
    $stmt = $conn->prepare("SELECT module_name FROM tbl_module WHERE module_id = :module_id");
    $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
    $stmt->execute();
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($module) {
        $module_name = $module['module_name'];
    }
}

// Retrieve modules for the specified course
if ($course_id) {
    $stmt = $conn->prepare("SELECT * FROM tbl_module WHERE course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_module");
}
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination
$resultsPerPage = 10;
if ($user_id) {
    // Main query for retrieving results with provided filters
    $sql = "SELECT r.stud_id, s.stud_fname, s.stud_mname, s.stud_lname, c.course_name, m.module_name, r.created_at, r.total_questions, r.result_score, SUM(r.result_score) AS total_score
            FROM tbl_result r
            INNER JOIN tbl_student s ON r.stud_id = s.stud_id
            INNER JOIN tbl_module m ON r.module_id = m.module_id
            INNER JOIN tbl_course c ON m.course_id = c.course_id
            WHERE result_status = 1";

    // Add conditions to filter by course ID and module ID if they are provided
    if ($course_id) {
        $sql .= " AND m.course_id = :course_id";
    }
    if ($module_id) {
        $sql .= " AND r.module_id = :module_id";
    }

    $sql .= " GROUP BY r.stud_id, c.course_id";

    // Count query to calculate the total number of results with provided filters
    $countQuery = "SELECT COUNT(*) AS count FROM (
                   SELECT r.stud_id
                   FROM tbl_result r
                   INNER JOIN tbl_student s ON r.stud_id = s.stud_id
                   INNER JOIN tbl_module m ON r.module_id = m.module_id
                   INNER JOIN tbl_course c ON m.course_id = c.course_id
                   WHERE result_status = 1";

    // Add conditions to count query
    if ($course_id) {
        $countQuery .= " AND m.course_id = :course_id";
    }
    if ($module_id) {
        $countQuery .= " AND r.module_id = :module_id";
    }

    $countQuery .= " GROUP BY r.stud_id, c.course_id ) AS sub";

    $stmtCount = $conn->prepare($countQuery);
    if ($course_id) {
        $stmtCount->bindValue(':course_id', $course_id);
    }
    if ($module_id) {
        $stmtCount->bindValue(':module_id', $module_id);
    }
    $stmtCount->execute();
    $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $totalCount = $countResult['count'];
    $totalPages = ceil($totalCount / $resultsPerPage);

    // Add pagination and execute main query
    $sql .= " LIMIT $resultsPerPage OFFSET $offset";

    $stmt = $conn->prepare($sql);
    if ($course_id) {
        $stmt->bindValue(':course_id', $course_id);
    }
    if ($module_id) {
        $stmt->bindValue(':module_id', $module_id);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch the results
} else {
    // Redirect if user ID is not set in the session
    header("Location: ../index.php");
    exit();
}

// Fetch all courses regardless of the program
$sql = "SELECT * FROM tbl_course";
$result = $conn->prepare($sql);
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
        <?php
        include 'back.php';
        ?>

        <div class="container">


            <div class="row justify-content-center mt-5">
                <div class="col-md-8">
                    <div class="text-center mb-4">
                        <?php if (!empty($results)) : ?>
                            <?php foreach ($results as $row) : ?>
                                <h1><?php echo htmlspecialchars($row['module_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <h1><?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row">

                <div class="col-sm">
                    <div id="myChart" style="border: 1px solid lightblue; /* Adds a light blue border for emphasis */
                padding: 10px; /* Optional: Adds some padding inside the div */
                box-sizing: border-box; /* Ensures padding and border are included in the element's total width and height */
                border-radius: 15px; /* Makes the border rounded */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Adds a subtle box shadow */
                width:100%; max-width:600px; height:500px;"></div>

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
                    <?php
                    include 'module_dropdown.php';
                    ?>
                    <!-- <form id="searchForm" class="mb-3">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" name="search" placeholder="Search..." value="<?php echo $search; ?>">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form> -->

                    <table style="background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));" class="table table-bordered table-custom">
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
                            <?php if (empty($results)) : ?>
                                <tr style="text-align: center;">
                                    <td colspan="5">No records found</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($results as $row) : ?>
                                    <tr style="text-align: center;">
                                        <td><?php echo htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']); ?></td>
                                        <td><?php echo htmlspecialchars($row['module_name']); ?></td>
                                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                        <td>
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
                                                echo htmlspecialchars($attempts);
                                            }
                                            ?>
                                        </td>
                                        <td>
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
                                                echo htmlspecialchars($passRate) . "%";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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