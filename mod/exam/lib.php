<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module exam
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the exam specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_exam
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('exam_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function exam_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:   return true;
        case FEATURE_GRADE_OUTCOMES:    return true;
        case FEATURE_GROUPS:            return true;
        case FEATURE_GROUPINGS:         return true;
        case FEATURE_GROUPMEMBERSONLY:  return true;
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_BACKUP_MOODLE2:    return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
        case FEATURE_USES_QUESTIONS:            return true;

        default:                        return null;
    }
}

/**
 * Saves a new instance of the exam into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $exam An object from the form in mod_form.php
 * @param mod_exam_mod_form $mform
 * @return int The id of the newly inserted exam record
 */
function exam_add_instance(stdClass $exam, mod_exam_mod_form $mform = null) {
    global $DB;

    $exam->timecreated = time();

    if(!$exam->grademethod){
        $exam->grademethod = 1;
    }
    //print_r($exam);exit;
    $examid = $DB->insert_record('exam', $exam);
    $exam->id =$examid;
    exam_grade_item_update($exam);
    return $examid;
}

/**
 * Updates an instance of the exam in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $exam An object from the form in mod_form.php
 * @param mod_exam_mod_form $mform
 * @return boolean Success/Fail
 */
function exam_update_instance(stdClass $exam, mod_exam_mod_form $mform = null) {
    global $DB;

    $exam->timemodified = time();
    $exam->id = $exam->instance;
   //print_r($exam);exit;
    # You may have to add extra stuff in here #

    $DB->update_record('exam', $exam);
    exam_grade_item_update($exam);
    exam_update_grades($exam, 0, false);
    return true;
}

/**
 * Removes an instance of the exam from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function exam_delete_instance($id) {
    global $DB;

    if (! $exam = $DB->get_record('exam', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #
    $DB->delete_records('exam', array('id' => $exam->id));
    $DB->delete_records('exam_grades', array('id' => $exam->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function exam_user_outline($course, $user, $mod, $exam) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $exam the module instance record
 * @return void, is supposed to echp directly
 */
function exam_user_complete($course, $user, $mod, $exam) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in exam activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function exam_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link exam_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function exam_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see exam_get_recent_mod_activity()}

 * @return void
 */
function exam_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function exam_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function exam_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of exam?
 *
 * This function returns if a scale is being used by one exam
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $examid ID of an instance of this module
 * @return bool true if the scale is used by the given exam instance
 */
