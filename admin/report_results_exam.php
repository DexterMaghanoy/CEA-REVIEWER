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
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * 10;

$resultsPerPage = 10;
if ($user_id) {
    // Modified SQL query to include condition for quiz_type = 3
    $sql = "SELECT r.stud_id, s.stud_fname, s.stud_mname, s.stud_lname, r.created_at, r.result_score, r.total_questions, r.quiz_type, COUNT(*) AS attempts,
    (SELECT COUNT(*) FROM tbl_result WHERE stud_id = r.stud_id AND quiz_type = 3 AND result_status = 1) / COUNT(*) * 100 AS success_rate
FROM tbl_result r
INNER JOIN tbl_student s ON r.stud_id = s.stud_id
WHERE r.quiz_type = 3
GROUP BY r.stud_id 
ORDER BY success_rate DESC
LIMIT $resultsPerPage OFFSET $offset ";

    // Prepare and execute the SQL query
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total pages for pagination
    $totalResults = count($results);
    $totalPages = ceil($totalResults / $resultsPerPage);
} else {
    // Redirect if user ID is not set in the session
    header("Location: ../index.php");
    exit();
}

// Fetch all courses regardless of the program
$sql = "SELECT * FROM tbl_course where course_status = 1";
$result = $conn->prepare($sql);
$result->execute();
$courses = $result->fetchAll(PDO::FETCH_ASSOC);

// Process data for graph
$graphData = [];
foreach ($results as $row) {
    // Calculate pass rate based on attempts
    $passRate = ($row['attempts'] > 0) ? (100 / $row['attempts']) : 0;

    // Set success rate to 0 if result_status is 0
    if ($row['success_rate'] == 0) {
        $passRate = 0;
    }

    // Add data to graphData array
    $graphData[] = [
        'Student Name' => $row['stud_fname'] . ' ' . $row['stud_lname'],
        'Score' => $passRate,
        'Tooltip' => number_format($passRate, 2) . '%' // Format tooltip
    ];
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
        <?php include 'sidebar.php'; ?>
        <?php include 'back.php'; ?>

        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-8">
                    <div class="text-center mb-4">
                        <h1>EXAM</h1>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm">
                    <div id="myChart" style="border: 1px solid lightblue; padding: 10px; box-sizing: border-box; border-radius: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width:100%; max-width:600px; height:500px;"></div>





                    <script>
                        google.charts.load('current', {
                            'packages': ['corechart']
                        });
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {
                            const data = new google.visualization.DataTable();
                            data.addColumn('string', 'Student Name');
                            data.addColumn('number', 'Success Rate');

                            <?php foreach ($graphData as $row) : ?>




                                data.addRow([
                                    '<?php echo htmlspecialchars($row['Student Name']); ?>',
                                    <?php
                                    echo $row['Score']; ?> // Ensure to use 'Score' instead of 'Success Rate'
                                ]);
                            <?php endforeach; ?>

                            const options = {
                                title: 'Student Performance by Module',
                                hAxis: {
                                    title: 'Success Rate',
                                    minValue: 0,
                                    maxValue: 100
                                },
                                vAxis: {
                                    title: 'Student Name'
                                },
                                chartArea: {
                                    width: '50%',
                                    height: '70%'
                                }
                            };

                            const chart = new google.visualization.BarChart(document.getElementById('myChart'));
                            chart.draw(data, options);
                        }
                    </script>


                </div>

                <div class="col-sm">
                    <!-- Table of Student Performance -->
                    <!-- Table of Student Performance -->
                    <table class="table table-bordered table-custom">
                        <caption>List of Student Performance</caption>
                        <thead class="table-dark">
                            <tr style="text-align: center;">
                                <th scope="col">Student Name</th>
                                <th scope="col">Date</th>
                                <th scope="col">Attempts</th>
                                <th scope="col">Success Rate</th> <!-- Changed 'Rate' to 'Success Rate' for clarity -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)) : ?>
                                <tr style="text-align: center;">
                                    <td colspan="4">No records found</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($results as $row) : ?>
                                    <!-- Wrap the row with the link -->
                                    <tr style="text-align: center;">

                                        <td>
                                            <a href="student_record_exam.php?student_id=<?php echo $row['stud_id']; ?>">

                                                <?php echo htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']); ?>
                                            </a>

                                        </td>
                                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['attempts']); ?></td>
                                        <td>
                                            <?php
                                            $sqlForResultStatusOne = "SELECT COUNT(*) AS success_count FROM tbl_result WHERE stud_id = :stud_id AND quiz_type = 3 AND result_status = 1";
                                            $stmtForResultStatusOne = $conn->prepare($sqlForResultStatusOne);
                                            $stmtForResultStatusOne->bindParam(':stud_id', $row['stud_id']);
                                            $stmtForResultStatusOne->execute();
                                            $resultForResultStatusOne = $stmtForResultStatusOne->fetch(PDO::FETCH_ASSOC);
                                            if ($resultForResultStatusOne['success_count'] > 0) {
                                                $successRate = ($resultForResultStatusOne['success_count'] / $row['attempts']) * 100;
                                                echo number_format($successRate, 2) . '%';
                                            } else {
                                                echo '0.0%';
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add an event listener to the search input field
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value.trim();
            fetchSearchResults(searchValue);
        });

        // Function to fetch search results via AJAX
        function fetchSearchResults(searchQuery) {
            fetch(`search.php?search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                })
                .catch(error => console.error('Error fetching search results:', error));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>

</html>


<script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
        document.querySelector("#sidebar").classList.toggle("expand");
    });
</script>