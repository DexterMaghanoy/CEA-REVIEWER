<?php
require("../api/db-connect.php");
session_start();

$recordsPerPage = 7;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// SQL with search filter
$sql = "SELECT s.*, p.program_name
        FROM tbl_student AS s
        JOIN tbl_program AS p ON s.program_id = p.program_id";

if (!empty($search)) {
    $sql .= " WHERE s.stud_lname LIKE :search OR s.stud_fname LIKE :search OR s.stud_mname LIKE :search OR s.stud_no LIKE :search";
}

$sql .= " ORDER BY s.stud_status DESC LIMIT :offset, :recordsPerPage";
$result = $conn->prepare($sql);

if (!empty($search)) {
    $searchParam = "%$search%";
    $result->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
$result->bindParam(':offset', $offset, PDO::PARAM_INT);
$result->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
$result->execute();

// Pagination
$countSql = "SELECT COUNT(*) as total FROM tbl_student";
if (!empty($search)) {
    $countSql .= " WHERE stud_lname LIKE :search OR stud_fname LIKE :search OR stud_mname LIKE :search OR stud_no LIKE :search";
}
$countStmt = $conn->prepare($countSql);
if (!empty($search)) {
    $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}
$countStmt->execute();
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $recordsPerPage);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/cea_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css" type="text/css">



    <style>
        .table-custom {
            background: linear-gradient(to left, rgba(220, 210, 211, 0.3), rgba(200, 240, 241, 0.3));
        }

        @media (max-width: 768px) {
            .table thead {
                display: none;
            }

            .table,
            .table tbody,
            .table tr,
            .table td {
                display: block;
                width: 100%;
            }

            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 50%;
                padding-left: 15px;
                font-weight: bold;
                text-align: left;
            }
        }
    </style>
</head>

<body>


    <div class="wrapper">
        <?php
        include 'sidebar.php';
        ?>
        <?php
        include 'back.php';
        ?>



        <div class="container mt-4">
            <h1 class="text-center">Students</h1>
            <div class="d-flex mb-3">
                <a class="btn btn-outline-success btn-sm me-2" href="add_student.php"><i class="lni lni-plus"></i></a>
                <a class="btn btn-outline-primary btn-sm" href="import_student.php"><i class="lni lni-upload"></i></a>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 offset-md-6">
                    <input type="text" id="liveSearch" class="form-control" placeholder="Search...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-custom">
                    <caption>List of Students</caption>
                    <thead class="table-dark">
                        <tr>
                            <th>Student No.</th>
                            <th>Program</th>
                            <th>Fullname</th>
                            <th>Action</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="studentTable">
                        <?php if ($result->rowCount() > 0): ?>
                            <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td data-label="Student No."><?php echo htmlspecialchars($row['stud_no']); ?></td>
                                    <td data-label="Program"><?php echo htmlspecialchars($row['program_name']); ?></td>
                                    <td data-label="Fullname"><?php echo htmlspecialchars($row['stud_lname'] . ', ' . $row['stud_fname'] . ' ' . $row['stud_mname']); ?></td>
                                    <td data-label="Action">
                                        <a class="btn btn-info btn-sm" href="edit_student.php?stud_id=<?php echo $row['stud_id']; ?>"><i class="lni lni-pencil"></i></a>
                                        <a href="student_record_test.php?student_id=<?php echo $row['stud_id']; ?>" class="btn btn-primary btn-sm"><i class="lni lni-eye"></i></a>
                                    </td>
                                    <td data-label="Status">
                                        <button type="button"
                                            class="btn btn-sm toggle-status-btn <?php echo $row['stud_status'] == 1 ? 'btn-success' : 'btn-warning'; ?>"
                                            data-id="<?php echo $row['stud_id']; ?>">
                                            <i class="lni lni-checkmark-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

    <script>
        // AJAX Status Toggle
        document.querySelectorAll('.toggle-status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const studId = this.dataset.id;
                const btn = this;

                fetch('toggle_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `stud_id=${studId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            btn.classList.toggle('btn-success', data.new_status == 1);
                            btn.classList.toggle('btn-warning', data.new_status == 0);
                        } else {
                            alert("Failed to toggle status.");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Live Filter on Keypress
        document.getElementById("liveSearch").addEventListener("input", function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll("#studentTable tr");

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? "" : "none";
            });
        });
    </script>
</body>

</html>