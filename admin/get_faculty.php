<?php
// get_faculty.php

// Include database connection
require("../api/db-connect.php");

// Check if program_id parameter is provided
if (isset($_GET['program_id'])) {
    // Retrieve program_id from GET parameter
    $programId = $_GET['program_id'];

    // Query database to fetch faculty members with matching program_id
    $sql = "SELECT user_id, user_lname, user_fname, user_mname FROM tbl_user WHERE program_id = :program_id AND type_id = 3";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':program_id', $programId, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send the list of users as JSON response
    header('Content-Type: application/json');
    echo json_encode($users);
} else {
    // Error handling for missing program_id parameter
    http_response_code(400);
    echo json_encode(array('error' => 'Missing program_id parameter'));
}
?>
