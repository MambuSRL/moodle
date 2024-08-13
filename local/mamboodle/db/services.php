<?php

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_mamboodle_get_users_custom' => array(
        'classname' => 'local_mamboodle_external',
        'methodname' => 'get_users_custom',
        'classpath' => 'local/mamboodle/externallib.php',
        'description' => 'Get users by array of IDs',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/user:viewdetails',
	),
    'local_mamboodle_create_course_from_model' => array(
        'classname' => 'local_mamboodle_external',
        'methodname' => 'create_course_from_model',
        'classpath' => 'local/mamboodle/externallib.php',
        'description' => 'Create a new course based on an existing course model.',
        'type' => 'write',
        'ajax' => false,
    ),
    'local_mamboodle_loadModelli' => array(
        'classname' => 'local_mamboodle_external',
        'methodname' => 'loadModelli',
        'classpath' => 'local/mamboodle/externallib.php',
        'description' => 'Carica i modelli di corsi',
        'type' => 'read',
        'ajax' => true,
    ),
);
