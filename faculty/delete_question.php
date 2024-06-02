<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../index.php");
    exit();
}

// Retrieve the 'question_id' from the URL parameters
if (isset($_GET['question_id'])) {
    $question_id = $_GET['question_id'];
} else {
    header("Location: ../error.php");
    exit();
}

if (empty($question_id)) {
    echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
    echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
    echo '<script>
            $(document).ready(function(){
                Swal.fire({
                    title: "Failed!",
                    text: "No Question ID found.",
                    icon: "error"
                }).then(() => {
                    window.location.href = "question.php?program_id=' . $_SESSION['program_id'] . '&course_id=' . $_SESSION['course_id'] . '&module_id=' . $_GET['module_id'] . '";
                });
            });
        </script>';
} else {
    // Start transaction
    $conn->beginTransaction();

    try {
        // Delete related records in tbl_quiz_answers
        $sql = "DELETE FROM `tbl_quiz_answers` WHERE question_id = :question_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":question_id", $question_id);
        $stmt->execute();

        // Delete record in tbl_question
        $sql = "DELETE FROM `tbl_question` WHERE question_id = :question_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":question_id", $question_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>';
        echo '<link href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css" rel="stylesheet">';
        echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Success!",
                        text: "Deleted successfully.",
                        icon: "success"
                    }).then(() => {
                        window.location.href = "question.php?program_id=' . $_SESSION['program_id'] . '&course_id=' . $_SESSION['course_id'] . '&module_id=' . $_SESSION['module_id'] . '";
                    });
                });
            </script>';
    } catch (PDOException $e) {
        // Rollback transaction if an error occurs
        $conn->rollBack();

        echo '<script>
                $(document).ready(function(){
                    Swal.fire({
                        title: "Failed!",
                        text: "Failed to delete: ' . $e->getMessage() . '",
                        icon: "error"
                    }).then(() => {
                        window.location.href = "question.php?program_id=' . $_SESSION['program_id'] . '&course_id=' . $_SESSION['course_id'] . '&module_id=' . $_SESSION['module_id'] . '";
                    });
                });
            </script>';
    }
}
?>
