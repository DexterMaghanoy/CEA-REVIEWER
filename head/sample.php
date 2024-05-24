<?php
require("../api/db-connect.php");
session_start();

// Ensure user is logged in
if (!isset($_SESSION['program_id']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user details including the image
    $sql = "SELECT u.*, t.type_name, p.program_name, u.user_image, u.user_image_type
            FROM tbl_user u
            INNER JOIN tbl_type t ON u.type_id = t.type_id
            INNER JOIN tbl_program p ON u.program_id = p.program_id
            WHERE u.user_id = :user_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Check if the query returned a result
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user_image is not empty
        if (!empty($user['user_image'])) {
            // Display the image using base64 encoding
            // Ensure the correct MIME type is used
            $mimeType = !empty($user['user_image_type']) ? $user['user_image_type'] : 'image/jpeg';
            echo '<img src="data:' . htmlspecialchars($mimeType) . ';base64,' . base64_encode($user['user_image']) . '" alt="Avatar" class="rounded-circle img-fluid my-5" style="width: 100px;">';
        } else {
            echo "No image found for the user.";
        }
    } else {
        echo "User not found.";
    }
} catch (PDOException $e) {
    // Log error to a file or monitoring system
    error_log($e->getMessage());

    // Display a user-friendly message
    echo "An error occurred while fetching user details. Please try again later.";
}
?>
