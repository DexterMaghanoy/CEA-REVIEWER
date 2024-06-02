<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floating Action Button</title>
    <link rel="stylesheet" href="styles.css">
</head>







<style>
    .container {
        position: relative;
        width: 100%;
        height: 100vh;
        /* Just for demonstration */
        padding: 20px;
    }

    .fab {
        position: absolute;
        top: 20px;
        left: 20px;
        /* Adjusted to be on the left */
        width: 60px;
        height: 60px;
        background-color: #007bff;
        color: #fff;
        border-radius: 50%;
        font-size: 36px;
        text-align: center;
        line-height: 60px;
        text-decoration: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .fab:hover {
        background-color: #0056b3;
    }
</style>





<body>
    <div class="container">
        <h1>Content Goes Here</h1>
        <a href="#" class="fab">+</a>
    </div>
</body>

</html>