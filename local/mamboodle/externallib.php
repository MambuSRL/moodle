<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_mamboodle_external extends external_api {

    public static function get_users_custom_parameters() {
        return new external_function_parameters(
            array(
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User IDs array')
                ),
            )
        );
    }

    public static function get_users_custom($userids) {
        global $DB;

        // Parameter validation
        $params = self::validate_parameters(self::get_users_custom_parameters(), array('userids' => $userids));

        // Capability checking
        // (Implement if needed, e.g., require_capability())

        // Actual retrieval of users
        $users = $DB->get_records_list('user', 'id', $params['userids']);
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                // Include other fields as needed
            ];
        }

        return $result;
    }

    public static function get_users_custom_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    // Define other fields as needed
                )
            )
        );
    }

	public static function create_course_from_model_parameters() {
		return new external_function_parameters(
			array(
				'new_course_name' => new external_value(PARAM_TEXT, 'Name of the new course'),
				'new_course_shortname' => new external_value(PARAM_TEXT, 'Short name of the new course'),
				'existing_course_id' => new external_value(PARAM_INT, 'ID of the existing course'),
				'startdate' => new external_value(PARAM_INT, 'Start date of the new course in Unix timestamp format'),
				'enddate' => new external_value(PARAM_INT, 'End date of the new course in Unix timestamp format'),
			)
		);
	}
	
	public static function create_course_from_model($new_course_name, $new_course_shortname, $existing_course_id, $startdate, $enddate) {
		global $DB;
	
		// Parameter validation
		$params = self::validate_parameters(self::create_course_from_model_parameters(), array(
			'new_course_name' => $new_course_name,
			'new_course_shortname' => $new_course_shortname,
			'existing_course_id' => $existing_course_id,
			'startdate' => $startdate,
			'enddate' => $enddate
		));
	
		// Retrieve details of the existing course
		$existing_course = $DB->get_record('course', ['id' => $params['existing_course_id']]);
		
		// Query the database to get the greatest sortorder among all courses in the category
		$sql = "SELECT MAX(sortorder) AS max_sortorder FROM {course} WHERE category = :category";
		$sqlParams = array('category' => $existing_course->category);
		$maxSortOrder = $DB->get_field_sql($sql, $sqlParams);

		// Create a new course based on the existing one
		$new_course_params = array(
			'fullname' => $params['new_course_name'],
			'shortname' => $params['new_course_shortname'],
			'category' => $existing_course->category,
			'summary' => $existing_course->summary,
			'timecreated' => time(),
			'timemodified' => time(),
			'startdate' => $params['startdate'],
			'enddate' => $params['enddate'],
			'sortorder' => (int)$maxSortOrder + 1,
		);
	
		$new_course_id = $DB->insert_record('course', (object)$new_course_params);
		
		// Retrieve sections from the existing course
		$existing_course_sections = $DB->get_records('course_sections', ['course' => $params['existing_course_id']]);

		// Prepare an array to hold the section details for creation
		$new_sections = array();

		foreach ($existing_course_sections as $section) {
			// Prepare parameters for creating the section in the new course
			$section_params = array(
				'courseid' => $new_course_id,
				'section' => $section->section,
				'summary' => $section->summary,
				'sequence' => $section->sequence,
				// Add other parameters as needed
			);

			// Add section details to the array
			$new_sections[] = $section_params;
		}
		// Create sections in the new course
		course_create_sections_if_missing($new_course_id, $new_sections);

		// Retrieve activities from the existing course
		$existing_course_activities = $DB->get_records('course_modules', ['course' => $existing_course_id]);
	
		// Create activities in the new course based on the activities from the model course
		foreach ($existing_course_activities as $activity) {
			// Get activity details
			$activity_details = $DB->get_record('course_modules', ['id' => $activity->id]);
	
			// Prepare parameters for creating the activity in the new course
			$activity_params = array(
				'courseid' => $new_course_id,
				'module' => $activity_details->module,
				'instance' => $activity_details->instance,
				'section' => $activity_details->section,
				// Add other parameters as needed
			);
	
			// Create the activity in the new course
			$new_activity_id = $DB->insert_record('course_modules', (object)$activity_params);
	
			// Update course section
			$DB->set_field('course_modules', 'section', $activity_details->section, ['id' => $new_activity_id]);
		}
	
		// Optionally, you can return any relevant information or handle errors that may occur during the process
		
	}
	
	public static function create_course_from_model_returns() {
		return new external_single_structure(
			array(
				'new_course_id' => new external_value(PARAM_INT, 'ID of the newly created course'),
				// Add other return values as needed
			)
		);
	}
	
}
