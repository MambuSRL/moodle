<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/enrol/manual/lib.php'); 
require_once("$CFG->dirroot/course/lib.php");

class local_mamboodle_external extends external_api {

	public static function get_course_completion_participants_parameters() {
		return new external_function_parameters(
			array(
				'a_idCorsoMoodle' => new external_multiple_structure(
					new external_value(PARAM_INT, 'Course ID')
				)
			)
		);
	}

	public static function get_course_completion_participants($a_idCorsoMoodle) {
		global $DB;
		$params = self::validate_parameters(self::get_course_completion_participants_parameters(), array('a_idCorsoMoodle' => $a_idCorsoMoodle));
		$course_ids = $params['a_idCorsoMoodle'];
		if (empty($course_ids)) {
			throw new invalid_parameter_exception('Array di ID dei corsi vuoto');
		}
		$course_ids = implode(',', $course_ids);
		$sql = "SELECT c.id, u.idnumber AS user_idnumber, u.timecreated, u.timemodified, cc.timecompleted
				FROM {course} c
				JOIN {course_completions} cc ON c.id = cc.course
				JOIN {user} u ON cc.userid = u.id
				WHERE c.id IN ($course_ids) 
				AND cc.timecompleted IS NOT NULL 
				ORDER BY c.id, u.idnumber";
		$records = $DB->get_records_sql($sql);
		$result = [];
		$a_id = [];
		foreach ($records as $record) {
			if(!in_array($record->id, $a_id)){
				$a_id[] = $record->id;
				$result[] = [
					"course_id" => $record->id,
					"completions" => []
				];
			}
			$result[count($result)-1]["completions"][] = [
				"user_idnumber" => $record->user_idnumber,
				"timecompleted" => $record->timecompleted
			];
		}
		return $result;
	}

