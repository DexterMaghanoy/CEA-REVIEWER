<?php
session_start();

require '../api/db-connect.php';

if (isset($_SESSION['program_id'])) {
	$program_id = $_SESSION['program_id'];
	$year_id = $_SESSION['year_id'];

	// Prepare SQL query to fetch courses for the given program and year
	$sql = "SELECT *, COUNT(r.result_id) AS quiz_count
            FROM tbl_course c
            LEFT JOIN tbl_result r ON c.course_id = r.course_id AND r.quiz_type = 2
            WHERE c.program_id = :program_id
            GROUP BY c.course_id";
	$result = $conn->prepare($sql);
	$result->bindParam(':program_id', $program_id, PDO::PARAM_INT);

	$result->execute();

	// Fetch the result and store it in a variable to use later
	$courses = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
	header("Location: ../index.php");
	exit();
}

?>


<!DOCTYPE html>
<html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>

<body>

	<canvas id="myChart" style="width:100%;max-width:600px"></canvas>

	<script>
		const xValues = ["Italy", "France", "Spain", "USA", "Argentina"];
		const PercentSign = '%';
		const yValues = [200 + PercentSign, 49 + PercentSign, 44 + PercentSign, 24 + PercentSign, 15 + PercentSign];
		const barColors = ["red", "green", "blue", "orange", "brown"];

		new Chart("myChart", {
			type: "horizontalBar",
			data: {
				labels: xValues,
				datasets: [{
					backgroundColor: barColors,
					data: yValues
				}]
			},
			options: {
				legend: {
					display: false
				},
				title: {
					display: true,
					text: "World Wine Production 2018"
				},
				scales: {
					xAxes: [{
						ticks: {
							beginAtZero: true
						}
					}],
					yAxes: [{
						ticks: {
							beginAtZero: true
						}
					}]
				},
				tooltips: {
					callbacks: {
						label: function(tooltipItem, data) {
							return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
						}
					}
				}
			}
		});
	</script>



</body>

</html>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
<script>
	const hamBurger = document.querySelector(".toggle-btn");
	const sidebar = document.querySelector("#sidebar");
	const mainContent = document.querySelector(".main");

	hamBurger.addEventListener("click", function() {
		sidebar.classList.toggle("expand");
		mainContent.classList.toggle("expand");
	});
</script>