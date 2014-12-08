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
 * Redirect the user to the appropriate submission related page
 *
 * @package   mod_exam
 * @category  grade
 * @copyright 2011 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//require_once(__DIR__ . "../../config.php");
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$id = required_param('id', PARAM_INT);          // Course module ID
$itemnumber = optional_param('itemnumber', 0, PARAM_INT); // Item number, may be != 0 for activities that allow more than one grade per user
$userid = optional_param('userid', 0, PARAM_INT); // Graded user ID (optional)

if ($id) {
    if (!$cm = get_coursemodule_from_id('exam', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$exam = $DB->get_record('exam', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }

} else {
    if (!$exam = $DB->get_record('exam', array('id' => $q))) {
        print_error('invalidquizid', 'exam');
    }
    if (!$course = $DB->get_record('course', array('id' => $exam->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("exam", $exam->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}



require_login($course, false, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_pagelayout('report');
$PAGE->set_url('/mod/exam/grade.php', array('id' => $cm->id));
$PAGE->set_title(format_string($exam->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('grade'), new moodle_url('/mod/exam/grade.php', array('id' => $cm->id) ));

$params = array("examid" => $exam->id);
$sql = "SELECT g.id, u.id AS userid, u.firstname, 
            u.lastname, u.email, g.grade AS rawgrade,
            g.attempttime           
            FROM {user} u, {exam_grades} g
            WHERE u.id = g.userid AND g.examid = :examid";
            
$result = $DB->get_records_sql($sql, $params);     
// Output starts here
echo $OUTPUT->header();

echo html_writer::start_tag('div');
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        $table = new html_table();
            $table->head = array('Name', 'Email', 'Attempted', 'Grade');
            foreach ($result as $gobject){
                $name = $gobject->firstname ." ".$gobject->lastname;
                $table->data[] = array($name, $gobject->email, userdate($gobject->attempttime), $gobject->rawgrade);
            }
        echo html_writer::table($table);
    echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Print footer.
echo $OUTPUT->footer();
