<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Year Picker</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: #f0f0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .navbar-custom {
      background-color: #4CAF50;
      border-radius: 8px;
      padding: 10px 20px;
      color: white;
      text-align: center;
      font-size: 1.2em;
      margin-bottom: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .year-picker-container {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    .year-picker {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background: white url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjggMTI4Ij48cGF0aCBkPSJNNi41IDY0Ljc1TDY0IDEyMi41bDU3LjQ5LTU3LjQ5YTEuNSAxLjUgMCAwIDAgMC0yLjEyTDY0IDExOS4yNSAxMCA1MC43NWEuMTMuMTMgMCAwIDAtLjAzIDAuMDZsLS4wNiAwLjA1YTEuNSA1IDEgMCAwIDAgMCAyLjEyWiIvPjwvc3ZnPg==') no-repeat right 10px center;
      background-size: 16px;
      width: 200px;
      font-size: 1em;
      color: #333;
      cursor: pointer;
      transition: border 0.3s ease;
    }

    .year-picker:hover {
      border-color: #4CAF50;
    }

    .year-picker:focus {
      outline: none;
      border-color: #4CAF50;
      box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
    }
  </style>
</head>
<body>

<nav class="navbar-custom">
 <div class="year-picker-container">
  <select id="yearPicker" class="year-picker">
    <!-- Years will be populated by JavaScript -->
  </select>
</div>
</nav>



<script>
  document.addEventListener('DOMContentLoaded', function() {
    var yearPicker = document.getElementById('yearPicker');
    var currentYear = new Date().getFullYear();
    var startYear = 2000; // You can set this to any start year you prefer

    for (var year = currentYear; year >= startYear; year--) {
      var option = document.createElement('option');
      option.value = year;
      option.textContent = year;
      yearPicker.appendChild(option);
    }

    yearPicker.addEventListener('change', function() {
      var selectedYear = this.value;
      if (selectedYear) {
        var url = 'http://localhost/cea-reviewer/admin/report.php?year=' + selectedYear;
        window.location.href = url;
      }
    });
  });
</script>

</body>
</html>
