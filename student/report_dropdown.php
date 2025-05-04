<?php
// Get the current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Define report button label based on current page
$reportButtonLabel = match ($currentPage) {
    'report_questions.php' => 'Module Test',
    'report_quiz.php'      => 'Subject Quiz',
    'report_exam.php'      => 'Exam',
    default                => 'Module Test' // fallback label
};

// Define report options
$reports = [
    ['title' => 'Module Test', 'link' => 'report_questions.php'],
    ['title' => 'Subject Quiz', 'link' => 'report_quiz.php'],
    ['title' => 'Exam', 'link' => 'report_exam.php']
];
?>

<div class="dropdown mb-3">
    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
        <?php echo $reportButtonLabel; ?>
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <?php foreach ($reports as $report): ?>
            <?php if ($report['title'] !== $reportButtonLabel): ?>
                <li><a class="dropdown-item" href="<?php echo $report['link']; ?>"><?php echo $report['title']; ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>


<style>
    @media (min-width: 769px) {
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    }


    @media (max-height: 500px) {

        .dropdown:hover .dropdown-menu {
            display: none;
        }


    }
</style>


<script>
    $(document).ready(function() {
        $('#dropdownMenuButton').on('click', function() {
            var $dropdownMenu = $(this).next('.dropdown-menu');
            if ($dropdownMenu.is(':visible')) {
                $dropdownMenu.hide();
            } else {
                $dropdownMenu.show();
            }
        });
    });
</script>