function exam_scale_used($examid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('exam', array('id' => $examid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of exam.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any exam instance
 */
function exam_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('exam', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}


/**
 * Return total no of user attempts
 *
 * @param int $examid
 * @param int $userid
 * @return object
 */

function exam_user_attempts($examid, $userid = null) {
   global $USER, $CFG ,$DB;
   if(!$userid){
       $userid = $USER->id;
   }
   $attempts= $DB->count_records('exam_grades',array('examid'=>$examid, 'userid'=>$userid));
   return $attempts;
}
/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $examid id of exam
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function exam_get_user_grades($exam, $userid=0) {
    global $CFG, $DB;

    $params = array("examid" => $exam->id,"examid2" => $exam->id);

    if (!empty($userid)) {
        $params["userid"] = $userid;
        $params["userid2"] = $userid;
        $user = "AND u.id = :userid";
        $fuser = "AND uu.id = :userid2";
        $attempts = exam_user_attempts($exam->id, $userid);
    }
    else {
        $user="";
        $fuser="";
        
    }
    //$attempts = exam_user_attempts($exam->id, $userid);
    if ($exam->attempts != 1) {
        switch ($exam->grademethod) {
            case 1:
                // Highest grade
                $sql = "SELECT u.id, u.id AS userid, MAX(g.grade) AS rawgrade
                        FROM {user} u, {exam_grades} g
                        WHERE u.id = g.userid AND g.examid = :examid
                           $user
                        GROUP BY u.id";
                break;
            case 2:
                // Average grade
                $sql = "SELECT u.id, u.id AS userid, AVG(g.grade) AS rawgrade
                        FROM {user} u, {exam_grades} g
                        WHERE u.id = g.userid AND g.examid = :examid
                           $user
                        GROUP BY u.id";
                break;
            case 3:
                // First grade
                $firstonly = "SELECT uu.id AS userid, MIN(gg.id) AS firstcompleted
                        FROM {user} uu, {exam_grades} gg
                        WHERE uu.id = gg.userid AND gg.examid = :examid2
                             $fuser
                        GROUP BY uu.id";

                $sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
                        FROM {user} u, {exam_grades} g, ($firstonly) f
                        WHERE u.id = g.userid AND g.examid = :examid
                        AND g.id = f.firstcompleted AND g.userid=f.userid
                        $user";
                break;
            case 4:
                // Last grade
                $lastonly = "SELECT uu.id AS userid, MAX(gg.id) AS lastcompleted
                        FROM {user} uu, {exam_grades} gg
                        WHERE uu.id = gg.userid AND gg.examid = :examid2
                             $fuser
                        GROUP BY uu.id";

                $sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
                        FROM {user} u, {exam_grades} g, ($lastonly) l
                        WHERE u.id = g.userid AND g.examid = :examid
                        AND g.id = l.lastcompleted AND g.userid=l.userid
                        $user";
                break;
        }
        //look for grading methods
    }else{
        unset($params['examid2']);
        unset($params['userid2']);

        $sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
                  FROM {user} u, {exam_grades} g
                 WHERE u.id = g.userid AND g.examid = :examid                
                       $user";
    }
    return $DB->get_records_sql($sql, $params);
}
/**
 * Creates or updates grade item for the give exam instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $exam instance object with extra cmidnumber and modname property
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function exam_grade_item_update($exam, $grades=null) {
    global $CFG, $DB;
    //require_once($CFG->libdir.'/gradelib.php');
    
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
        if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        echo "not found";exit;
    }

    $totalquestion = $DB->count_records('quiz_slots', array('quizid' => $exam->quizid));
    //$params = array('itemname'=>$exam->name);
    /** @example */
    $params = array();
    $params['itemname'] = $exam->name;
    //$params['itemname'] = clean_param($exam->name, PARAM_NOTAGS);
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = $exam->grade; //$totalquestion* $exam->marksperquestion;
    $params['grademin']  = 0;
    
    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

     return grade_update('mod/exam', $exam->course, 'mod', 'exam', $exam->id, 0, $grades, $params);

}

/**
 * Update exam grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $exam instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function exam_update_grades($exam, $userid = 0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($exam->grade == 0) { 
        exam_grade_item_update($exam);

    }else if($grades = exam_get_user_grades($exam, $userid)){
        exam_grade_item_update($exam, $grades);
        
    }else if($userid and $nullifnone) {
        
        $grade = new stdclass();
        $grade->userid = $userid;
        $grade->rawgrade = NULL;
        exam_grade_item_update($exam,$grade);
    }else{
        exam_grade_item_update($exam);
    }
}
/**
 * Delete grade item for given exam
 *
 * @category grade
 * @param object $exam object
 * @return object exam
 */
function exam_grade_item_delete($exam) {
    global $CFG;

}


////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function exam_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for exam file areas
 *
 * @package mod_exam
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function exam_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the exam file areas
 *
 * @package mod_exam
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the exam's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function exam_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) { 
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);
    
    if (!$exam = $DB->get_record('exam', array('id'=>$cm->instance))) {
        return false;
    }

    // The 'intro' area is served by pluginfile.php.
    $fileareas = array('feedback');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $feedbackid = (int)array_shift($args);
    if (!$feedback = $DB->get_record('quiz_feedback', array('id'=>$feedbackid))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_exam/$filearea/$feedbackid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true, $options);

    send_file_not_found();
}


