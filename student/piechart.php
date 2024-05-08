<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pie Chart</title>
<style>
    .chart-container {
        position: relative;
        width: 300px; /* Adjust the width as needed */
        height: 300px; /* Adjust the height as needed */
    }

    .chart {
        position: absolute;
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }

    .slice {
        position: absolute;
        width: 100%;
        height: 100%;
        clip: rect(0, 150px, 300px, 0); /* Adjust the clipping for different slices */
        border-radius: 50%;
    }

    .slice:nth-child(1) {
        background-color: #ff6347; /* Adjust colors for different slices */
        transform: rotate(0deg);
    }

    .slice:nth-child(2) {
        background-color: #4682b4; /* Adjust colors for different slices */
        transform: rotate(90deg);
    }

    /* Add more slice styles for additional data */
</style>
</head>
<body>

<div class="chart-container">
    <div class="chart">
        <div class="slice"></div> <!-- Adjust the number of slices as needed -->
        <div class="slice"></div>
        <!-- Add more slices for additional data -->
    </div>
</div>

</body>
</html>
