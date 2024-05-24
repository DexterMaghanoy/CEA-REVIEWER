<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pic";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_POST["submit"])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        // Open the file and read its contents
        $imageData = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);

        // Escape special characters in the image data
        $escapedImageData = $conn->real_escape_string($imageData);

        // Insert the image data into the database
        $sql = "INSERT INTO upload_view_pic (file, image_data) VALUES ('$target_file', '$escapedImageData')";
        if ($conn->query($sql) === TRUE) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded and stored in the database.";
        } else {
            echo "Error inserting record: " . $conn->error;
        }
    }
}

$conn->close();
?>
