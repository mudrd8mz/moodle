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
 * Prints a particular instance of exam
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_exam
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/exam/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // exam instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('exam', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $exam  = $DB->get_record('exam', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $exam  = $DB->get_record('exam', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $exam->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('exam', $exam->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/exam:view', $context);

if (!$qzcm = get_coursemodule_from_instance('quiz', $exam->quizid, $course->id)) {
    print_error('invalidcoursemodule');
}

// Log this request.
$params = array(
    'objectid' => $exam->id,
    'context' => $context
);
$event = \mod_exam\event\course_module_viewed::create($params);
$event->add_record_snapshot('exam', $exam);
$event->trigger();

//completion
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Print the page header
$PAGE->set_url('/mod/exam/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($exam->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$quizobj = quiz::create($qzcm->instance, $USER->id);
// If no questions have been set up yet redirect to edit.php or display an error.
if (!$quizobj->has_questions()) {
    if ($quizobj->has_capability('mod/quiz:manage')) {
        redirect($quizobj->edit_url());
    } else {
        print_error('cannotstartnoquestions', 'quiz', $quizobj->view_url());
    }
}

// Output starts here
echo $OUTPUT->header();
echo $OUTPUT->heading($exam->name .' ('. $quizobj->get_quiz()->name.')');
if ($exam->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('exam', $exam, $cm->id), 'generalbox mod_introbox', 'examintro');
}

$attempted_attempt = exam_user_attempts($exam->id, $USER->id);

$output = html_writer::start_tag('div', array('class' => 'quizinfo'));
$output .= html_writer::tag('p', get_string('attemptallowed', 'exam', $exam->attempts ? $exam->attempts : 'unlimited'));
$output .= html_writer::tag('p', get_string('gradingmethod', 'quiz',
            quiz_get_grading_option_name($exam->grademethod)));
$output .= html_writer::tag('p', get_string('attempted', 'exam',$attempted_attempt));
$output .= html_writer::end_tag('div');
echo $output;

$grade =exam_get_user_grades($exam, $USER->id);

if(($exam->attempts == 0) || $attempted_attempt < $exam->attempts) {
    echo $OUTPUT->single_button(new moodle_url('/mod/exam/attempt.php?id='.$id, array('id' => $id)), get_string('attemptexamnow', 'exam'));
    if($attempted_attempt >0){
        echo $OUTPUT->heading(quiz_get_grading_option_name($exam->grademethod) .' grade : '. number_format($grade[$USER->id]->rawgrade, 2). ' / ' .number_format($exam->grade, 2));
    }
}else{  
    echo $OUTPUT->heading(get_string('yourfinalgradeis', 'exam', number_format($grade[$USER->id]->rawgrade, 2)). number_format($exam->grade,2));
    echo $output = html_writer::tag('p', get_string('nomoreattempts', 'quiz'));         
}
// Finish the page
echo $OUTPUT->footer();
