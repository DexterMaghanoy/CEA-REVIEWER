<?php
session_start();

require("api/db-connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $user_id = $_SESSION['user_id'];

    // Check if the current password and the new password are the same
    if ($currentPassword === $newPassword) {
        echo 'Current password and new password must not be the same. Please try another password.';
        exit();
    }

    // Perform validation here

    // Check if the new password matches the confirm password
    if ($newPassword === $confirmPassword) {
        // Prepare and execute the SQL query
        $sql = "UPDATE tbl_user 
                SET user_password = :newPassword 
                WHERE user_id = :user_id AND user_password = :currentPassword";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':newPassword', $newPassword); // Using the new password directly
        $stmt->bindParam(':user_id', $user_id );
        $stmt->bindParam(':currentPassword', $currentPassword);
        
        if ($stmt->execute()) {
            // Password updated successfully
            echo "Password updated successfully.";
        } else {
            // Error updating password
            echo "Error updating password.";
        }
    } else {
        // New password and confirm password do not match
        echo "New password and confirm password do not match.";
    }
} else {
    // Redirect if accessed directly
    header("Location: index.php");
    exit();
}
?>
