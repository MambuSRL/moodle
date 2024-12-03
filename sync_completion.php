<?php
// Secure the script to prevent unauthorized access.
require(__DIR__.'/config.php');

// Security: Replace 'your-secret-key' with a strong secret key.
$secret_key = 'your-secret-key';
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    http_response_code(403);
    echo "Forbidden: Invalid key.";
    exit;
}

// Load task classes and execute the task.
$task = \core\task\manager::get_scheduled_task('\core\task\completion_regular_task');
if ($task) {
    try {
        $task->execute();
        echo "Task executed successfully.";
    } catch (Exception $e) {
        echo "Error executing task: " . $e->getMessage();
    }
} else {
    echo "Task not found.";
}
