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
        // Check if the current page is one of the specified pages
        const pages = ["profile.php", "user.php", "student.php", "program.php", "course.php", "report.php"];
        const currentPage = window.location.href;

        if (pages.some(page => currentPage.includes(page))) {
            window.location = "index.php";
        } else {
            // If not on one of the specified pages, just go back to the previous page
            window.history.back();
        }
    }
</script>