

<div class="dropdown mb-3">
    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" aria-expanded="false">
        Report Type
    </button>
    <ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenuButton">
        <?php
        // Define report types
        $reports = [
            ['title' => 'Module Tests', 'link' => 'student_record_test.php?student_id='.$stud_id],
            ['title' => 'Subject Quizzes', 'link' => 'student_record_quiz.php?student_id='.$stud_id],
            ['title' => 'Exams', 'link' => 'student_record_exam.php?student_id='.$stud_id]
        ];

        // Generate dropdown items dynamically
        foreach ($reports as $report) {
        ?>
            <li><a class="dropdown-item" href="<?php echo $report['link']; ?>"><?php echo $report['title']; ?></a></li>
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
