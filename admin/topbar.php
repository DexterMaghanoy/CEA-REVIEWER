<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navigation Bar</title>
  <style>
    .navbar-custom {
      background-color: rgba(45, 24, 230, 0.8);
      border-radius: 8px;
      padding: 10px 20px;
    }

    .navbar-toggler {
      border: none;
      outline: none;
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 255, 255, 0.8)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
    }

    .nav-item .nav-link {
      color: #fff;
      transition: background-color 0.3s ease;
    }

    .nav-item:hover .nav-link,
    .nav-item:focus .nav-link {
      background-color: rgba(255, 255, 255, 0.3);
      border-radius: 8px;
    }

    .nav-item:active .nav-link {
      background-color: rgba(255, 255, 255, 0.6);
    }

    .dropdown:hover .dropdown-menu {
      display: block;
    }

    .year-picker-container {
      margin-left: auto;
    }

    .year-picker {
      padding: 5px 10px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    .custom-radio {
      display: flex;
      align-items: center;
      padding: 5px 10px;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }

    .custom-radio input {
      margin-right: 8px;
    }

    .custom-radio:hover,
    .custom-radio:focus-within {
      background-color: rgba(255, 255, 255, 0.3);
    }

    .custom-radio input:checked + i {
      color: #28a745;
    }
  </style>
</head>
<body>

<?php
// Extract the URL parameters
$programId = isset($_GET['program_id']) ? htmlspecialchars($_GET['program_id']) : '';
$quizType = isset($_GET['quiz_type']) ? htmlspecialchars($_GET['quiz_type']) : '';
?>

<nav class="navbar navbar-expand-lg navbar-custom mb-2">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php echo $programId ? $programId : 'Course'; // Placeholder or the selected course name ?>
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <?php
            // Assuming $conn is your database connection
            $stmtTotalStudents = $conn->prepare("SELECT program_id, program_name FROM tbl_program WHERE program_status = 1");
            $stmtTotalStudents->execute();
            $results = $stmtTotalStudents->fetchAll(PDO::FETCH_ASSOC);

            // Generate dropdown items dynamically
            foreach ($results as $stmtTotalStudent) {
              $programIdValue = htmlspecialchars($stmtTotalStudent['program_id']);
              $programName = htmlspecialchars($stmtTotalStudent['program_name']);
            ?>
              <a class="dropdown-item" href="#" data-program-id="<?php echo $programIdValue; ?>">
                <?php echo $programName; ?>
              </a>
            <?php
            }
            ?>
          </div>
        </li>

        <li style="padding-left: 50px;" class="nav-item">
          <label class="nav-link custom-radio">
            <input type="radio" name="navigation" id="testRadio" value="1" <?php echo $quizType == '1' ? 'checked' : ''; ?>>
            <i class="fas fa-check-circle"></i> Test
          </label>
        </li>

        <li class="nav-item">
          <label class="nav-link custom-radio">
            <input type="radio" name="navigation" id="quizRadio" value="2" <?php echo $quizType == '2' ? 'checked' : ''; ?>>
            <i class="fas fa-check-circle"></i> Quiz
          </label>
        </li>

        <li class="nav-item">
          <label class="nav-link custom-radio">
            <input type="radio" name="navigation" id="examRadio" value="3" <?php echo $quizType == '3' ? 'checked' : ''; ?>>
            <i class="fas fa-check-circle"></i> Exam
          </label>
        </li>
      </ul>
    </div>

    <div class="year-picker-container">
      <select id="yearPicker" class="year-picker">
        <!-- Years will be populated by JavaScript -->
      </select>
    </div>
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

<!-- Hidden input to store the selected program_id -->
<input type="hidden" id="selectedProgramId" value="<?php echo $programId; ?>">

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
  $(document).ready(function() {
    // Set the dropdown button text based on the selected program
    var selectedProgramId = $('#selectedProgramId').val();
    if (selectedProgramId) {
      var selectedProgramText = $('.dropdown-item[data-program-id="' + selectedProgramId + '"]').text();
      if (selectedProgramText) {
        $('.dropdown-toggle').text(selectedProgramText);
      }
    }

    // Handle dropdown selection
    $('.dropdown-item').click(function(e) {
      e.preventDefault(); // Prevent the default link behavior
      var programId = $(this).data('program-id');
      $('#selectedProgramId').val(programId);
      var programName = $(this).text(); // Get the text of the clicked item
      $('.dropdown-toggle').text(programName); // Set the dropdown button text to the selected program name

      // Unselect all radio buttons
      $('input[name="navigation"]').prop('checked', false);
    });

    // Handle radio button selection
    $('input[name="navigation"]').change(function() {
      var quizType = $(this).val();
      var programId = $('#selectedProgramId').val();
      if (programId) {
        var url = 'http://localhost/cea-reviewer/admin/report.php?program_id=' + programId + '&quiz_type=' + quizType;
        window.location.href = url;
      } else {
        alert('Please select a course first.');
      }
    });
  });
</script>

</body>
</html>
