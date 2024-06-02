<style>
    /* Define the transition effect */
    .back-button {
        transition: transform 0.3s, background-color 0.3s;
        /* Add background-color to the transition */
    }

    /* Define the hover effect */
    .back-button:hover {
        transform: scale(1.3);
        /* Increase size on hover */
        background-color: rgba(0, 0, 0, 0.1);
        /* Darker background color */
    }

    /* Define the active effect (when button is clicked) */
    .back-button:active {
        transform: scale(0.9);
        /* Decrease size when clicked */
    }


    /* Rest of your CSS remains unchanged */
</style>


<div style="white-space: nowrap;">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    <button style="font-size: 40px; background: none; border: none; padding-left: 15px; margin-left: 15px;" onclick="goBack()" class="back-button"><i class="bi bi-arrow-left-circle"></i></button>
</div>
<script>
    function goBack() {
        // List of pages to redirect from
        const pages = ["profile.php", "user.php", "student.php", "courses.php", "subjects.php", "report.php"];

        // Get the current page URL
        const currentPage = window.location.href;

        if (pages.some(page => currentPage.includes(page))) {
            window.location = "index.php";
        } else if (currentPage.includes("add_faculty.php") || currentPage.includes("edit_faculty.php")) {
            window.location = "user.php";
        } else if (currentPage.includes("add_student.php") || currentPage.includes("import_student.php") ||
            currentPage.includes("student_record_test.php") ||
            currentPage.includes("student_record_quiz.php") || currentPage.includes("student_record_exam.php")) {
            window.location = "student.php";
        } else if (currentPage.includes("edit_course.php") || currentPage.includes("add_course.php")) {
            window.location = "courses.php";
        } else if (currentPage.includes("edit_subject.php") || currentPage.includes("view_module.php") ||
            currentPage.includes("add_subject.php")) {
            window.location = "subjects.php";
        } else if (currentPage.includes("report_results_test.php") || currentPage.includes("report_results_quiz.php") ||
            currentPage.includes("report_results_exam.php")) {
            window.location = "report.php";
        } else {
            window.history.back();
        }
    }
</script>