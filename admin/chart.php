<!DOCTYPE html>
<html>
<head>
    <title>Pie Chart with Legend on Right</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #chart-container {
            display: flex;
            align-items: center;
        }
        #myPieChart {
            width: 70%;
            max-width: 400px;
        }
        #legend-container {
            width: 30%;
            max-width: 200px;
        }
        .chart-legend {
            list-style: none;
            padding: 10;
        }
        .chart-legend li {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .chart-legend li span {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div id="chart-container">
        <canvas id="myPieChart"></canvas>
        <div id="legend-container">
            <ul class="chart-legend" id="chart-legend"></ul>
        </div>
    </div>
    <script>
        var ctx = document.getElementById('myPieChart').getContext('2d');
        var data = {
            labels: ['Bitcoin', 'Ethereum', 'Ripple', 'Litecoin'],
            datasets: [{
                data: [10, 20, 40, 60],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
            }]
        };

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false, // Hide default legend
                },
            },
        };

        var myPieChart = new Chart(ctx, {
            type: 'pie',
            data: data,
            options: options
        });

        // Generate custom legend
        var legendContainer = document.getElementById('chart-legend');
        data.labels.forEach((label, index) => {
            var li = document.createElement('li');
            var colorBox = document.createElement('span');
            colorBox.style.backgroundColor = data.datasets[0].backgroundColor[index];
            li.appendChild(colorBox);
            li.appendChild(document.createTextNode(label + ': ' + data.datasets[0].data[index] + '%'));
            legendContainer.appendChild(li);
        });
    </script>
</body>
</html>
