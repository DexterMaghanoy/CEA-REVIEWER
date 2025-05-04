<div class="dropdown mb-3">
    <button class="btn btn-secondary dropdown-toggle" type="button" id="moduleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <span id="moduleDropdownText"><?php echo $module['module_name']; ?></span>
    </button>

    <ul class="dropdown-menu" aria-labelledby="moduleDropdown">
        <?php
        // Check if $modules is set and not empty
        if (isset($modules) && !empty($modules)) {
            // Sort $modules array by module_id in descending order
            usort($modules, function ($a, $b) {
                return $b['module_id'] - $a['module_id'];
            });

            // Iterate over modules
            foreach ($modules as $module) {
                echo '<li><a class="dropdown-item module-item" href="#" data-module-id="' . $module['module_id'] . '">' . $module['module_name'] . '</a></li>';
            }
        } else {
            // Handle the case when $modules is not set or empty
            echo "<li><span class='dropdown-item disabled'>No modules found</span></li>";
        }
        ?>
    </ul>
</div>