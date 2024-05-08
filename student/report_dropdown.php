<div class="dropdown mb-3">
    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" aria-expanded="false">
        Report Type
    </button>
    <ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenuButton">
        <?php
        // Define report types
        $reports = [
            ['title' => 'Module Tests', 'link' => 'report_questions.php'],
            ['title' => 'Subject Quizzes', 'link' => 'report_quiz.php'],
            ['title' => 'Exams', 'link' => 'report_exam.php']
        ];

        // Generate dropdown items dynamically
        foreach ($reports as $report) {
        ?>
            <li><a class="dropdown-item btn btn-success" href="<?php echo $report['link']; ?>"><?php echo $report['title']; ?></a></li>
        <?php
        }
        ?>
        
    </ul>
</div>

<style>
    .dropdown:hover .dropdown-menu {
        display: block;

    }


</style>