	public static function get_course_completion_participants_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array(
					"course_id" => new external_value(PARAM_INT, 'Course ID'),
					"completions" => new external_multiple_structure(
						new external_single_structure(
							array(
								"user_idnumber" => new external_value(PARAM_TEXT, 'User ID number'),
								"timecompleted" => new external_value(PARAM_INT, 'Time of completion in Unix timestamp format')
							)
						)
					)
				)
			)
		);
	}

	public static function loadModelli_parameters() {
		return new external_function_parameters(
			array()
		);
	}

	public static function loadModelli(){
		global $DB;
		// Query the database to get each cours that has idnumber not null
		$sql = "SELECT * FROM {course} WHERE idnumber IS NOT NULL AND idnumber != ''";
		$modelli = $DB->get_records_sql($sql);
		$result = [];
		foreach ($modelli as $modello) {
			$result[] = [
				'id' => $modello->id,
				'fullname' => $modello->fullname,
				'shortname' => $modello->shortname,
				'idnumber' => $modello->idnumber,
				'startdate' => $modello->startdate,
				'enddate' => $modello->enddate,
			];
		}
		return $result;
	}

	public static function loadModelli_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array(
					'id' => new external_value(PARAM_INT, 'Course ID'),
					'fullname' => new external_value(PARAM_TEXT, 'Full name of the course'),
					'shortname' => new external_value(PARAM_TEXT, 'Short name of the course'),
					'idnumber' => new external_value(PARAM_TEXT, 'ID number of the course'),
					'startdate' => new external_value(PARAM_INT, 'Start date of the course in Unix timestamp format'),
					'enddate' => new external_value(PARAM_INT, 'End date of the course in Unix timestamp format'),
				)
			)
		);
	}

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
				/* 'idLezioneEdumbu' => new external_value(PARAM_INT, 'ID of the lesson in Edumbu') */
			)
		);
	}

	public static function create_course_from_model($new_course_name, $new_course_shortname, $existing_course_id, $startdate, $enddate){//, int $idLezioneEdumbu) {
		global $DB, $CFG;
		require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
		require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
	
		// Validazione dei parametri
		$params = self::validate_parameters(self::create_course_from_model_parameters(), array(
			'new_course_name' => $new_course_name,
			'new_course_shortname' => $new_course_shortname,
			'existing_course_id' => $existing_course_id,
			'startdate' => $startdate,
			'enddate' => $enddate,
			/* 'idLezioneEdumbu' => $idLezioneEdumbu */
		));
	
		// Verifica se il corso esiste
		if (!$existing_course = $DB->get_record('course', ['idnumber' => $params['existing_course_id']])) {
			throw new invalid_parameter_exception('ID del corso non valido. Il corso con l\'ID fornito non esiste.');
		}
	
		// Esegui il backup
		$backup_controller = new backup_controller(
			backup::TYPE_1COURSE, 
			$existing_course->id, 
			backup::FORMAT_MOODLE, 
			backup::INTERACTIVE_NO, 
			backup::MODE_SAMESITE, 
			2
		);		 

		// Set the default filename.
		$format = $backup_controller->get_format();
		$type = $backup_controller->get_type();
		$id = $backup_controller->get_id();
		$users = $backup_controller->get_plan()->get_setting('users')->get_value();
		$anonymised = $backup_controller->get_plan()->get_setting('anonymize')->get_value();
		$filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
		$backup_controller->get_plan()->get_setting('filename')->set_value($filename);

		// Execution.
		$backup_controller->execute_plan();
		$results = $backup_controller->get_results();
		$file = $results['backup_destination']; // May be empty if file already moved to target location.
		$dir = $CFG->backuptempdir;
		// Do we need to store backup somewhere else?
		$fullPath = $dir.'/'.$filename;
		$filename_without_extension = substr($filename, 0, strrpos($filename, '.'));
		$dirFullPath = $dir.'/'.$filename_without_extension;
		if ($file) {
			if ($file->copy_content_to($dir.'/'.$filename)) {
				$file->delete();
			} else {
			}
		}

        $fb = get_file_packer('application/vnd.moodle.backup');
        $result = $fb->extract_to_pathname($fullPath, $dir . '/' . $filename_without_extension. '/');

		$id_new = restore_dbops::create_new_course( $new_course_name, $new_course_shortname, $existing_course->category);
		// Create new course.
		try{
			$restore_controller = new restore_controller(
				$filename_without_extension,         // Full path to the backup file
				$id_new,                 // Set to 0 to indicate that the controller should create a new course
				backup::INTERACTIVE_NO,
				backup::MODE_GENERAL,
				2,                 // User ID of the user performing the restore
				backup::TARGET_NEW_COURSE
			);
		}
		catch(Exception $e){
			//throw new moodle_exception('errorcreatingcourse', 'local_mamboodle', '', $e->getMessage());
		}
		
		// Esegui il ripristino
		if ($restore_controller->get_status() == backup::STATUS_NEED_PRECHECK) {
			if (!$restore_controller->execute_precheck()) {
				$errors = $restore_controller->get_precheck_results();
				throw new moodle_exception('Pre-check failed: ' . implode(', ', $errors));
			}
		}
		// Proceed with the restore
		$restore_controller->execute_plan();
		
		// Get the ID of the newly created course
		$new_course_id = $restore_controller->get_courseid();

		// Aggiorno le date del corso
		$course = $DB->get_record('course', ['id' => $new_course_id]);
		$course->startdate = $startdate;
		$course->enddate = $enddate;
		$course->fullname = $new_course_name;
		$course->shortname = $new_course_shortname;
		/* $course->idnumber = $idLezioneEdumbu; */
		$DB->update_record('course', $course);

		
		// Pulisci
		$backup_controller->destroy();
		remove_dir($dirFullPath);  // Remove the temporary directory
		$restore_controller->destroy();  // Clean up the controller
		return array('new_course_id' => $new_course_id);
	}
		
	public static function create_course_from_model_returns() {
		$test = new external_single_structure(
			array(
				'new_course_id' => new external_value(PARAM_INT, 'ID of the newly created course'),
				// Add other return values as needed
			)
		);
		return new external_single_structure(
			array(
				'new_course_id' => new external_value(PARAM_INT, 'ID of the newly created course'),
				// Add other return values as needed
			)
		);
	}
	
	public static function enroll_users_to_course($course_id, $user_list) {
		global $DB;
		global $CFG;
		$params = self::validate_parameters(self::enroll_users_to_course_parameters(), array(
			'course_id' => $course_id,
			'user_list' => $user_list
		));

		$course = $DB->get_record('course', ['id' => $params['course_id']]);
		if (!$course) {
			throw new invalid_parameter_exception('ID del corso non valido. Il corso con l\'ID fornito non esiste.');
		}
		// Retrieve the manual enrollment instance for the course
		$enrol_instance = $DB->get_record('enrol', [
			'courseid' => $course->id, 
			'enrol' => 'manual'
		]);

		// Check if the enrollment instance exists
		if (!$enrol_instance) {
			throw new moodle_exception('No manual enrollment instance found for the course');
		}
		// Get the manual enrollment plugin
		$manual_enrol = new enrol_manual_plugin();

		/* function generate_random_password($length = 12) {
			$lowercase = 'abcdefghijklmnopqrstuvwxyz';
			$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$digits = '0123456789';
			$special = '!@#$%^&*()-_=+';
			$all_characters = $lowercase . $uppercase . $digits . $special;
		
			// Ensure the password includes at least one of each character type
			$password = '';
			$password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
			$password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
			$password .= $digits[random_int(0, strlen($digits) - 1)];
			$password .= $special[random_int(0, strlen($special) - 1)];
		
			// Fill the remaining length with random characters
			for ($i = 4; $i < $length; $i++) {
				$password .= $all_characters[random_int(0, strlen($all_characters) - 1)];
			}
		
			// Shuffle the password to avoid predictable patterns
			$password = str_shuffle($password);
		
			return $password;
		} */

		//per ogni record di user_list, verifico se l'anagrafica è già presente (cf) e se non lo è, la creo
		foreach ($params['user_list'] as $user) {
			$existing_user = $DB->get_record('user', ['idnumber' => $user['iduse']]);
			if (!$existing_user) {
				// Creo l'utente
				$new_user_password = generate_password();
				$new_user = new stdClass();
				$new_user->username = $user['usern'];
				$new_user->password = "placeholder"; // Temporary password placeholder
				$new_user->firstname = $user['vnome'];
				$new_user->lastname = $user["vcogn"];
				$new_user->email = $user['email'];
				$new_user->auth = 'manual';
				$new_user->confirmed = 1;
				$new_user->mnethostid = $CFG->mnet_localhost_id;
				$new_user->lang = 'it';
				$new_user->idnumber = $user['iduse'];
				$new_user->timecreated = time();
				$new_user->timemodified = time();
				$user_id = $DB->insert_record('user', $new_user);
				$existing_user = $DB->get_record('user', ['id' => $user_id]);
				// Aggiorno l'utente con la password generata
				update_internal_user_password($existing_user, $new_user_password);
			}
			else {
				$user_id = $existing_user->id;
			}
			// Iscrivo l'utente al corso
			$student_role_id = $DB->get_field('role', 'id', ['shortname' => 'student']);
			$manual_enrol->enrol_user($enrol_instance, $user_id, $student_role_id, time());
		}
		return ['success' => true];
	}

	public static function enroll_users_to_course_parameters() {
		return new external_function_parameters(
			array(
				'course_id' => new external_value(PARAM_INT, 'ID of the course'),
				'user_list' => new external_multiple_structure(
					new external_single_structure(
						array(
							'iduse' => new external_value(PARAM_INT, 'ID of the user'),
							'usern' => new external_value(PARAM_TEXT, 'Username of the user'),
							'email' => new external_value(PARAM_TEXT, 'Email of the user'),
							'nato_il' => new external_value(PARAM_INT, 'Date of birth of the user in Unix timestamp format', VALUE_OPTIONAL),
							'cf' => new external_value(PARAM_TEXT, 'Fiscal code of the user'),
							'cnome' => new external_value(PARAM_TEXT, 'Name and surname of the user'),
							'vnome' => new external_value(PARAM_TEXT, 'Name of the user'),
							'vcogn' => new external_value(PARAM_TEXT, 'Surname of the user'),
						)
					)
				),
			)
		);
	}

	public static function enroll_users_to_course_returns() {
		return new external_single_structure(
			array(
				'success' => new external_value(PARAM_BOOL, 'True if the operation was successful, false otherwise'),
			)
		);
	}

	public static function sync_users_to_course($course_id, $user_list) {
		global $DB;
		global $CFG;
		$params = self::validate_parameters(self::enroll_users_to_course_parameters(), array(
			'course_id' => $course_id,
			'user_list' => $user_list
		));

		$course = $DB->get_record('course', ['id' => $params['course_id']]);
		if (!$course) {
			throw new invalid_parameter_exception('ID del corso non valido. Il corso con l\'ID fornito non esiste.');
		}
		// Retrieve the manual enrollment instance for the course
		$enrol_instance = $DB->get_record('enrol', [
			'courseid' => $course->id, 
			'enrol' => 'manual'
		]);
		// Check if the enrollment instance exists
		if (!$enrol_instance) {
			throw new moodle_exception('No manual enrollment instance found for the course');
		}
		// Get the manual enrollment plugin
		$manual_enrol = new enrol_manual_plugin();

		//trovo la lista degli utenti già iscritti al corso
		$enrolled_users = $DB->get_records_sql("SELECT u.id, u.idnumber FROM {user} u JOIN {user_enrolments} ue ON u.id = ue.userid JOIN {enrol} e ON ue.enrolid = e.id WHERE e.courseid = ?", [$course->id]);
		$enrolled_users_idnumber = [];
		foreach ($enrolled_users as $user) {
			$enrolled_users_idnumber[] = $user->idnumber;
		}

		//trovo gli utenti da rimuovere e quelli da iscrivere
		$users_to_add = [];
		foreach($user_list as $user){
			if(!in_array($user['iduse'], $enrolled_users_idnumber)){
				$users_to_add[] = $user;
			}
		}
		$users_to_remove = [];
		foreach($enrolled_users_idnumber as $id){
			$found = false;
			foreach($user_list as $user){
				if($user['iduse'] == $id){
					$found = true;
					break;
				}
			}
			if(!$found){
				$users_to_remove[] = $id;
			}
		}

		//rimuovo i partecipanti non più presenti
		foreach($users_to_remove as $id){
			$existing_user = $DB->get_record('user', ['idnumber' => $id]);
			$manual_enrol->unenrol_user($enrol_instance, $existing_user->id);
		}
		
		//per ogni record di user_list, verifico se l'anagrafica è già presente (cf) e se non lo è, la creo
		foreach ($users_to_add as $user) {
			$existing_user = $DB->get_record('user', ['idnumber' => $user['iduse']]);
			if (!$existing_user) {
				// Creo l'utente
				$new_user_password = generate_password();
				$new_user = new stdClass();
				$new_user->username = $user['usern'];
				$new_user->password = "placeholder"; // Temporary password placeholder
				$new_user->firstname = $user['vnome'];
				$new_user->lastname = $user["vcogn"];
				$new_user->email = $user['email'];
				$new_user->auth = 'manual';
				$new_user->confirmed = 1;
				$new_user->mnethostid = $CFG->mnet_localhost_id;
				$new_user->lang = 'it';
				$new_user->idnumber = $user['iduse'];
				$new_user->timecreated = time();
				$new_user->timemodified = time();
				$user_id = $DB->insert_record('user', $new_user);
				$existing_user = $DB->get_record('user', ['id' => $user_id]);
				// Aggiorno l'utente con la password generata
				update_internal_user_password($existing_user, $new_user_password);
			}
			else {
				$user_id = $existing_user->id;
			}
			// Se non è già iscritto, iscrivo l'utente al corso, altrimenti lo ignoro
			$student_role_id = $DB->get_field('role', 'id', ['shortname' => 'student']);
			$manual_enrol->enrol_user($enrol_instance, $user_id, $student_role_id, time());
		}
		return ['success' => true];
	}

	public static function sync_users_to_course_parameters() {
		return new external_function_parameters(
			array(
				'course_id' => new external_value(PARAM_INT, 'ID of the course'),
				'user_list' => new external_multiple_structure(
					new external_single_structure(
						array(
							'iduse' => new external_value(PARAM_INT, 'ID of the user'),
							'usern' => new external_value(PARAM_TEXT, 'Username of the user'),
							'email' => new external_value(PARAM_TEXT, 'Email of the user'),
							'nato_il' => new external_value(PARAM_INT, 'Date of birth of the user in Unix timestamp format', VALUE_OPTIONAL),
							'cf' => new external_value(PARAM_TEXT, 'Fiscal code of the user'),
							'cnome' => new external_value(PARAM_TEXT, 'Name and surname of the user'),
							'vnome' => new external_value(PARAM_TEXT, 'Name of the user'),
							'vcogn' => new external_value(PARAM_TEXT, 'Surname of the user'),
						),
						VALUE_OPTIONAL
					),
					'List of users to enroll in the course', VALUE_DEFAULT, []
				),
			)
		);
	}

	public static function sync_users_to_course_returns() {
		return new external_single_structure(
			array(
				'success' => new external_value(PARAM_BOOL, 'True if the operation was successful, false otherwise'),
			)
		);
	}
}