/**
 * Called via pluginfile.php -> question_pluginfile to serve files belonging to
 * a question in a question_attempt when that attempt is a exam attempt.
 *
 * @package  mod_exam
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this quiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function exam_question_pluginfile($course, $context, $component,
        $filearea, $qubaid, $slot, $args, $forcedownload, array $options=array()) { 
    global $CFG;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    //$attemptobj = quiz_attempt::create_from_usage_id($qubaid);
    //require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm);
    
    $quba = question_engine::load_questions_usage_by_activity($qubaid);

    if (!question_has_capability_on($quba->get_question($slot), 'use')) {
        send_file_not_found();
    }

    $qoptions = new question_display_options();
    $qoptions->feedback = question_display_options::VISIBLE;
    $qoptions->numpartscorrect = question_display_options::VISIBLE;
    $qoptions->generalfeedback = question_display_options::VISIBLE;
    $qoptions->rightanswer = question_display_options::VISIBLE;
    $qoptions->manualcomment = question_display_options::VISIBLE;
    $qoptions->history = question_display_options::VISIBLE;
    if (!$quba->check_file_access($slot, $qoptions, $component,
            $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }

   /* if ($attemptobj->is_own_attempt() && !$attemptobj->is_finished()) {
        // In the middle of an attempt.
        if (!$attemptobj->is_preview_user()) {
            $attemptobj->require_capability('mod/quiz:attempt');
        }
        $isreviewing = false;

    } else {
        // Reviewing an attempt.
        $attemptobj->check_review_capability();
        $isreviewing = true;
    }

    if (!$attemptobj->check_file_access($slot, $isreviewing, $context->id,
            $component, $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }*/

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    //var_dump(sha1($fullpath));exit;
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}


////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding exam nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the exam module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function exam_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the exam settings
 *
 * This function is called when the context for the page is a exam module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $examnode {@link navigation_node}
 */
function exam_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $examnode=null) {
}


function exam_file_path($args, $forcedownload, $options /*$contextid,$component,$filearea,$itemid*/){   
    global $DB;
    $options = array('preview' => $options);
    $fs = get_file_storage();
    $relativepath = explode('/', $args);
    //print_r($relativepath);exit;
    //$fullpath = "/$context->id/$component/$filearea/$relativepath";
    //var_dump(sha1($fullpath));exit;
   /* if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }*/
    $hashpath = $DB->get_field('files', 'pathnamehash', array("contextid"=>$relativepath[1],
        'component' =>$relativepath[2], 'filearea' => $relativepath[3], 'itemid' => $relativepath[4], 'filename' => $relativepath[5]));
        
    if (!$file = $fs->get_file_by_hash($hashpath) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}


function exam_file_rewrite_pluginfile_urls($text, $file, $contextid, $component, $filearea, $itemid, $filename, array $options=null) {
    global $CFG;

    $options = (array)$options;
    if (!isset($options['forcehttps'])) {
        $options['forcehttps'] = false;
    }

    if (!$CFG->slasharguments) {
        $file = $file . '?file=';
    }

    $baseurl = "$CFG->wwwroot/$file/$contextid/$component/$filearea/";

    if ($itemid !== null) {
        $baseurl .= "$itemid/$filename";
    }

    if ($options['forcehttps']) {
        $baseurl = str_replace('http://', 'https://', $baseurl);
    }
    $replaceurl = "$CFG->wwwroot/$file/$contextid/$component/$filearea/$itemid/";
    return str_replace('@@PLUGINFILE@@/', $replaceurl, $text);
}

function exam_formate_text($questiondata,$text,$formate,$component, $filearea,$itemid){
    global $PAGE, $DB;

    $context = context_module::instance($PAGE->cm->id);
    if (!empty($text)) {
        if (!isset($formate)) {
            $formate = FORMAT_HTML;
        }
        $pattern = '/src="@@PLUGINFILE@@\/(.*?)"/';
        preg_match($pattern,$text, $matches);
        if(!empty($matches)){
            $filename = $matches[1];
            $f = 'mod/exam/pluginfile.php';
            $contents = exam_file_rewrite_pluginfile_urls($text, $f, $questiondata->contextid, $component, $filearea, $itemid, $filename);
            //$contents =exam_make_html_inline($contents);
           /* return format_text($contents, $questiondata->questiontextformat,
                                   array('context' => $context, 'noclean' => true,
                                         'overflowdiv' => true)); */
            return  exam_make_html_inline($contents);
    
        } else {
            return  exam_make_html_inline($text);
        }
    } else {
        return '';
    }
}

function exam_make_html_inline($html) {
        $html = preg_replace('~\s*<p>\s*~u', '', $html);
        $html = preg_replace('~\s*</p>\s*~u', '<br />', $html);
        $html = preg_replace('~(<br\s*/?>)+$~u', '', $html);
        return trim($html);
}
