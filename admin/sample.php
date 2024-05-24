<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload and Display</title>
</head>

<body>
    <h2>Upload Image</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        Select image to upload:
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Upload Image" name="submit">
    </form>

    <h2>Uploaded Images</h2>
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


    $sql = "SELECT * FROM upload_view_pic";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<img src="data:image/jpeg;base64,' . base64_encode($row['image_data']) . '" alt="Uploaded Image">';
        }
    } else {
        echo "No images uploaded yet.";
    }

    $conn->close();
    ?>
</body>

</html>