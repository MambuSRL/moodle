<?php
define('CLI_SCRIPT', true);
require(__DIR__.'/config.php');

// Load task classes.
$task = \core\task\manager::get_scheduled_task('\core\task\completion_regular_task');
$task->execute();
echo "Task executed successfully.";
