<?php
session_start();

require '../api/db-connect.php';

if(isset($_SESSION['program_id'])){
    $program_id = $_SESSION['program_id'];
} else {
    header("Location: ../login.php");
    exit();
}

// Retrieve module_id from URL query parameter
if(isset($_GET['module_id'])) {
    $module_id = $_GET['module_id'];

    // Query to retrieve module_file based on module_id
    $sql = "SELECT * FROM tbl_module WHERE module_id = :module_id";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":module_id", $module_id);
    $stmt->execute();

    // Check if query execution is successful
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set appropriate HTTP headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $row['module_name'] . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');

        // Output PDF content
        echo $row['module_file'];
        exit();
    } else {
        echo "No module found with the given ID.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $row['module_name']?></title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        #pdfContainer {
            width: 100%;
            height: 100%;
        }
        #pdfViewer {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div id="pdfContainer">
        <embed id="pdfViewer" src="data:application/pdf;base64,<?php echo base64_encode($row['module_file']); ?>" type="application/pdf"/>
    </div>
</body>
</html>