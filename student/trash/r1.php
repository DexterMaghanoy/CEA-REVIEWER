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
<html>
<script src="https://d3js.org/d3.v4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/billboard.js/dist/billboard.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/billboard.js/dist/billboard.min.css" />
<link rel="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" type="text/css" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js">
</script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js">
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.1/Chart.min.js">
</script>

<style>
	body {
		text-align: center;
		color: green;
	}

	h2 {
		text-align: center;
		font-family: "Verdana", sans-serif;
		font-size: 40px;
	}
</style>

<body>
	<div class="wrapper">
		<div class="container">
			<div class="row">
				<div class="col-sm">
					Right Side
				</div>
				<div class="col-sm">
					<div class="row">
						<div class="col-xs-12 text-center">
							<h2>Donut Chart</h2>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6"></div>
						<div class="col-sm-6">
							<div id="donut-chart"></div>
							<script>
								let chart = bb.generate({
									data: {
										columns: [
											["Blue", 2],
											["orange", 4],
											["green", 3],
										],
										type: "donut",
										onclick: function(d, i) {
											console.log("onclick", d, i);
										},
										onover: function(d, i) {
											console.log("onover", d, i);
										},
										onout: function(d, i) {
											console.log("onout", d, i);
										},
									},
									donut: {
										title: "Quiz Rating",
									},
									bindto: "#donut-chart",
								});
							</script>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


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